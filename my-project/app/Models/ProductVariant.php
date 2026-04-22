<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;   // ✅ THÊM: để kiểm tra tồn tại bảng

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'discount',
        'img',
        'status',
    ];

    protected $casts = [
        'price'    => 'float',
        'discount' => 'float',
        'status'   => 'boolean',
    ];

    // ✅ THÊM: auto-append các accessor dùng trong Infolist
    protected $appends = [
        'display_name',
        'options_string',
        'price_final',
        'image_url',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function auditReports()
    {
        return $this->hasMany(\App\Models\AuditReport::class, 'product_variant_id');
    }

    /**
     * Accessor phục vụ hiển thị trong dropdown
     * Ví dụ: "Đen 8GB/128GB" hoặc fallback "Biến thể #ID" nếu chưa có name.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) ($this->name ?? ''));
        return $name !== '' ? $name : ('Biến thể #' . $this->id);
    }

    // GIỮ NGUYÊN quan hệ bạn đã khai báo
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values')
            ->with('attribute'); // AttributeValue belongsTo Attribute
    }

    /**
     * ✅ Accessor: Chuỗi thuộc tính "Màu: Đen • RAM: 8GB ..."
     * — An toàn: nếu CHƯA có bảng pivot => KHÔNG query, fallback về 'name'
     */
    public function getOptionsStringAttribute(): ?string
    {
        // Chưa tạo bảng pivot => tránh query gây lỗi 1146
        if (! Schema::hasTable('product_variant_attribute_values')) {
            return filled($this->name) ? $this->name : null;
        }

        try {
            if (! $this->relationLoaded('attributeValues')) {
                $this->loadMissing('attributeValues.attribute');
            }

            $pairs = collect($this->attributeValues ?? [])
                ->map(function ($av) {
                    $attrName = data_get($av, 'attribute.name') ?? 'Thuộc tính';
                    $val      = data_get($av, 'value') ?? data_get($av, 'name');
                    return trim($attrName . ': ' . ($val ?? '—'));
                })
                ->filter()
                ->implode(' • ');

            if ($pairs === '' && filled($this->name)) {
                return $this->name;
            }

            return $pairs !== '' ? $pairs : null;
        } catch (\Throwable $e) {
            // Phòng trường hợp các bảng attributes/attribute_values chưa tồn tại
            return filled($this->name) ? $this->name : null;
        }
    }

    /**
     * ✅ Accessor: Giá sau giảm
     */
    public function getPriceFinalAttribute(): float
    {
        $price    = (float) ($this->price ?? 0);
        $discount = (float) ($this->discount ?? 0);
        $final    = $price - $discount;
        return $final > 0 ? $final : 0.0;
    }

    /**
     * ✅ Accessor: Chuẩn hoá field ảnh để dùng với ImageEntry
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->img ?: null;
    }
}
