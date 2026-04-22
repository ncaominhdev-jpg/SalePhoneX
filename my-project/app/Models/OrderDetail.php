<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }


    // Accessor để lấy giá sản phẩm
    public function getPriceAttribute()
    {
        if ($this->productVariant) {
            return $this->productVariant->price;
        } elseif ($this->product) {
            return $this->product->price;
        }
        return 0;
    }

    // Accessor để tính subtotal
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    // Accessor để lấy tên sản phẩm
    public function getProductNameAttribute()
    {
        if ($this->productVariant && $this->productVariant->product) {
            $name = $this->productVariant->product->name;

            // Thêm thông tin variant nếu có
            $variantInfo = [];
            if ($this->productVariant->color) $variantInfo[] = $this->productVariant->color;
            if ($this->productVariant->size) $variantInfo[] = $this->productVariant->size;

            if (!empty($variantInfo)) {
                $name .= ' (' . implode(' - ', $variantInfo) . ')';
            }

            return $name;
        } elseif ($this->product) {
            return $this->product->name;
        }

        return 'Sản phẩm đã xóa';
    }
}
