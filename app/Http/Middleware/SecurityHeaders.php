<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $enforceHttps = (bool) config('app.force_https', false) || app()->environment('production');

        if ($enforceHttps && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }
        $response = $next($request);

        if (method_exists($response, 'headers') || isset($response->headers)) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $response->headers->set(
                'Content-Security-Policy',
                implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                    "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
                    "img-src 'self' data: blob: https:",
                    "connect-src 'self'",
                    "frame-src 'self' https://www.google.com",
                    "frame-ancestors 'self'",
                    "object-src 'none'",
                    "base-uri 'self'",
                    "form-action 'self'",
                ])
            );

            if ($enforceHttps || $request->secure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
