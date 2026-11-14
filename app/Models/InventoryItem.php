<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

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
