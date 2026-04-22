<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'to_balances_id',
        'approved_by',
        'transfer_date',
        'status', // trạng thái của quá trình chuyển ('approved', 'rejected', 'completed')
        'admin_note',
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_balances_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}