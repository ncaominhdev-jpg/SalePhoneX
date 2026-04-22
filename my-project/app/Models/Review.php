<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    // Nếu bảng của bạn là product_reviews (đúng như screenshot)
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id', // 👈 thêm để lưu theo biến thể
        'order_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'user_id'            => 'integer',
        'product_id'         => 'integer',
        'product_variant_id' => 'integer',
        'order_id'           => 'integer',
        'rating'             => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
