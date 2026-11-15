<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use App\Services\ActiveSessionManager;
use Closure;
use Illuminate\Http\Request;

class ApplySessionTimeout
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (ActiveSessionManager::hasTable('business_settings')) {
            config(['session.lifetime' => BusinessSetting::cachedSessionTimeoutMinutes()]);
        }

        return $next($request);
    }
}
