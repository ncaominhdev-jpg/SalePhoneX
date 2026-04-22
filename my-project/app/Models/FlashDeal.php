<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class FlashDeal extends Model
{
    protected $fillable = ['title', 'deal_date', 'start_time', 'end_time', 'is_active'];

    protected $casts = [
        'deal_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FlashDealItem::class, 'flash_deal_id');
    }

    /** scope active trong thời điểm hiện tại (nếu cần dùng FE) */
    public function scopeActive(Builder $q): Builder
    {
        $now = now();
        return $q->where('is_active', true)
            ->whereDate('deal_date', $now->toDateString())
            ->where('start_time', '<=', $now->format('H:i:s'))
            ->where('end_time', '>=', $now->format('H:i:s'));
    }
}
