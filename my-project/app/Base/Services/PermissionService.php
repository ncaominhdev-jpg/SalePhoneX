<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * PermissionService - Lớp tiện ích quản lý phân quyền
 *
 * Tính năng:
 * - Kiểm tra quyền và role của người dùng với Spatie\Laravel-Permission
 * - Gán/thu hồi quyền hoặc role cho người dùng
 * - Tích hợp với BaseController và Filament để kiểm soát truy cập
 * - Xử lý lỗi và ghi log với BaseException
 */
class PermissionService{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     */
    public function __construct(){
        // Kiểm tra package Spatie\Laravel-Permission
        if (!class_exists(\Spatie\Permission\Models\Permission::class)){
            throw new Exception('Package Spatie\Laravel-Permission chưa được cài đặt. Vui lòng chạy: composer require spatie/laravel-permission');
        }
    }

    /**
     * Kiểm tra quyền của người dùng
     *
     * @param Authenticatable $user       Người dùng
     * @param string          $permission Quyền cần kiểm tra
     *
     * @return bool
     * @throws BaseException
     */
    public function hasPermission(Authenticatable $user, string $permission)
    : bool{
        try{
            return $user->hasPermissionTo($permission);
        }catch (Exception $e){
            $this->logError('hasPermission', [
                'user_id'    => $user->id,
                'permission' => $permission,
            ], $e);
            throw new BaseException('Không thể kiểm tra quyền.', 500, [], [], $e);
        }
    }

    /**
     * Kiểm tra role của người dùng
     *
     * @param Authenticatable $user Người dùng
     * @param string          $role Role cần kiểm tra
     *
     * @return bool
     * @throws BaseException
     */
    public function hasRole(Authenticatable $user, string $role)
    : bool{
        try{
            return $user->hasRole($role);
        }catch (Exception $e){
            $this->logError('hasRole', [
                'user_id' => $user->id,
                'role'    => $role,
            ], $e);
            throw new BaseException('Không thể kiểm tra role.', 500, [], [], $e);
        }
    }

    /**
     * Gán quyền cho người dùng
     *
     * @param Authenticatable $user       Người dùng
     * @param string          $permission Quyền cần gán
     *
     * @return void
     * @throws BaseException
     */
    public function assignPermission(Authenticatable $user, string $permission)
    : void{
        try{
            // Tạo quyền nếu chưa tồn tại
            Permission::findOrCreate($permission);
            $user->givePermissionTo($permission);
        }catch (Exception $e){
            $this->logError('assignPermission', [
                'user_id'    => $user->id,
                'permission' => $permission,
            ], $e);
            throw new BaseException('Không thể gán quyền.', 500, [], [], $e);
        }
    }

    /**
     * Gán role cho người dùng
     *
     * @param Authenticatable $user Người dùng
     * @param string          $role Role cần gán
     *
     * @return void
     * @throws BaseException
     */
    public function assignRole(Authenticatable $user, string $role)
    : void{
        try{
            // Tạo role nếu chưa tồn tại
            Role::findOrCreate($role);
            $user->assignRole($role);
        }catch (Exception $e){
            $this->logError('assignRole', [
                'user_id' => $user->id,
                'role'    => $role,
            ], $e);
            throw new BaseException('Không thể gán role.', 500, [], [], $e);
        }
    }

    /**
     * Thu hồi quyền khỏi người dùng
     *
     * @param Authenticatable $user       Người dùng
     * @param string          $permission Quyền cần thu hồi
     *
     * @return void
     * @throws BaseException
     */
    public function revokePermission(Authenticatable $user, string $permission)
    : void{
        try{
            $user->revokePermissionTo($permission);
        }catch (Exception $e){
            $this->logError('revokePermission', [
                'user_id'    => $user->id,
                'permission' => $permission,
            ], $e);
            throw new BaseException('Không thể thu hồi quyền.', 500, [], [], $e);
        }
    }

    /**
     * Thu hồi role khỏi người dùng
     *
     * @param Authenticatable $user Người dùng
     * @param string          $role Role cần thu hồi
     *
     * @return void
     * @throws BaseException
     */
    public function revokeRole(Authenticatable $user, string $role)
    : void{
        try{
            $user->removeRole($role);
        }catch (Exception $e){
            $this->logError('revokeRole', [
                'user_id' => $user->id,
                'role'    => $role,
            ], $e);
            throw new BaseException('Không thể thu hồi role.', 500, [], [], $e);
        }
    }

    /**
     * Kiểm tra và ném lỗi nếu không có quyền
     *
     * @param Authenticatable $user       Người dùng
     * @param string          $permission Quyền cần kiểm tra
     *
     * @return void
     * @throws BaseException
     */
    public function checkPermissionOrFail(Authenticatable $user, string $permission)
    : void{
        if (!$this->hasPermission($user, $permission)){
            throw new BaseException('Bạn không có quyền thực hiện hành động này.', 403, [], []);
        }
    }

    /**
     * Lấy danh sách quyền của người dùng
     *
     * @param Authenticatable $user Người dùng
     *
     * @return array
     * @throws BaseException
     */
    public function getUserPermissions(Authenticatable $user)
    : array{
        try{
            return $user->getAllPermissions()->pluck('name')->toArray();
        }catch (Exception $e){
            $this->logError('getUserPermissions', [
                'user_id' => $user->id,
            ], $e);
            throw new BaseException('Không thể lấy danh sách quyền.', 500, [], [], $e);
        }
    }

    /**
     * Lấy danh sách role của người dùng
     *
     * @param Authenticatable $user Người dùng
     *
     * @return array
     * @throws BaseException
     */
    public function getUserRoles(Authenticatable $user)
    : array{
        try{
            return $user->roles->pluck('name')->toArray();
        }catch (Exception $e){
            $this->logError('getUserRoles', [
                'user_id' => $user->id,
            ], $e);
            throw new BaseException('Không thể lấy danh sách role.', 500, [], [], $e);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (user_id, permission, role, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in PermissionService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}