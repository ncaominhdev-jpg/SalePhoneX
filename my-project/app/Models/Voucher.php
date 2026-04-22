<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class Voucher extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_value',
        'usage_limit',
        'used',
        'start_date',
        'end_date',
        'status',
    ];

    // ✅ Laravel sẽ tự convert start_date & end_date thành Carbon instance
    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'status'     => 'boolean',
    ];

    public function isValid($totalAmount)
    {
        $now = Carbon::now();

        // ✅ Debug log nếu cần kiểm tra
        Log::info('Voucher Check', [
            'now' => $now,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'used' => $this->used,
            'usage_limit' => $this->usage_limit,
            'status' => $this->status,
            'min_order_value' => $this->min_order_value,
            'totalAmount' => $totalAmount,
        ]);

        // Kiểm tra trạng thái
        if (!$this->status) {
            return false;
        }

        // Kiểm tra ngày
       if ($now->lt($this->start_date->startOfDay()) || $now->gt($this->end_date->endOfDay())) {
    return false;
}


        // Kiểm tra số lượt sử dụng
        if ($this->used >= $this->usage_limit) {
            return false;
        }

        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($totalAmount < $this->min_order_value) {
            return false;
        }

        return true;
    }
}
