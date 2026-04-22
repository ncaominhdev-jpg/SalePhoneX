<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // Import HasOne
use Illuminate\Support\Facades\Auth;
class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_date',
        'status', // Giữ lại status trên bảng requests
        'created_by',
        'note',
        // 'request_type' sẽ được ngầm định là 'import'
    ];

    protected $casts = [
        'request_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requestDetails(): HasMany
    {
        return $this->hasMany(RequestDetail::class);
    }

    public function transfer(): HasOne // Mối quan hệ tới phiếu chuyển kho
    {
        return $this->hasOne(RequestTransfer::class);
    }

    // Accessor để truy cập chi nhánh của người tạo (từ User model)
    public function getFromBranchAttribute(): ?Branch
    {
        return $this->creator?->branch;
    }

    // Accessor để truy cập chi nhánh cung cấp (từ RequestTransfer model)
    public function getToBranchAttribute(): ?Branch
    {
        return $this->transfer?->toBranch;
    }

    // Accessor để truy cập người duyệt (từ RequestTransfer model)
    public function getApproverAttribute(): ?User
    {
        return $this->transfer?->approver;
    }
    public function issuanceRequests(): HasMany
    {
        // Giả sử model phiếu xuất của bạn tên là IssuanceRequest
        // và cột khóa ngoại trong bảng issuance_requests là parent_request_id
        return $this->hasMany(IssuanceRequest::class, 'parent_request_id');
    }
     protected static function booted(): void
    {
        static::creating(function (Request $request) {
            if (Auth::check()) {
                $user = Auth::user();
                $branchId = $user->branch_id;
                $branchCode = 'CN' . str_pad($branchId, 2, '0', STR_PAD_LEFT);

                // Tìm phiếu cuối cùng của chi nhánh đó
                $latestRequest = self::whereHas('creator', fn ($q) => $q->where('branch_id', $branchId))
                    ->latest('id')->first();

                $nextNumber = 1;
                if ($latestRequest && $latestRequest->code) {
                    // Tách số cuối cùng ra và +1
                    $lastNumber = (int) substr($latestRequest->code, -4);
                    $nextNumber = $lastNumber + 1;
                }
                
                $request->code = '#PNK_' . $branchCode . '_' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}