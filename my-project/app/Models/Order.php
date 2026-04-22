<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_momo',
        'user_id',
        'total_amount',
        'payment_method',
        'branch_id',
        'status',
        'note',
        'recipient_name',
        'phone',
        'address',
        'inventory_decreased',
         'delivery_type',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'inventory_decreased' => 'boolean',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
    
    public function order_details()
    {
        return $this->orderDetails();
    }
    protected static function booted()
    {
        static::created(function ($order) {
            if (in_array($order->payment_method, ['vnpay', 'momo'])) {
                app(\App\Services\OrderStatusService::class)->confirmPaidOrder($order);
            }
        });
    }
    // Mutator để validate trạng thái đơn hàng (TẠM THỜI DISABLE ĐỂ DEBUG)
    public function setStatusAttribute($value)
    {
        // TẠM THỜI CHỈ LOG VÀ SET TRỰC TIẾP
        \Illuminate\Support\Facades\Log::info("Order setStatusAttribute called", [
            'order_id' => $this->id ?? 'new',
            'current_status' => $this->status ?? 'none',
            'new_status' => $value,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
        
        $this->attributes['status'] = $value;
        
        /* COMMENT OUT VALIDATION TEMPORARILY
        // Định nghĩa thứ tự trạng thái hợp lệ
        $statusOrder = [
            'pending' => 1,
            'confirmed' => 2,
            'shipped' => 3,
            'delivered' => 4,
            'cancelled' => 5
        ];

        // Cho phép hủy đơn từ bất kỳ trạng thái nào
        if ($value === 'cancelled') {
            $this->attributes['status'] = $value;
            return;
        }

        // Kiểm tra nếu đang cố lùi trạng thái
        if (isset($statusOrder[$this->status]) && isset($statusOrder[$value])) {
            $currentStatusValue = $statusOrder[$this->status];
            $newStatusValue = $statusOrder[$value];

            if ($newStatusValue < $currentStatusValue) {
                throw ValidationException::withMessages([
                    'status' => 'Không được phép lùi trạng thái từ ' . $this->status . ' về ' . $value
                ]);
            }
        }

        $this->attributes['status'] = $value;
        */
    }

    // Accessor để tính tổng tiền từ order details
    public function getTotalFromDetailsAttribute()
    {
        return $this->orderDetails->sum(function ($detail) {
            if ($detail->productVariant) {
                return $detail->quantity * $detail->productVariant->price;
            } elseif ($detail->product) {
                return $detail->quantity * $detail->product->price;
            }
            return 0;
        });
    }
    
    public function orderItems()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    // Accessor để lấy số lượng sản phẩm
    public function getTotalItemsAttribute()
    {
        return $this->orderDetails->sum('quantity');
    }
}