<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'product_id',
        'url',
        'is_thumbnail',
    ];

    public $timestamps = true;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
