<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\UserActiveSession;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class ActiveSessionManager
{
    public const ALREADY_ACTIVE_MESSAGE = 'This account is already logged in on another device. Please log out from that device before trying again.';

    public const WEB_SESSION_TOKEN_KEY = '_eb_active_session_token';

    public const WEB_SESSION_BOUND_KEY = '_eb_active_session_bound';

    public const WEB_SESSION_USER_KEY = '_eb_active_session_user_id';

    /** @var array<string, bool> */
    private static array $tableExistsMemo = [];

    public static function hasTable(string $table): bool
    {
        if (app()->runningUnitTests()) {
            return Schema::hasTable($table);
        }

        $conn = (string) config('database.default', 'mysql');
        $dbName = (string) config("database.connections.{$conn}.database", '');
        $key = "schema.exists.{$conn}.{$dbName}.{$table}";

        if (isset(self::$tableExistsMemo[$key])) {
            return true;
        }

        try {
            if (Cache::get($key) === true) {
                self::$tableExistsMemo[$key] = true;

                return true;
            }
        } catch (\Throwable) {
            // Fall back to direct schema check during pre-migration bootstrap.
        }

        $exists = Schema::hasTable($table);
        if ($exists) {
            self::$tableExistsMemo[$key] = true;
            try {
                Cache::put($key, true, 600);
            } catch (\Throwable) {
                // Non-fatal if cache store is not yet initialized.
            }
        }

        return $exists;
    }

    public function resolveTimeoutMinutes(): int
    {
        if (self::hasTable('business_settings')) {
            return BusinessSetting::cachedSessionTimeoutMinutes();
        }

        return max(5, min(240, (int) config('session.lifetime', 20)));
    }

    public function hashWebSessionToken(string $plainToken): string
    {
        return hash('sha256', 'web:' . $plainToken);
    }

    public function hashApiTokenId(int|string $tokenId): string
    {
        return hash('sha256', 'api:' . (string) $tokenId);
    }

    /**
     * Atomically claim the single active session for a web login before establishing Auth state.
     *
     * @param  callable(Carbon):void  $establishWebAuth
     */
    public function claimForWeb(User $user, Request $request, callable $establishWebAuth): void
    {
        $deviceInfo = $this->describeDevice($request);

        $this->acquireSlot($user, 'web', $deviceInfo, function (Carbon $expiresAt) use ($request, $user, $establishWebAuth): string {
            $establishWebAuth($expiresAt);

            $plainToken = Str::random(64);
            $request->session()->put(self::WEB_SESSION_BOUND_KEY, true);
            $request->session()->put(self::WEB_SESSION_USER_KEY, $user->id);
            $request->session()->put(self::WEB_SESSION_TOKEN_KEY, $plainToken);

            return $this->hashWebSessionToken($plainToken);
        });
    }

    /**
     * Atomically claim the single active session for a Sanctum mobile API login before issuing a token.
     *
     * @param  callable(Carbon):NewAccessToken  $issueApiToken
     * @return array{token: NewAccessToken, expires_at: Carbon, timeout_minutes: int}
     */
    public function claimForApi(User $user, Request $request, ?string $deviceName, callable $issueApiToken): array
    {
        $deviceInfo = $this->describeDevice($request, $deviceName);
        $issued = null;

        $timeoutMinutes = $this->acquireSlot($user, 'api', $deviceInfo, function (Carbon $expiresAt) use ($issueApiToken, &$issued): string {
            $issued = $issueApiToken($expiresAt);

            return $this->hashApiTokenId($issued->accessToken->id);
        });

        return [
            'token' => $issued,
            'expires_at' => $issued->accessToken->expires_at ?? now()->addMinutes($timeoutMinutes),
            'timeout_minutes' => $timeoutMinutes,
        ];
    }

    /**
     * Revalidate session ownership and expiration for authenticated web requests.
     */
    public function validateWebRequest(Request $request, User $user): bool
    {
        if (!self::hasTable('user_active_sessions')) {
            return true;
        }

        $isBound = $request->session()->get(self::WEB_SESSION_BOUND_KEY) === true;

        if (!$isBound) {
            if (Auth::viaRemember()) {
                return $this->tryClaimRememberedSession($request, $user);
            }

            return true;
        }

        if ((int) $request->session()->get(self::WEB_SESSION_USER_KEY) !== (int) $user->id) {
            return false;
        }

        $session = UserActiveSession::where('user_id', $user->id)->first();
        if (!$session) {
            return false;
        }

        if ($session->isExpired()) {
            $session->delete();

            return false;
        }

        $plainToken = (string) $request->session()->get(self::WEB_SESSION_TOKEN_KEY, '');
        if ($plainToken === '') {
            return false;
        }

        $expectedHash = $this->hashWebSessionToken($plainToken);
        if (!hash_equals($session->session_hash, $expectedHash)) {
            return false;
        }

        if (!$session->last_activity_at || $session->last_activity_at->lte(now()->subSeconds(60))) {
            $timeoutMinutes = $this->resolveTimeoutMinutes();
            $session->forceFill([
                'last_activity_at' => now(),
                'expires_at' => now()->addMinutes($timeoutMinutes),
            ])->save();
        }

        return true;
    }

    /**
     * Revalidate single-session ownership and sliding expiry for API bearer tokens.
     *
     * @return array{valid: bool, message?: string}
     */
    public function validateApiRequest(User $user): array
    {
        $token = $user->currentAccessToken();
        if (!($token instanceof PersonalAccessToken) || !$token->exists) {
            return ['valid' => true];
        }

        $timeoutMinutes = $this->resolveTimeoutMinutes();

        if ($token->expires_at && $token->expires_at->isPast()) {
            $this->releaseByHash($user->id, $this->hashApiTokenId($token->id));
            $token->delete();

            return [
                'valid' => false,
                'message' => 'Session expired. Please sign in again.',
            ];
        }

        if (self::hasTable('user_active_sessions')) {
            $expectedHash = $this->hashApiTokenId($token->id);
            $active = UserActiveSession::where('user_id', $user->id)->first();

            if ($active) {
                if ($active->isExpired()) {
                    $active->delete();
                    $token->delete();

                    return [
                        'valid' => false,
                        'message' => 'Session expired. Please sign in again.',
                    ];
                }

                if (!hash_equals($active->session_hash, $expectedHash)) {
                    return [
                        'valid' => false,
                        'message' => 'This session is no longer active. Please sign in again.',
                    ];
                }

                if (!$active->last_activity_at || $active->last_activity_at->lte(now()->subSeconds(60))) {
                    $active->forceFill([
                        'last_activity_at' => now(),
                        'expires_at' => now()->addMinutes($timeoutMinutes),
                    ])->save();
                }
            }
        }

        if (!$token->expires_at || $token->expires_at->lte(now()->addMinutes(max(1, $timeoutMinutes - 1)))) {
            $token->forceFill([
                'expires_at' => now()->addMinutes($timeoutMinutes),
            ])->save();
        }

        return ['valid' => true];
    }

    public function releaseForWeb(User $user, Request $request): void
    {
        if (!self::hasTable('user_active_sessions')) {
            return;
        }

        $plainToken = (string) $request->session()->get(self::WEB_SESSION_TOKEN_KEY, '');

        if ($request->session()->get(self::WEB_SESSION_BOUND_KEY) === true && $plainToken !== '') {
            UserActiveSession::where('user_id', $user->id)
                ->where('session_hash', $this->hashWebSessionToken($plainToken))
                ->delete();
        }
    }

    public function releaseForApi(User $user): void
    {
        if (!self::hasTable('user_active_sessions')) {
            return;
        }

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $this->releaseByHash($user->id, $this->hashApiTokenId($token->id));
        }
    }

    public function invalidateAllForUser(User $user): void
    {
        if (self::hasTable('user_active_sessions')) {
            UserActiveSession::where('user_id', $user->id)->delete();
        }

        $this->revokeObsoleteDatabaseSessions($user);

        $user->tokens()->delete();
    }

    public function invalidateOtherSessionsExceptApiToken(User $user, int|string|null $keepTokenId): void
    {
        if ($keepTokenId === null) {
            $this->invalidateAllForUser($user);

            return;
        }

        $user->tokens()->where('id', '!=', $keepTokenId)->delete();

        if (self::hasTable('user_active_sessions')) {
            UserActiveSession::where('user_id', $user->id)
                ->where('session_hash', '!=', $this->hashApiTokenId($keepTokenId))
                ->delete();
        }

        $this->revokeObsoleteDatabaseSessions($user);
    }

    public function revokeObsoleteDatabaseSessions(User $user, ?string $preserveSessionId = null): void
    {
        $sessionTable = (string) config('session.table', 'sessions');
        if (config('session.driver') !== 'database' || !self::hasTable($sessionTable)) {
            return;
        }

        $query = DB::table($sessionTable)->where('user_id', $user->id);
        if ($preserveSessionId !== null && $preserveSessionId !== '') {
            $query->where('id', '!=', $preserveSessionId);
        }

        $query->delete();
    }

    /**
     * Rotate session ownership during a self-service password change so prior session IDs/cookies stop working.
     */
    public function rotateWebSessionAfterPasswordChange(User $user, Request $request): void
    {
        $user->tokens()->delete();

        $oldPlainToken = (string) $request->session()->get(self::WEB_SESSION_TOKEN_KEY, '');
        $oldHash = $oldPlainToken !== '' ? $this->hashWebSessionToken($oldPlainToken) : '';
        $wasBound = $request->session()->get(self::WEB_SESSION_BOUND_KEY) === true;

        $request->session()->regenerate(true);
        $request->session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());
        $this->revokeObsoleteDatabaseSessions($user, $request->session()->getId());

        if (!self::hasTable('user_active_sessions')) {
            return;
        }

        $active = UserActiveSession::where('user_id', $user->id)->first();
        $matchesOld = $active && $oldHash !== '' && hash_equals($active->session_hash, $oldHash);

        if ($wasBound || $matchesOld) {
            $timeoutMinutes = $this->resolveTimeoutMinutes();
            $newPlainToken = Str::random(64);
            $request->session()->put(self::WEB_SESSION_BOUND_KEY, true);
            $request->session()->put(self::WEB_SESSION_USER_KEY, $user->id);
            $request->session()->put(self::WEB_SESSION_TOKEN_KEY, $newPlainToken);

            UserActiveSession::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'channel' => 'web',
                    'session_hash' => $this->hashWebSessionToken($newPlainToken),
                    'device_info' => $this->describeDevice($request),
                    'logged_in_at' => $active?->logged_in_at ?? now(),
                    'last_activity_at' => now(),
                    'expires_at' => now()->addMinutes($timeoutMinutes),
                ]
            );
        } else {
            UserActiveSession::where('user_id', $user->id)->delete();
        }
    }

    public function revokeByOwner(User $targetUser, User $actor, Request $request): void
    {
        if (!$actor->isOwner()) {
            abort(403, 'Only the business Owner may use Owner session recovery.');
        }

        if ($targetUser->isSystemAdmin()) {
            abort(403, 'Owners cannot view or revoke System Admin sessions.');
        }

        DB::transaction(function () use ($targetUser, $actor, $request): void {
            $this->invalidateAllForUser($targetUser);
            $targetUser->forceFill(['remember_token' => Str::random(60)])->save();

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'session_revoked',
                'model_type' => User::class,
                'model_id' => $targetUser->id,
                'details' => "Active login session revoked for account {$targetUser->username} by Owner recovery.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });
    }

    public function revokeBySystemAdmin(User $targetUser, User $actor, Request $request): void
    {
        if (!$actor->isSystemAdmin() || !$targetUser->isSystemAdmin()) {
            abort(403, 'System Admin session recovery only applies to System Admin accounts.');
        }

        DB::transaction(function () use ($targetUser, $actor, $request): void {
            $this->invalidateAllForUser($targetUser);
            $targetUser->forceFill(['remember_token' => Str::random(60)])->save();

            TechnicalAuditLogger::record(
                $request,
                $actor->id,
                'security',
                'admin_session_revoked',
                "Active login session revoked for System Admin account {$targetUser->username} via technical security controls.",
                User::class,
                $targetUser->id
            );
        });
    }

    public function pruneExpiredSessions(): int
    {
        if (!self::hasTable('user_active_sessions')) {
            return 0;
        }

        return UserActiveSession::where('expires_at', '<=', now())->delete();
    }

    public function describeDevice(Request $request, ?string $explicitDeviceName = null): string
    {
        if ($explicitDeviceName !== null) {
            $cleaned = trim(strip_tags($explicitDeviceName));
            if ($cleaned !== '') {
                return Str::limit($cleaned, 110, '');
            }
        }

        $agent = (string) $request->userAgent();
        if (trim($agent) === '') {
            return 'Web Browser';
        }

        if (str_contains($agent, 'Expo') || str_contains($agent, 'MyBatchoy') || str_contains($agent, 'okhttp') || str_contains($agent, 'CFNetwork')) {
            return 'MyBatchoy Mobile App';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'CriOS/') || str_contains($agent, 'Chrome/') => 'Google Chrome',
            str_contains($agent, 'FxiOS/') || str_contains($agent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Web Browser',
        };

        $platform = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $platform ? "{$browser} on {$platform}" : $browser;
    }

    /**
     * @param  callable(Carbon):string  $afterAcquired
     */
    private function acquireSlot(User $user, string $channel, string $deviceInfo, callable $afterAcquired): int
    {
        $timeoutMinutes = $this->resolveTimeoutMinutes();

        try {
            Cache::lock('single_active_session:user:' . $user->id, 10)->block(5, function () use ($user, $channel, $deviceInfo, $timeoutMinutes, $afterAcquired): void {
                DB::transaction(function () use ($user, $channel, $deviceInfo, $timeoutMinutes, $afterAcquired): void {
                    User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                    // Clean up stale expired sessions and expired API tokens for this account.
                    UserActiveSession::where('user_id', $user->id)
                        ->where('expires_at', '<=', now())
                        ->delete();
                    $user->tokens()->where('expires_at', '<=', now())->delete();

                    $existing = UserActiveSession::where('user_id', $user->id)->lockForUpdate()->first();
                    if ($existing !== null) {
                        throw ValidationException::withMessages([
                            'username' => [self::ALREADY_ACTIVE_MESSAGE],
                        ]);
                    }

                    $now = now();
                    $expiresAt = $now->copy()->addMinutes($timeoutMinutes);

                    try {
                        $sessionRecord = UserActiveSession::create([
                            'user_id' => $user->id,
                            'channel' => $channel,
                            'session_hash' => hash('sha256', 'claiming:' . Str::uuid()),
                            'device_info' => $deviceInfo,
                            'logged_in_at' => $now,
                            'last_activity_at' => $now,
                            'expires_at' => $expiresAt,
                        ]);
                    } catch (QueryException) {
                        throw ValidationException::withMessages([
                            'username' => [self::ALREADY_ACTIVE_MESSAGE],
                        ]);
                    }

                    $finalHash = $afterAcquired($expiresAt);
                    $sessionRecord->forceFill(['session_hash' => $finalHash])->save();
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'username' => [self::ALREADY_ACTIVE_MESSAGE],
            ]);
        }

        return $timeoutMinutes;
    }

    private function tryClaimRememberedSession(Request $request, User $user): bool
    {
        try {
            $this->acquireSlot($user, 'web', $this->describeDevice($request), function () use ($request, $user): string {
                $plainToken = Str::random(64);
                $request->session()->put(self::WEB_SESSION_BOUND_KEY, true);
                $request->session()->put(self::WEB_SESSION_USER_KEY, $user->id);
                $request->session()->put(self::WEB_SESSION_TOKEN_KEY, $plainToken);

                return $this->hashWebSessionToken($plainToken);
            });

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function releaseByHash(int $userId, string $sessionHash): void
    {
        UserActiveSession::where('user_id', $userId)
            ->where('session_hash', $sessionHash)
            ->delete();
    }
}
