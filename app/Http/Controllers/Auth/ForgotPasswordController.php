<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
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
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $ip = $request->ip();
        $rateKey = 'password-reset-request:' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'identifier' => ["Too many code requests from this device. Please try again in {$seconds} seconds."],
            ]);
        }
        RateLimiter::hit($rateKey, 900); // 15-minute window

        $identifier = trim($request->input('identifier'));

        $user = User::where('is_active', true)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                  ->orWhere('username', $identifier);
            })
            ->first();

        if ($user) {
            // Cryptographically secure 6-digit code
            $code = (string) random_int(100000, 999999);

            // Store hashed code in database (never plain text)
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            // Audit log the security code request
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password_reset_code_requested',
                'model_type' => User::class,
                'model_id' => $user->id,
                'details' => 'Secure 6-digit password reset verification code generated.',
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
            ]);

            Log::info("Password reset code for user [{$user->username} / {$user->email}]: {$code}");

            session(['password_reset_email' => $user->email]);

            // In local/testing development, provide the code directly for testing convenience
            if (app()->environment('local', 'testing')) {
                session()->flash('security_code_demo', $code);
            }
        }

        return redirect()->route('password.reset')->with(
            'status',
            'If an active account is associated with this username or email, a 6-digit security code has been generated. The code expires in 15 minutes.'
        );
    }

    public function showResetForm(Request $request)
    {
        $email = $request->query('email', session('password_reset_email', ''));
        return view('auth.reset-password', compact('email'));
    }

    public function resetPassword(Request $request)
    {
        $ip = $request->ip();
        $rateKey = 'password-reset-verify:' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'code' => ["Too many failed verification attempts. Please wait {$seconds} seconds."],
            ]);
        }

        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        $email = trim($request->input('email'));
        $code = trim($request->input('code'));

        $record = DB::table('password_resets')->where('email', $email)->first();

        if (!$record) {
            RateLimiter::hit($rateKey, 900);
            throw ValidationException::withMessages([
                'code' => ['No active reset code found for this email. Please request a new code.'],
            ]);
        }

        // Expire after 15 minutes
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_resets')->where('email', $email)->delete();
            RateLimiter::hit($rateKey, 900);
            throw ValidationException::withMessages([
                'code' => ['This security code has expired (valid for 15 minutes). Please request a new one.'],
            ]);
        }

        // Verify cryptographically hashed code
        if (!Hash::check($code, $record->token)) {
            RateLimiter::hit($rateKey, 900);
            throw ValidationException::withMessages([
                'code' => ['Invalid 6-digit security code. Please check and try again.'],
            ]);
        }

        $user = User::where('email', $email)->where('is_active', true)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No active account found for this email address.'],
            ]);
        }

        // Update password with secure bcrypt hash
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Invalidate the code immediately (one-time use)
        DB::table('password_resets')->where('email', $email)->delete();
        RateLimiter::clear($rateKey);
        session()->forget('password_reset_email');

        // Audit the password reset completion
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset_completed',
            'model_type' => User::class,
            'model_id' => $user->id,
            'details' => 'Password successfully reset using 6-digit verification code.',
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('login')->with(
            'success',
            'Password reset successful! You can now sign in with your new password.'
        );
    }
}
