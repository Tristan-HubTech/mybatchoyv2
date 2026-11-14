<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $user->tokens()->delete();

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
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $timeout = 60;
                if (Schema::hasTable('business_settings')) {
                    $configured = (int) (BusinessSetting::current()?->session_timeout_minutes ?? 60);
                    $timeout = max(5, min(240, $configured));
                }

                if ($token->expires_at && $token->expires_at->isPast()) {
                    $token->delete();

                    return response()->json([
                        'message' => 'Session expired. Please sign in again.',
                    ], 401);
                }

                $token->forceFill([
                    'expires_at' => now()->addMinutes($timeout),
                ])->save();
            }
        }

        return $next($request);
    }
}