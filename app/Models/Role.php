<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label'];

    protected static function booted(): void
    {
        $clear = static function (): void {
            Cache::forget('roles.name_map');
            Cache::forget('roles.all_by_id');
        };
        static::saved($clear);
        static::deleted($clear);
    }

    public static function cachedModelById(?int $roleId): ?self
    {
        if ($roleId === null) {
            return null;
        }

        if (app()->runningUnitTests()) {
            return static::find($roleId);
        }

        $roles = Cache::remember('roles.all_by_id', 300, fn () => static::all()->keyBy('id'));

        return $roles->get($roleId);
    }

    public static function cachedNameById(?int $roleId): ?string
    {
        if ($roleId === null) {
            return null;
        }

        if (app()->runningUnitTests()) {
            return static::whereKey($roleId)->value('name');
        }

        return static::cachedModelById($roleId)?->name;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
