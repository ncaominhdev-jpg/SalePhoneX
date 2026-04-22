<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashDealItem extends Model
{
    protected $fillable = [
        'flash_deal_id',
        'product_id',
        'product_variant_id', // thêm vào đây
        'stock_quota',
        'sold',
        'price_sale',
        'price_list',
        'badges',
        'note'
    ];

    protected $casts = ['badges' => 'array'];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(FlashDeal::class, 'flash_deal_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
