<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Category extends Model
{
    protected $fillable = [
        'name',
        'image',
        'status',
        'parent_id',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function attributes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(Attribute::class, 'attribute_category');
}
public function products()
{
    return $this->hasMany(Product::class, 'category_id');
}

public function attributeCategories()
{
    return $this->hasMany(AttributeCategory::class, 'category_id');
}


}

