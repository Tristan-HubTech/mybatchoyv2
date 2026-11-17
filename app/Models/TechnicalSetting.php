<?php

namespace App\Models;

use App\Services\ActiveSessionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TechnicalSetting extends Model
{
    private const CACHE_KEY = 'technical_settings.current';

    protected $fillable = [
        'maintenance_mode',
        'maintenance_notice',
        'log_retention_days',
        'security_lockout_attempts',
        'security_lockout_minutes',
        'api_rate_limit_per_minute',
        'backup_retention_days',
        'owner_can_edit_technical',
        'updated_by',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'log_retention_days' => 'integer',
        'security_lockout_attempts' => 'integer',
        'security_lockout_minutes' => 'integer',
        'api_rate_limit_per_minute' => 'integer',
        'backup_retention_days' => 'integer',
        'owner_can_edit_technical' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    public static function cachedCurrent(): ?self
    {
        try {
            if (!ActiveSessionManager::hasTable('technical_settings')) {
                return null;
            }

            if (app()->runningUnitTests()) {
                return static::find(1);
            }

            return Cache::remember(self::CACHE_KEY, 30, fn () => static::find(1));
        } catch (Throwable) {
            return null;
        }
    }

    public static function cachedApiRateLimitPerMinute(): int
    {
        $configured = static::cachedCurrent()?->api_rate_limit_per_minute;
        if ($configured !== null) {
            return max(20, min(300, (int) $configured));
        }

        return 60;
    }

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'maintenance_mode' => false,
                'maintenance_notice' => null,
                'log_retention_days' => 90,
                'security_lockout_attempts' => 5,
                'security_lockout_minutes' => 15,
                'api_rate_limit_per_minute' => 60,
                'backup_retention_days' => 30,
                'owner_can_edit_technical' => false,
            ]
        );
    }
}

