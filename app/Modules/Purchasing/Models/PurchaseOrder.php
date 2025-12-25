<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'supplier_id',
        'purchase_number',
        'status',
        'ordered_at',
        'expected_delivery_date',
        'received_at',
        'notes',
        'created_by',
        'received_by',
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'expected_delivery_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Calculate total amount of the purchase order
     */
    public function getTotalAttribute()
    {
        return $this->items->sum('subtotal');
    }

    protected static function newFactory()
    {
        return \Database\Factories\PurchaseOrderFactory::new();
    }
}
