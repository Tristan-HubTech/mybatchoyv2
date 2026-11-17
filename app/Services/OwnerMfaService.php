<?php

namespace App\Services;

use App\Mail\SecurityMfaCode;
use App\Models\AuditLog;
use App\Models\OwnerMfaChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class OwnerMfaService
{
    private const CHALLENGE_TTL_MINUTES = 10;

    public function __construct(private readonly TotpService $totp)
    {
    }

    public function issueEmailChallenge(User $owner, Request $request, string $purpose = 'session_recovery'): void
    {
        $rateKey = "owner-mfa-issue:{$owner->id}:{$purpose}";
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'mfa_code' => ["Too many verification code requests. Try again in {$seconds} seconds."],
            ]);
        }

        if (!MailTransportGuard::isSafeForOtp()) {
            Log::warning('Owner MFA email not sent because the configured mail transport does not support secure live delivery.');

            throw ValidationException::withMessages([
                'mfa_code' => ['Email verification codes are unavailable because production SMTP mail is not configured. Use your Authenticator app or configure SMTP mail settings.'],
            ]);
        }

        RateLimiter::hit($rateKey, 300);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = Hash::make($code);

        $challenge = DB::transaction(function () use ($owner, $purpose, $codeHash, $request): OwnerMfaChallenge {
            OwnerMfaChallenge::where('user_id', $owner->id)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->delete();

            $record = OwnerMfaChallenge::create([
                'user_id' => $owner->id,
                'code_hash' => $codeHash,
                'purpose' => $purpose,
                'expires_at' => now()->addMinutes(self::CHALLENGE_TTL_MINUTES),
            ]);

            AuditLog::create([
                'user_id' => $owner->id,
                'action' => 'owner_mfa_code_requested',
                'model_type' => User::class,
                'model_id' => $owner->id,
                'details' => 'Owner/Admin requested a one-time MFA code for security verification.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $record;
        });

        try {
            Mail::to($owner->email)->send(new SecurityMfaCode($code, 'session recovery'));
        } catch (Throwable $exception) {
            $challenge->delete();
            Log::error('Owner MFA email delivery failed.', [
                'exception' => $exception::class,
            ]);

            throw ValidationException::withMessages([
                'mfa_code' => ['Unable to send MFA verification code via email. Use your Authenticator app or check mail settings.'],
            ]);
        }
    }

    /**
     * Verify Owner/Admin current password AND a 6-digit MFA code (Authenticator TOTP or Email OTP).
     */
    public function verifyPasswordAndMfa(User $owner, string $currentPassword, string $mfaCode, string $purpose = 'session_recovery'): void
    {
        $rateKey = "owner-mfa-verify:{$owner->id}:{$purpose}";
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'mfa_code' => ["Too many failed security verification attempts. Try again in {$seconds} seconds."],
            ]);
        }

        if (!Hash::check($currentPassword, $owner->getAuthPassword())) {
            RateLimiter::hit($rateKey, 300);
            throw ValidationException::withMessages([
                'current_password' => ['The Owner/Admin password entered is incorrect.'],
            ]);
        }

        $normalizedCode = preg_replace('/\s+/', '', $mfaCode) ?? '';
        if (!preg_match('/^\d{6}$/', $normalizedCode)) {
            RateLimiter::hit($rateKey, 300);
            throw ValidationException::withMessages([
                'mfa_code' => ['Enter a valid 6-digit MFA verification code.'],
            ]);
        }

        if ($owner->mfa_enabled && !empty($owner->mfa_secret) && $this->totp->verifyCode((string) $owner->mfa_secret, $normalizedCode)) {
            RateLimiter::clear($rateKey);

            return;
        }

        $consumedChallenge = DB::transaction(function () use ($owner, $purpose, $normalizedCode): bool {
            $challenge = OwnerMfaChallenge::where('user_id', $owner->id)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$challenge || !Hash::check($normalizedCode, $challenge->code_hash)) {
                return false;
            }

            $challenge->forceFill(['used_at' => now()])->save();

            return true;
        });

        if (!$consumedChallenge) {
            RateLimiter::hit($rateKey, 300);
            throw ValidationException::withMessages([
                'mfa_code' => ['The 6-digit MFA code is invalid or expired. Request a new email security code or enter your Authenticator code.'],
            ]);
        }

        RateLimiter::clear($rateKey);
    }

    /**
     * Require Owner/Admin password re-verification, plus 6-digit MFA when Authenticator MFA is configured.
     */
    public function verifySensitiveAccess(
        User $owner,
        string $currentPassword,
        ?string $mfaCode = null,
        string $purpose = 'staff_access_management'
    ): void {
        if ($owner->mfa_enabled && !empty($owner->mfa_secret)) {
            $this->verifyPasswordAndMfa($owner, $currentPassword, (string) $mfaCode, $purpose);

            return;
        }

        $rateKey = "owner-sensitive-verify:{$owner->id}:{$purpose}";
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'current_password' => ["Too many failed security verification attempts. Try again in {$seconds} seconds."],
            ]);
        }

        if (!Hash::check($currentPassword, $owner->getAuthPassword())) {
            RateLimiter::hit($rateKey, 300);
            throw ValidationException::withMessages([
                'current_password' => ['The Owner/Admin password entered is incorrect.'],
            ]);
        }

        RateLimiter::clear($rateKey);
    }
}

