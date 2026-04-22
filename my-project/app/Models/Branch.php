<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $table = 'branches';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'district',
        'city',
        'ward',
        'status',
        'type',
    ];

    protected $casts = [
        'status' => 'boolean',
        'type' => 'string',
    ];

    /**
     * Lấy danh sách user thuộc chi nhánh này.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }
}
