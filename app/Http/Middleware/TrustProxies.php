<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * Resolve trusted proxies dynamically from configuration instead of a blind wildcard.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies(): array|string|null
    {
        $configured = trim((string) config('app.trusted_proxies', ''));
        if ($configured !== '') {
            if ($configured === '*') {
                return '*';
            }

            $list = array_values(array_filter(array_map('trim', explode(',', $configured))));

            return $list !== [] ? $list : null;
        }

        if (
            filter_var(config('app.serverless_readonly', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($_SERVER['VERCEL'] ?? $_ENV['VERCEL'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ) {
            return '*';
        }

        return null;
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
