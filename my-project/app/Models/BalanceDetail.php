<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceDetail extends Model
{
    protected $fillable = [
        'balance_id',
        'product_variant_id',
        'recorded_quantity',
        'actual_quantity', 
        'adjusted_quantity',
        'created_by',
        'reason'
    ];

    protected $casts = [
        'recorded_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'adjusted_quantity' => 'integer',
    ];

    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncrease(): bool
    {
        return $this->adjusted_quantity > 0;
    }

    public function isDecrease(): bool
    {
        return $this->adjusted_quantity < 0;
    }
}