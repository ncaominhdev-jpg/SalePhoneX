<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuanceRequestDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function issuanceRequest(): BelongsTo
    {
        return $this->belongsTo(IssuanceRequest::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}