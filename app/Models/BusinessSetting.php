<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}