<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_export_id',
        'product_variant_id',
        'quantity',
    ];

    public function export()
    {
        return $this->belongsTo(Export::class, 'stock_export_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}