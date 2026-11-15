<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $userRole = $user->relationLoaded('role')
            ? $user->role?->name
            : Role::cachedNameById($user->role_id);

        if (!in_array($userRole, $roles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'You are not authorized to access this resource.',
                ], 403);
            }

            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
