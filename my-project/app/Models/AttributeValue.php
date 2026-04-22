<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $fillable = [
         'product_id', 
        'product_variant_id',
        'attribute_id',
        'value',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

