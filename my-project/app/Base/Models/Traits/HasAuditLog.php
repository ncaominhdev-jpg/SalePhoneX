<?php

namespace App\Base\Models\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasAuditLog - Cung cấp chức năng ghi nhật ký thay đổi (audit logging) cho các model
 *
 * Tính năng:
 * - Ghi lại thông tin người tạo và người cập nhật bản ghi
 * - Ghi log chi tiết cho các sự kiện tạo, cập nhật, xóa mềm, và khôi phục
 * - Cung cấp phương thức truy xuất lịch sử thay đổi
 * - Xử lý lỗi khi ghi log hoặc không có user đăng nhập
 */
trait HasAuditLog{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected static $throwCustomExceptions = TRUE;

    /**
     * Boot trait - Gắn các sự kiện cho model
     */
    protected static function bootHasAuditLog(){
        // Ghi lại người tạo và người cập nhật khi tạo bản ghi
        static::creating(function (Model $model){
            try{
                $userId            = Auth::id() ?? config('base.audit_log.default_user_id',
                    'system');
                $model->created_by = $userId;
                $model->updated_by = $userId;
            }catch (Exception $e){
                Log::error("Failed to set audit user for {$model->getTable()} on creation: {$e->getMessage()}");
                static::throwException('Không thể ghi thông tin người tạo bản ghi.', 500, $e);
            }
        });

        // Cập nhật người sửa khi cập nhật bản ghi
        static::updating(function (Model $model){
            try{
                $model->updated_by = Auth::id() ?? config('base.audit_log.default_user_id',
                    'system');
            }catch (Exception $e){
                Log::error("Failed to set audit user for {$model->getTable()} on update: {$e->getMessage()}");
                static::throwException('Không thể ghi thông tin người cập nhật bản ghi.', 500, $e);
            }
        });

        // Ghi log khi bản ghi được tạo
        static::created(function (Model $model){
            try{
                Log::info("Created {$model->getTable()}", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid ?? NULL,
                    'data'    => $model->toArray(),
                    'user_id' => $model->created_by,
                ]);
            }catch (Exception $e){
                Log::error("Failed to log creation of {$model->getTable()}: {$e->getMessage()}");
            }
        });

        // Ghi log khi bản ghi được cập nhật
        static::updated(function (Model $model){
            try{
                if ($model->wasChanged()){
                    Log::info("Updated {$model->getTable()}", [
                        'id'       => $model->id,
                        'uuid'     => $model->uuid ?? NULL,
                        'changes'  => $model->getChanges(),
                        'original' => array_intersect_key($model->getOriginal(),
                            $model->getChanges()),
                        'user_id'  => $model->updated_by,
                    ]);
                }
            }catch (Exception $e){
                Log::error("Failed to log update of {$model->getTable()}: {$e->getMessage()}");
            }
        });

        // Ghi log khi bản ghi bị xóa mềm
        static::softDeleted(function (Model $model){
            try{
                Log::info("Soft deleted {$model->getTable()}", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid ?? NULL,
                    'user_id' => Auth::id() ?? config('base.audit_log.default_user_id', 'system'),
                ]);
            }catch (Exception $e){
                Log::error("Failed to log soft deletion of {$model->getTable()}: {$e->getMessage()}");
            }
        });

        // Ghi log khi bản ghi được khôi phục
        static::restored(function (Model $model){
            try{
                Log::info("Restored {$model->getTable()}", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid ?? NULL,
                    'user_id' => Auth::id() ?? config('base.audit_log.default_user_id', 'system'),
                ]);
            }catch (Exception $e){
                Log::error("Failed to log restoration of {$model->getTable()}: {$e->getMessage()}");
            }
        });
    }

    /**
     * Lấy thông tin audit trail của bản ghi
     *
     * @return array
     */
    public function getAuditTrail()
    : array{
        try{
            return [
                'created_by' => $this->creator?->name ?? ($this->created_by === 'system' ? 'Hệ thống' : 'Không xác định'),
                'updated_by' => $this->updater?->name ?? ($this->updated_by === 'system' ? 'Hệ thống' : 'Không xác định'),
                'created_at' => $this->created_at?->format('d/m/Y H:i') ?? 'N/A',
                'updated_at' => $this->updated_at?->format('d/m/Y H:i') ?? 'N/A',
                'changes'    => $this->wasChanged() ? $this->getChanges() : [],
                'original'   => $this->wasChanged() ? array_intersect_key($this->getOriginal(),
                    $this->getChanges()) : [],
            ];
        }catch (Exception $e){
            Log::error("Failed to retrieve audit trail for {$this->getTable()} ID {$this->id}: {$e->getMessage()}");

            return [
                'created_by' => 'Lỗi',
                'updated_by' => 'Lỗi',
                'created_at' => 'N/A',
                'updated_at' => 'N/A',
                'changes'    => [],
                'original'   => [],
            ];
        }
    }

    /**
     * Lấy danh sách các thay đổi gần đây
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentChanges(int $limit = 10)
    : array{
        // Giả định sử dụng một bảng audit_logs riêng nếu có
        // Hiện tại trả về thay đổi từ model hiện tại
        try{
            return [
                'id'         => $this->id,
                'uuid'       => $this->uuid ?? NULL,
                'changes'    => $this->wasChanged() ? $this->getChanges() : [],
                'original'   => $this->wasChanged() ? array_intersect_key($this->getOriginal(),
                    $this->getChanges()) : [],
                'updated_at' => $this->updated_at?->format('d/m/Y H:i') ?? 'N/A',
                'updated_by' => $this->updater?->name ?? ($this->updated_by === 'system' ? 'Hệ thống' : 'Không xác định'),
            ];
        }catch (Exception $e){
            Log::error("Failed to retrieve recent changes for {$this->getTable()} ID {$this->id}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     *
     * @return void
     * @throws Exception
     */
    protected static function throwException(
        string $message,
        int $code = 400,
        ?Exception $previous = NULL)
    : void{
        if (static::$throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}