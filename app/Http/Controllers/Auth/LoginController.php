<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const USERNAME_MAX_ATTEMPTS = 5;

    private const IP_MAX_ATTEMPTS = 30;

    private const DECAY_SECONDS = 900;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $username = trim($request->input('username'));
        $usernameKey = $this->usernameRateLimitKey($username);
        $ipKey = 'login:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($usernameKey, self::USERNAME_MAX_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipKey, self::IP_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $authenticated = Auth::attempt([
            'username' => $username,
            'password' => $request->input('password'),
        ], $request->boolean('remember'));

        if (!$authenticated || !Auth::user()->is_active) {
            if ($authenticated) {
                Auth::logout();
            }

            RateLimiter::hit($usernameKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'username' => ['These credentials do not match our records.'],
            ]);
        }

        RateLimiter::clear($usernameKey);
        RateLimiter::clear($ipKey);
        $request->session()->regenerate();

        Auth::user()->update(['last_login_at' => now()]);

        return redirect()->intended($this->redirectTo());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function validateLogin(Request $request): void
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);
    }

    private function usernameRateLimitKey(string $username): string
    {
        return 'login:username:' . hash('sha256', mb_strtolower(trim($username)));
    }

    protected function redirectTo(): string
    {
        if (Auth::user()?->role?->name === 'owner') {
            return route('owner.dashboard');
        }

        return route('staff.dashboard');
    }
}
