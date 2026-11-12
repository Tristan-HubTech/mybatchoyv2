<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use Closure;
use Illuminate\Http\Request;

class ApplySessionTimeout
{
    public function handle(Request $request, Closure $next): mixed
    {
        $timeout = BusinessSetting::whereKey(1)->value('session_timeout_minutes');

        if ($timeout !== null) {
            config(['session.lifetime' => max(5, min(240, (int) $timeout))]);
        }

        return $next($request);
    }
}
