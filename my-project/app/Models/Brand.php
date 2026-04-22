<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'brands_id');
    }
    public function category()
{
    return $this->belongsTo(Category::class);
}
    
}
