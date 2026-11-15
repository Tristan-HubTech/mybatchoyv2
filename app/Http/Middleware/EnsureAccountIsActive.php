<?php

namespace App\Http\Middleware;

use App\Services\ActiveSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountIsActive
{
    public function __construct(private readonly ActiveSessionManager $sessions)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            $this->sessions->invalidateAllForUser($user);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This account is inactive. Contact the owner for assistance.',
                ], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'This account is inactive. Contact the owner for assistance.',
            ]);
        }

        if ($user && ($request->expectsJson() || $request->is('api/*'))) {
            $validation = $this->sessions->validateApiRequest($user);
            if (!$validation['valid']) {
                return response()->json([
                    'message' => $validation['message'] ?? 'Session expired. Please sign in again.',
                ], 401);
            }
        } elseif ($user && !$this->sessions->validateWebRequest($request, $user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Your session has expired or was ended. Please sign in again.',
            ]);
        }

        return $next($request);
    }
}