<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $group): mixed
    {
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (!PermissionService::allows($user, $group)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Permission denied for this operation.',
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

