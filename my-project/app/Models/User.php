<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * App\Models\User
 *
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int|null $branch_id
 * @property bool $status
 * @property string|null $avatar
 * @property string|null $citizen_identity_card
 * @property string|null $province_code
 * @property string|null $province_name
 * @property string|null $ward_code
 * @property string|null $ward_name
 * @property string|null $address
 * @property \App\Models\Branch|null $branch
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'role',
        'branch_id',
        'email_verified_at',
        'password',
        'avatar',
        'citizen_identity_card',
        'province_code',
        'province_name',
        'ward_code',
        'ward_name',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
        'branch_id' => 'integer',
        'password' => 'hashed',
    ];

    /**
     * Get the branch that the user belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'user_vouchers')
            ->withPivot('is_used')
            ->withTimestamps();
    }
     public function hasRole(array $roles): bool
    {
        // Kiểm tra xem cột 'role' của user có nằm trong mảng $roles không
        return in_array($this->role, $roles);
    }
}
