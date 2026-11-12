<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class ForgotPasswordController extends Controller
{
    private const RESET_CODE_MINUTES = 10;

    private const RATE_LIMIT_SECONDS = 900;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $ipKey = 'password-reset-request:ip:' . $request->ip();
        $accountKey = $this->accountRateLimitKey('password-reset-request', $email);
        $response = $this->genericResetResponse();

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($accountKey, 3)) {
            return $response;
        }

        RateLimiter::hit($ipKey, self::RATE_LIMIT_SECONDS);
        RateLimiter::hit($accountKey, self::RATE_LIMIT_SECONDS);

        $user = User::where('email', $email)->where('is_active', true)->first();
        if (!$user) {
            return $response;
        }

        $transport = config('mail.mailers.' . config('mail.default') . '.transport');
        if (in_array($transport, ['log', 'failover', 'array'], true)) {
            Log::warning('Password recovery email not sent because the configured mail transport may log message contents.');

            return $response;
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
                'details' => 'A one-time password reset code was requested.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        try {
            Mail::to($user->email)->send(new PasswordResetCode($code));
            session(['password_reset_email' => $user->email]);
        } catch (Throwable $exception) {
            DB::table('password_resets')
                ->where('email', $user->email)
                ->where('token', $tokenHash)
                ->delete();
            Log::error('Password reset email delivery failed.', [
                'exception' => $exception::class,
            ]);
        }

        return $response;
    }

    public function showResetForm()
    {
        $email = session('password_reset_email', '');

        return view('auth.reset-password', compact('email'));
    }

    public function resetPassword(Request $request)
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
        $accountKey = $this->accountRateLimitKey('password-reset-verify', $email);

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
            RateLimiter::hit($ipKey, self::RATE_LIMIT_SECONDS);
            RateLimiter::hit($accountKey, self::RATE_LIMIT_SECONDS);

            throw ValidationException::withMessages([
                'code' => ['The code is invalid or expired. Request a new code and try again.'],
            ]);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($accountKey);
        session()->forget('password_reset_email');

        return redirect()->route('login')->with(
            'success',
            'Password reset successful. You can now sign in with your new password.'
        );
    }

    private function genericResetResponse()
    {
        return redirect()->route('password.reset')->with(
            'status',
            'If an active account matches that email address, recovery instructions will be sent when email delivery is configured.'
        );
    }

    private function accountRateLimitKey(string $operation, string $email): string
    {
        return $operation . ':account:' . hash('sha256', $email);
    }
}
