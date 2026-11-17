<?php

namespace App\Models;

use App\Services\StockNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $transaction): void {
            if ($transaction->transaction_type === 'spoilage' || $transaction->getOriginal('transaction_type') === 'spoilage') {
                StockNotificationService::clearSpoilageCache($transaction->user_id ? (int) $transaction->user_id : null);
            }
        });

        static::deleted(function (self $transaction): void {
            if ($transaction->transaction_type === 'spoilage') {
                StockNotificationService::clearSpoilageCache($transaction->user_id ? (int) $transaction->user_id : null);
            }
        });
    }

    protected $fillable = [
        'inventory_item_id',
        'user_id',
        'supplier_id',
        'transaction_type',
        'quantity',
        'previous_quantity',
        'updated_quantity',
        'reason',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'quantity' => 'decimal:3',
        'previous_quantity' => 'decimal:3',
        'updated_quantity' => 'decimal:3',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
