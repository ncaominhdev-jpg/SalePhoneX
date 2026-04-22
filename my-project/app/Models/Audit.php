<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Audit extends Model
{
    protected $fillable = [
        'warehouse_id',
        'audit_date',
        'created_by',
        'note',
        'status'
    ];

    protected $casts = [
        'audit_date' => 'datetime',
    ];

    // Quan hệ với warehouse (branch)
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'warehouse_id');
    }

    // Quan hệ với người tạo
    public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

    // Quan hệ với chi tiết kiểm kho (AuditReport)
    public function reports(): HasMany
    {
        return $this->hasMany(AuditReport::class);
    }

    // Quan hệ với phiếu điều chỉnh
    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }

    // Tự động tạo code khi tạo mới
    protected static function boot()
{
    parent::boot();
    
    static::creating(function ($audit) {
        $audit->status = $audit->status ?? 'draft';
        $audit->created_by = $audit->created_by ?? Auth::id();
        
        // Tự động sinh mã phiếu theo định dạng PKK_CNO1_0009
        if (empty($audit->code)) {
            $branchCode = optional($audit->warehouse)->code ?? 'CN01'; // Lấy mã kho
            $latest = Audit::where('warehouse_id', $audit->warehouse_id)
                        ->latest('id')
                        ->first();
            
            $nextNumber = $latest ? (int) substr($latest->code, -4) + 1 : 1;
            $audit->code = '#PKK_' . $branchCode . '_' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }
    });
}

    // Tổng số sản phẩm được kiểm
    public function getTotalProductsAttribute(): int
    {
        return $this->reports()->count();
    }

    // Tổng chênh lệch
    public function getTotalVarianceAttribute(): int
    {
        return $this->reports()->sum('difference');
    }

    // Có chênh lệch không
    public function getHasVarianceAttribute(): bool
    {
        return $this->total_variance != 0;
    }

    // Hoàn thành kiểm kho và cập nhật inventory
    public function complete(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        DB::beginTransaction();
        try {
            // Tạo Balance record để ghi lại điều chỉnh
            $balance = Balance::create([
                'branch_id' => $this->warehouse_id,
                'audit_id' => $this->id,
                'created_by' => $this->created_by,
                'note' => 'Điều chỉnh từ kiểm kê #' . $this->id,
            ]);

            // Cập nhật inventory và tạo balance details
            foreach ($this->reports as $report) {
                if ($report->difference != 0) {
                    // Tạo chi tiết điều chỉnh
                    $balance->balanceDetails()->create([
                        'product_variant_id' => $report->product_variant_id,
                        'recorded_quantity' => $report->recorded_quantity,
                        'actual_quantity' => $report->actual_quantity,
                        'adjusted_quantity' => $report->difference, // Chênh lệch cần điều chỉnh
                        'created_by' => $this->created_by,
                        'reason' => 'Kiểm kê kho',
                    ]);

                    // Cập nhật inventory
                    $this->updateInventoryForProduct($report->product_variant_id, $report->actual_quantity);
                }

                // Đánh dấu đã cân bằng
                $report->update([
                    'is_balanced' => true,
                    'difference' => $report->actual_quantity - $report->recorded_quantity
                ]);
            }

            // Cập nhật trạng thái
            $this->update(['status' => 'completed']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Cập nhật inventory cho một sản phẩm
    private function updateInventoryForProduct(int $productVariantId, int $actualQuantity): void
    {
        $inventory = Inventory::where('warehouse_id', $this->warehouse_id)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if ($inventory) {
            // Ghi lại transaction trước khi update
            InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'type' => 'audit_adjustment',
                'quantity_before' => $inventory->quantity,
                'quantity_after' => $actualQuantity,
                'quantity_change' => $actualQuantity - $inventory->quantity,
                'reference_type' => 'audit',
                'reference_id' => $this->id,
                'note' => "Điều chỉnh từ kiểm kho #{$this->id}",
                 'created_by' => $this->created_by ?? Auth::id(),
            ]);

            // Cập nhật số lượng thực tế
            $inventory->update(['quantity' => $actualQuantity]);
        }
    }
}