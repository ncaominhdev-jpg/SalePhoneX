<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Media;

class Product extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'description',
        'category_id',
        'price',
        'brands_id',
        'status',
        'image',
    ];

    /**
     * Quan hệ: Một sản phẩm có nhiều media.
     */


    /**
     * Quan hệ: Một sản phẩm thuộc về nhiều danh mục.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Accessor: Lấy URL của ảnh đại diện.
     */
    public function getThumbnailUrlAttribute()
    {
        $media = $this->media()->where('is_thumbnail', true)->first() ?? $this->media()->first();
        return $media ? asset('storage/' . $media->url) : null;
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brands_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class, 'product_id')->with('attribute');
    }

    public function productVariants()
    {
        return $this->hasMany(\App\Models\ProductVariant::class, 'product_id');
    }
    public function media()
    {
        return $this->hasMany(Media::class, 'product_id');
    }
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_values', 'product_id', 'attribute_id')
            ->withPivot('value')       // cột giá trị
            ->withTimestamps();        // bảng của bạn có created_at/updated_at
    }
}
