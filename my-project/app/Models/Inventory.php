<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'quantity'
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'warehouse_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Kiểm tra tồn kho thấp (giả sử min_quantity = 10)
    public function isLowStock(int $minQuantity = 10): bool
    {
        return $this->quantity <= $minQuantity;
    }

    // Kiểm tra hết hàng
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
}
