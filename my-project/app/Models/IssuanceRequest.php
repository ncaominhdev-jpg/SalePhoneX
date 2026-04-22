<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssuanceRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function parentRequest(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'parent_request_id');
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(IssuanceRequestDetail::class);
    }
     protected static function booted(): void
    {
        // Sử dụng event 'created' vì chúng ta cần ID của record để tạo code
        static::created(function (IssuanceRequest $issuanceRequest) {
            $issuanceRequest->code = '#PXK' . str_pad($issuanceRequest->id, 6, '0', STR_PAD_LEFT);
            $issuanceRequest->save();
        });
    }
}