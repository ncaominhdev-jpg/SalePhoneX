<?php

namespace App\Base\Models\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait HasUuid - Cung cấp chức năng quản lý UUID cho các model
 *
 * Tính năng:
 * - Tự động tạo UUID khi tạo bản ghi mới
 * - Sử dụng UUID làm route key
 * - Hỗ trợ tìm kiếm theo UUID
 * - Xử lý lỗi khi UUID không hợp lệ hoặc trùng lặp
 */
trait HasUuid{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected static $throwCustomExceptions = TRUE;

    /**
     * Boot trait - Gắn các sự kiện cho model
     */
    protected static function bootHasUuid(){
        static::creating(function (Model $model){
            try{
                if (empty($model->uuid)){
                    $model->uuid = self::generateUniqueUuid($model);
                }
                if (empty($model->id)){
                    $model->id = $model->uuid;
                }
            }catch (Exception $e){
                Log::error("Failed to generate UUID for {$model->getTable()}: {$e->getMessage()}");
                static::throwException('Không thể tạo UUID cho bản ghi.', 500, $e);
            }
        });

        // Kiểm tra tính hợp lệ của UUID trước khi lưu
        static::saving(function (Model $model){
            if (!self::isValidUuid($model->uuid)){
                Log::error("Invalid UUID for {$model->getTable()}: {$model->uuid}");
                static::throwException('UUID không hợp lệ.', 422);
            }
        });
    }

    /**
     * Sử dụng UUID làm route key thay vì ID
     *
     * @return string
     */
    public function getRouteKeyName()
    : string{
        return 'uuid';
    }

    /**
     * Tạo UUID duy nhất
     *
     * @param Model $model
     *
     * @return string
     * @throws Exception
     */
    protected static function generateUniqueUuid(Model $model)
    : string{
        $maxAttempts = 5;
        $attempt     = 0;

        do{
            $uuid = Str::uuid()->toString();
            $attempt ++;

            if ($attempt > $maxAttempts){
                throw new Exception('Không thể tạo UUID duy nhất sau nhiều lần thử.');
            }
        }while ($model->newQuery()->where('uuid', $uuid)->exists());

        return $uuid;
    }

    /**
     * Kiểm tra tính hợp lệ của UUID
     *
     * @param string|null $uuid
     *
     * @return bool
     */
    protected static function isValidUuid(?string $uuid)
    : bool{
        if (empty($uuid)){
            return FALSE;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $uuid) === 1;
    }

    /**
     * Tìm model bằng UUID
     *
     * @param string $uuid
     *
     * @return static|null
     */
    public static function findByUuid(string $uuid)
    : ?static{
        if (!self::isValidUuid($uuid)){
            return NULL;
        }

        return static::where('uuid', $uuid)->first();
    }

    /**
     * Tìm model bằng UUID hoặc ném ngoại lệ
     *
     * @param string $uuid
     *
     * @return static
     * @throws Exception
     */
    public static function findByUuidOrFail(string $uuid)
    : static{
        if (!self::isValidUuid($uuid)){
            static::throwException("UUID không hợp lệ: {$uuid}", 422);
        }

        $model = static::findByUuid($uuid);

        if (!$model){
            static::throwException("Không tìm thấy bản ghi với UUID: {$uuid}", 404);
        }

        return $model;
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