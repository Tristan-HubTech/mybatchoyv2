<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\ActiveSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        $businessSettings = ActiveSessionManager::hasTable('business_settings') ? BusinessSetting::current() : null;

        return view('auth.login', [
            'businessSettings' => $businessSettings,
        ]);
    }

    public function login(Request $request, ActiveSessionManager $sessions)
    {
        $this->validateLogin($request);

        $identifier = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');
        $usernameKey = $this->usernameRateLimitKey($identifier);
        $ipKey = 'login:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($usernameKey, self::USERNAME_MAX_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipKey, self::IP_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $user = $this->resolveAuthenticatedUser($identifier, $password);

        if (!$user || !$user->is_active) {
            RateLimiter::hit($usernameKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'username' => ['These credentials do not match our records.'],
            ]);
        }

        $sessions->claimForWeb($user, $request, function () use ($user, $remember, $request): void {
            Auth::guard('web')->login($user, $remember);
            $request->session()->regenerate();
        });

        RateLimiter::clear($usernameKey);
        RateLimiter::clear($ipKey);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended($this->redirectTo());
    }

    public function logout(Request $request, ActiveSessionManager $sessions)
    {
        $user = $request->user() ?? Auth::guard('web')->user();
        if ($user) {
            $sessions->releaseForWeb($user, $request);
        }

        Auth::guard('web')->logout();

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

    private function resolveAuthenticatedUser(string $identifier, string $password): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false) {
            $emailUser = User::with('role')
                ->where('email', mb_strtolower($identifier))
                ->first();

            if ($emailUser && Hash::check($password, $emailUser->getAuthPassword())) {
                return $emailUser;
            }
        }

        $usernameUser = User::with('role')
            ->where('username', $identifier)
            ->first();

        if ($usernameUser && Hash::check($password, $usernameUser->getAuthPassword())) {
            return $usernameUser;
        }

        return null;
    }

    private function usernameRateLimitKey(string $username): string
    {
        return 'login:username:' . hash('sha256', mb_strtolower(trim($username)));
    }

    protected function redirectTo(): string
    {
        if (Auth::guard('web')->user()?->role?->name === 'owner') {
            return route('owner.dashboard');
        }

        return route('staff.dashboard');
    }
}
