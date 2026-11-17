<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InventoryItem extends Model
{
    use HasFactory;

    public static function generateUniqueSku(string $name): string
    {
        $base = strtoupper(Str::slug($name));
        if ($base === '') {
            $base = 'ITEM';
        }

        do {
            $sku = $base . '-' . random_int(100, 999);
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Validate catalog metadata while keeping active category/unit checks intact
     * and preventing direct stock overwrites during item edits.
     */
    public static function validateItemRequest(Request $request, ?self $existing = null): array
    {
        $rawSku = trim((string) $request->input('sku', ''));
        $request->merge(['sku' => $rawSku === '' ? null : $rawSku]);

        $categoryRule = Rule::exists('inventory_categories', 'id')->where(function ($query) use ($existing) {
            if ($existing) {
                $query->where(fn ($sub) => $sub->where('is_active', true)->orWhere('id', $existing->category_id));
            } else {
                $query->where('is_active', true);
            }
        });

        $unitRule = Rule::exists('units', 'id')->where(function ($query) use ($existing) {
            if ($existing) {
                $query->where(fn ($sub) => $sub->where('is_active', true)->orWhere('id', $existing->unit_id));
            } else {
                $query->where('is_active', true);
            }
        });

        $rules = [
            'name' => 'required|string|max:255',
            'category_id' => ['required', 'integer', $categoryRule],
            'unit_id' => ['required', 'integer', $unitRule],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('inventory_items', 'sku')->ignore($existing?->id)],
            'description' => 'nullable|string|max:2000',
            'minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'reorder_level' => 'nullable|numeric|min:0|max:999999999.999',
        ];

        if ($existing === null) {
            $rules['current_quantity'] = 'required|numeric|min:0|max:999999999.999';
        }

        $validated = $request->validate($rules);

        if ($existing === null) {
            $validated['sku'] = $validated['sku'] ?? static::generateUniqueSku($validated['name']);
            $validated['reorder_level'] = $validated['reorder_level'] ?? 0;
            $validated['is_active'] = true;
        } else {
            // Never overwrite live stock via item editing; blank SKUs keep existing value.
            unset($validated['current_quantity']);
            $validated['sku'] = $validated['sku'] ?? $existing->sku;
            $validated['reorder_level'] = $validated['reorder_level'] ?? $existing->reorder_level ?? 0;
        }

        return $validated;
    }

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\StockNotificationService::clearStockCache());
        static::deleted(fn () => \App\Services\StockNotificationService::clearStockCache());
    }

    public static function cachedUrgentAlertCount(): int
    {
        $query = fn (): int => static::where('is_active', true)
            ->where(function ($q) {
                $q->where('current_quantity', '<=', 0)
                  ->orWhereColumn('current_quantity', '<=', 'minimum_stock_level');
            })
            ->count();

        if (app()->runningUnitTests()) {
            return $query();
        }

        return (int) Cache::remember('inventory.urgent_alert_count', 30, $query);
    }

    protected $fillable = [
        'category_id',
        'unit_id',
        'name',
        'sku',
        'description',
        'current_quantity',
        'minimum_stock_level',
        'reorder_level',
        'is_active',
        'last_counted_at',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:3',
        'minimum_stock_level' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'last_counted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getStatusAttribute(): string
    {
        if ($this->current_quantity == 0) {
            return 'Out of Stock';
        }

        if ($this->current_quantity <= $this->minimum_stock_level) {
            return 'Low Stock';
        }

        return 'Available';
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->current_quantity <= 0;
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_quantity > 0
            && (float) $this->current_quantity <= (float) $this->minimum_stock_level;
    }
}
