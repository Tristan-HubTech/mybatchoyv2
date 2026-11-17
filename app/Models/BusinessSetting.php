<?php

namespace App\Models;

use App\Services\PersistentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'phone',
        'email',
        'address',
        'logo_path',
        'session_timeout_minutes',
        'default_minimum_stock_level',
        'default_reorder_level',
    ];

    protected $casts = [
        'session_timeout_minutes' => 'integer',
        'default_minimum_stock_level' => 'decimal:3',
        'default_reorder_level' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('business_settings.current');
        });

        static::deleted(function (): void {
            Cache::forget('business_settings.current');
        });
    }

    public static function current(): ?self
    {
        if (app()->runningUnitTests()) {
            return static::find(1);
        }

        return Cache::remember('business_settings.current', 60, fn () => static::find(1));
    }

    public static function cachedSessionTimeoutMinutes(): int
    {
        $configured = static::current()?->session_timeout_minutes;
        if ($configured !== null) {
            return max(5, min(240, (int) $configured));
        }

        return max(5, min(240, (int) config('session.lifetime', 20)));
    }

    public function resolveLogoUrl(): ?string
    {
        $path = trim((string) $this->logo_path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $diskName = PersistentStorage::mediaDisk();
        $relativePath = ltrim($path, '/');

        if ($diskName === 'public' && is_file(public_path('storage/' . $relativePath))) {
            return asset('storage/' . $relativePath);
        }

        if (!PersistentStorage::isLocalDisk($diskName) && filled(config("filesystems.disks.{$diskName}.url"))) {
            try {
                return Storage::disk($diskName)->url($relativePath);
            } catch (Throwable) {
                return route('media.logo');
            }
        }

        return route('media.logo');
    }
}
