<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'customer_name',
        'phone',
        'email',
        'product_variant_id',
        'quantity',
        'note',
        'receive_promotions',
        'status',
    ];

    // Quan hệ với bảng Users
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ với bảng ProductVariants
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
