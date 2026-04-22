<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditReport extends Model
{
    protected $fillable = [
        'audit_id',
        'product_variant_id',
        'actual_quantity',
        'recorded_quantity',
        'difference',
        'is_balanced',
    ];

    protected static function booted()
    {
        static::creating(function ($report) {
            $report->difference = $report->actual_quantity - $report->recorded_quantity;
        });
    }

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}