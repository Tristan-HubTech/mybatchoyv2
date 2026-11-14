<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($key === '') {
            return $next($request);
        }

        if (mb_strlen($key) > 128) {
            return response()->json([
                'message' => 'Invalid X-Idempotency-Key header length.',
            ], 422);
        }

        $userId = $request->user()?->id ?? 'guest';
        $fingerprint = hash('sha256', $request->method() . '|' . $request->path() . '|' . $request->getContent());
        $cacheKey = "idempotency:{$userId}:{$key}";

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            if (($cached['fingerprint'] ?? '') !== $fingerprint) {
                return response()->json([
                    'message' => 'Idempotency key already used with a different request payload.',
                ], 409);
            }

            return response($cached['body'], (int) $cached['status'], [
                'Content-Type' => $cached['content_type'] ?? 'application/json',
                'X-Idempotent-Replayed' => 'true',
            ]);
        }

        $lock = Cache::lock("idempotency_lock:{$userId}:{$key}", 15);
        if (!$lock->get()) {
            return response()->json([
                'message' => 'A request with this idempotency key is currently being processed.',
            ], 409);
        }

        try {
            $existingAfterLock = Cache::get($cacheKey);
            if (is_array($existingAfterLock)) {
                return response($existingAfterLock['body'], (int) $existingAfterLock['status'], [
                    'Content-Type' => $existingAfterLock['content_type'] ?? 'application/json',
                    'X-Idempotent-Replayed' => 'true',
                ]);
            }

            $response = $next($request);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'content_type' => $response->headers->get('Content-Type', 'application/json'),
                    'fingerprint' => $fingerprint,
                ], now()->addHours(24));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}

