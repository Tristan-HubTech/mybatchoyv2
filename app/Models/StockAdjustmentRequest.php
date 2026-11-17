<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'requested_by',
        'request_type',
        'previous_quantity',
        'counted_quantity',
        'reason',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'previous_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'reviewed_at' => 'datetime',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

