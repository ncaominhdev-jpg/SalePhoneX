<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_warehouse_id',
        'export_type',
        'to_warehouse_id',
        'export_date',
        'created_by',
        'note',
        'processed_by',
        'final_approved_by',
        'status',
        'approved_at'
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Branch::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Branch::class, 'to_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function exportDetails()
{
    return $this->hasMany(ExportDetail::class, 'stock_export_id');
}
public function processedBy()
{
    return $this->belongsTo(User::class, 'processed_by');
}

public function finalApprovedBy()
{
    return $this->belongsTo(User::class, 'final_approved_by');
}

 protected $casts = [
        'export_date' => 'datetime',
        'approved_at' => 'datetime',
    ];
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
        ];
    }
    public function order()
{
    return $this->belongsTo(\App\Models\Order::class);
}
   protected static function booted(): void
    {
        static::creating(function ($export) {
            $branchId = $export->from_warehouse_id ?? Auth::user()->branch_id;
            $branchCode = 'CN' . str_pad($branchId, 2, '0', STR_PAD_LEFT);

            $latest = Export::where('from_warehouse_id', $branchId)->max('id') ?? 0;
            $nextNumber = str_pad($latest + 1, 4, '0', STR_PAD_LEFT);

            $export->code = '#PXK_' . $branchCode . '_' . $nextNumber;
            
            // Mặc định status khi tạo mới
            if (!isset($export->status)) {
                $export->status = self::STATUS_PENDING;
            }
        });

        static::updating(function ($export) {
            // Tự động cập nhật status khi có người duyệt
            if ($export->isDirty('final_approved_by')) {
                $export->status = $export->final_approved_by 
                    ? self::STATUS_APPROVED 
                    : self::STATUS_PENDING;
                $export->approved_at = $export->final_approved_by ? now() : null;
            }
        });
    }
    
    public function productVariant()
{
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
}
}