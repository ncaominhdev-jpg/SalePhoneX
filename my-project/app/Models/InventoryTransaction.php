<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

