<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'unit',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'attribute_category');
    }

    // ⚠️ Hàm này có vẻ không cần thiết nếu `Attribute` không thuộc về 1 Product
    // Nếu không dùng thì nên xóa để tránh hiểu lầm
    public function products()
    {
        return $this->belongsToMany(Product::class, 'attribute_values', 'attribute_id', 'product_id')
            ->withPivot('value')
            ->withTimestamps();
    }
}
