<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason',
        'notes',
        'created_by',
    ];

    public function movements()
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
