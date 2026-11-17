<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'created_by',
        'purchase_number',
        'purchase_date',
        'total_amount',
        'notes',
        'fulfillment_mode',
        'supplier_status',
        'delivery_status',
        'invoice_number',
        'delivery_notes',
        'supplier_responded_at',
        'delivered_at',
        'received_in_inventory',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'supplier_responded_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_in_inventory' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Scope queries to realized purchases received in inventory and not rejected.
     */
    public function scopeRealized($query)
    {
        return $query->where('received_in_inventory', true)
            ->where('supplier_status', '!=', 'rejected');
    }
}
