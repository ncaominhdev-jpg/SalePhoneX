<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'import_date',
        'created_by',
        'note',
        'status',
    'approved_by',
    'processed_by',
    'final_approved_by',
    ];
public function warehouse()
{
    return $this->belongsTo(Branch::class, 'warehouse_id');
}

public function user()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function importDetails()
{
    return $this->hasMany(ImportDetail::class, 'stock_import_id');
}

protected $casts = [
    'import_date' => 'date',
];
protected static function booted(): void
{
    static::creating(function ($import) {
        $branchId = $import->warehouse_id ?? Auth::user()->branch_id;
        $branchCode = 'CN' . str_pad($branchId, 2, '0', STR_PAD_LEFT);

        $latest = Import::where('warehouse_id', $branchId)->max('id') ?? 0;
        $nextNumber = str_pad($latest + 1, 4, '0', STR_PAD_LEFT);

        $import->code = '#PNK_' . $branchCode . '_' . $nextNumber;
    });
}




}