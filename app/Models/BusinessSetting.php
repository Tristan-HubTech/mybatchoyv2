<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
}
