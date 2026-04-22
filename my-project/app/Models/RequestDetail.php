<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'product_variant_id',
        'quantity',
        'approved_quantity',
        'status', // Trạng thái của từng chi tiết (nếu cần)
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}