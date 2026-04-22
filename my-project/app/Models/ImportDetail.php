<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_import_id',
        'product_variant_id',
        'quantity',
    ];

  public function import()
{
    return $this->belongsTo(Import::class, 'stock_import_id');
}

public function productVariant()
{
    return $this->belongsTo(ProductVariant::class, 'product_variant_id')->with('product');
}

}