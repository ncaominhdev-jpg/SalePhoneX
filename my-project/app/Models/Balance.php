<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth; // Đảm bảo đã import Auth

class Balance extends Model
{
    protected $fillable = [
        'branch_id',
        'audit_id',
        'created_by',
        'note',
        'code', // Thêm 'code' vào mảng fillable
    ];

    // Các mối quan hệ hiện có
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function balanceDetails(): HasMany
    {
        return $this->hasMany(BalanceDetail::class);
    }

    // Mối quan hệ productVariant có vẻ không phù hợp ở đây
    // productVariant thường nằm trong BalanceDetail, không phải Balance trực tiếp.
    // public function productVariant()
    // {
    //     return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    // }


    // Tổng điều chỉnh
    public function getTotalAdjustedAttribute(): int
    {
        return $this->balanceDetails()->sum('adjusted_quantity');
    }

    // Tự động tạo code khi tạo mới
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($balance) {
            // Đảm bảo created_by được thiết lập
            $balance->created_by = $balance->created_by ?? Auth::id();

            // Tự động sinh mã phiếu điều chỉnh
            if (empty($balance->code)) {
                $branchCode = 'CN00'; // Mặc định nếu không lấy được mã chi nhánh
                if ($balance->branch_id) {
                    $branch = Branch::find($balance->branch_id);
                    if ($branch) {
                        $branchCode = $branch->code;
                    }
                }

                // Lấy số thứ tự lớn nhất cho chi nhánh này
                $latest = static::where('branch_id', $balance->branch_id)
                                ->latest('id')
                                ->first();

                $nextNumber = 1;
                if ($latest && preg_match('/_(\d{4})$/', $latest->code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                $balance->code = '#PDD_' . $branchCode . '_' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}