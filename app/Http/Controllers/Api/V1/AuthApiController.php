<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\ActiveSessionManager;
use App\Services\MailTransportGuard;
use App\Services\PermissionService;
use App\Services\TechnicalAuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthApiController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const MAX_IP_ATTEMPTS = 30;
    private const DECAY_SECONDS = 900;
    private const RESET_CODE_MINUTES = 10;

    public function login(Request $request, ActiveSessionManager $sessions): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $username = mb_strtolower(trim($credentials['username']));
        $ipKey = 'login-ip:' . $request->ip();
        $accountKey = 'login:' . $username;

        $maxAttempts = self::MAX_LOGIN_ATTEMPTS;
        $decaySeconds = self::DECAY_SECONDS;
        if (ActiveSessionManager::hasTable('technical_settings')) {
            $tech = \App\Models\TechnicalSetting::current();
            $maxAttempts = max(3, (int) $tech->security_lockout_attempts);
            $decaySeconds = max(60, (int) $tech->security_lockout_minutes * 60);
        }

        if (
            RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)
            || RateLimiter::tooManyAttempts($accountKey, $maxAttempts)
        ) {
            $seconds = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($accountKey));
            $this->recordAuthEvent($request, null, 'login_rate_limited', 'API login throttled after repeated failed attempts.');

            throw ValidationException::withMessages([
                'username' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $user = User::with(['role', 'supplier'])
            ->whereRaw('LOWER(username) = ?', [$username])
            ->first();

        $supplierBlocked = $user && $user->isSupplier() && (
            !$user->supplier_id
            || !$user->supplier()->where('is_active', true)->exists()
        );

        if (!$user || !Hash::check($credentials['password'], $user->password) || !$user->is_active || $supplierBlocked) {
            RateLimiter::hit($ipKey, $decaySeconds);
            RateLimiter::hit($accountKey, $decaySeconds);
            $this->recordAuthEvent($request, $user, 'login_failed', 'Failed mobile API login attempt.');

            throw ValidationException::withMessages([
                'username' => ['The provided credentials do not match an active account.'],
            ]);
        }

        $deviceName = trim((string) ($credentials['device_name'] ?? 'MyBatchoy Mobile'));
        $claimed = $sessions->claimForApi(
            $user,
            $request,
            $deviceName,
            fn ($expiresAt) => $user->createToken($deviceName !== '' ? $deviceName : 'MyBatchoy Mobile', ['*'], $expiresAt)
        );

        RateLimiter::clear($ipKey);
        RateLimiter::clear($accountKey);

        $user->forceFill(['last_login_at' => now()])->save();
        $this->recordAuthEvent($request, $user, 'login', 'User signed in via mobile application.');

        return response()->json([
            'message' => 'Authenticated successfully.',
            'token' => $claimed['token']->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $claimed['expires_at']->toIso8601String(),
            'session_timeout_minutes' => $claimed['timeout_minutes'],
            'user' => self::formatUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');
        $settings = ActiveSessionManager::hasTable('business_settings') ? BusinessSetting::current() : null;

        return response()->json([
            'user' => self::formatUser($user),
            'business' => [
                'business_name' => $settings?->business_name ?? config('app.name', 'MyBatchoy'),
                'session_timeout_minutes' => $this->resolveSessionTimeoutMinutes(),
                'default_minimum_stock_level' => (float) ($settings?->default_minimum_stock_level ?? 0),
                'default_reorder_level' => (float) ($settings?->default_reorder_level ?? 0),
            ],
        ]);
    }

    public function logout(Request $request, ActiveSessionManager $sessions): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $sessions->releaseForApi($user);
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            } else {
                $user->tokens()->delete();
            }

            $this->recordAuthEvent($request, $user, 'logout', 'User signed out from mobile application.');
        }

        return response()->json([
            'message' => 'Signed out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $ipKey = 'password-reset-request:ip:' . $request->ip();
        $accountKey = 'password-reset-request:account:' . hash('sha256', $email);
        $genericMessage = 'If an active account matches that email address, a 6-digit recovery code will be sent.';

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($accountKey, 3)) {
            return response()->json(['message' => $genericMessage]);
        }

        RateLimiter::hit($ipKey, self::DECAY_SECONDS);
        RateLimiter::hit($accountKey, self::DECAY_SECONDS);

        $user = User::where('email', $email)->where('is_active', true)->first();
        if (!$user) {
            return response()->json(['message' => $genericMessage]);
        }

        if (!MailTransportGuard::isSafeForOtp()) {
            Log::warning('Password recovery email not sent because the configured mail transport does not support secure live delivery.');

            return response()->json(['message' => $genericMessage]);
        }

        $code = (string) random_int(100000, 999999);
        $tokenHash = Hash::make($code);

        DB::transaction(function () use ($user, $tokenHash, $request): void {
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => $tokenHash,
                    'created_at' => now(),
                ]
            );

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password_reset_code_requested',
                'model_type' => User::class,
                'model_id' => $user->id,
                'details' => 'A one-time password reset code was requested via mobile API.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        try {
            Mail::to($user->email)->send(new PasswordResetCode($code));
        } catch (Throwable $exception) {
            DB::table('password_resets')
                ->where('email', $user->email)
                ->where('token', $tokenHash)
                ->delete();
            Log::error('Password reset email delivery failed.', [
                'exception' => $exception::class,
            ]);
        }

        return response()->json(['message' => $genericMessage]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'code' => 'required|digits:6',
            'password' => [
                'required',
                'string',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $ipKey = 'password-reset-verify:ip:' . $request->ip();
        $accountKey = 'password-reset-verify:account:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($accountKey, 5)) {
            throw ValidationException::withMessages([
                'code' => ['Too many verification attempts. Request a new code and try again later.'],
            ]);
        }

        $code = trim($validated['code']);

        $user = DB::transaction(function () use ($email, $code, $validated, $request): ?User {
            $record = DB::table('password_resets')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (!$record) {
                return null;
            }

            if (Carbon::parse($record->created_at)->addMinutes(self::RESET_CODE_MINUTES)->isPast()) {
                DB::table('password_resets')->where('email', $email)->delete();

                return null;
            }

            if (!Hash::check($code, $record->token)) {
                return null;
            }

            $user = User::where('email', $email)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                DB::table('password_resets')->where('email', $email)->delete();

                return null;
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')->where('email', $email)->delete();
            app(ActiveSessionManager::class)->invalidateAllForUser($user);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password_reset_completed',
                'model_type' => User::class,
                'model_id' => $user->id,
                'details' => 'Password successfully reset using a one-time email code.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $user;
        });

        if (!$user) {
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);
            RateLimiter::hit($accountKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'code' => ['The code is invalid or expired. Request a new code and try again.'],
            ]);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($accountKey);

        return response()->json([
            'message' => 'Password reset successful. You can now sign in with your new password.',
        ]);
    }

    public static function formatUser(User $user): array
    {
        $user->loadMissing(['role', 'supplier']);
        $roleName = $user->role?->name ?? 'staff';

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $roleName,
            'role_label' => $user->role?->label ?? ucfirst($roleName),
            'supplier_id' => $user->supplier_id,
            'supplier_name' => $user->supplier?->name,
            'permission_groups' => PermissionService::permissionGroupsForRole($roleName),
            'is_active' => (bool) $user->is_active,
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function resolveSessionTimeoutMinutes(): int
    {
        if (!ActiveSessionManager::hasTable('business_settings')) {
            return 60;
        }

        $configured = (int) (BusinessSetting::current()?->session_timeout_minutes ?? 60);

        return max(5, min(240, $configured));
    }

    private function recordAuthEvent(Request $request, ?User $user, string $action, string $details): void
    {
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => User::class,
            'model_id' => $user?->id,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        TechnicalAuditLogger::record(
            $request,
            $user?->id,
            'security',
            $action,
            $details,
            User::class,
            $user?->id
        );
    }
}
