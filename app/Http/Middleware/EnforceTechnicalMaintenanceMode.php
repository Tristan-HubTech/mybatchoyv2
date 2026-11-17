<?php

namespace App\Http\Middleware;

use App\Models\TechnicalSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceTechnicalMaintenanceMode
{
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = TechnicalSetting::cachedCurrent();
        if (!$settings || !$settings->maintenance_mode) {
            return $next($request);
        }

        if ($this->isBypassPath($request)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user && $request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
        }

        if ($user && $user->isSystemAdmin()) {
            return $next($request);
        }

        $notice = trim((string) ($settings->maintenance_notice ?? ''))
            ?: 'System maintenance is currently in progress. Please try again shortly.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $notice,
                'maintenance_mode' => true,
            ], 503);
        }

        $escapedNotice = e($notice);
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Scheduled Maintenance - MyBatchoy</title>
</head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#111111;color:#FFFFFF;font-family:system-ui,-apple-system,sans-serif;padding:24px;">
    <main style="max-width:460px;width:100%;background:#1A1A1A;border:1px solid #2E2E2E;border-radius:14px;padding:28px;text-align:center;">
        <div style="display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(255,193,7,0.14);color:#FFC107;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:12px;">
            Maintenance Mode
        </div>
        <h1 style="margin:0 0 10px;font-size:22px;color:#FFFFFF;">MyBatchoy Is Under Maintenance</h1>
        <p style="margin:0;color:#BDBDBD;font-size:14px;line-height:1.6;">{$escapedNotice}</p>
    </main>
</body>
</html>
HTML;

        return response($html, 503, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Retry-After' => '300',
        ]);
    }

    private function isBypassPath(Request $request): bool
    {
        return $request->is(
            'login',
            'logout',
            'media/business-logo',
            'sanctum/csrf-cookie',
            'api/v1/health',
            'api/v1/auth/login',
            'api/v1/auth/logout'
        );
    }
}

