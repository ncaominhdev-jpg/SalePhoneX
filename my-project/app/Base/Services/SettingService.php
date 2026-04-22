<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use App\Base\Models\Setting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SettingService - Lớp tiện ích quản lý cấu hình hệ thống
 *
 * Tính năng:
 * - Lấy, lưu, và xóa cài đặt với khóa tùy chỉnh
 * - Cache cài đặt để giảm truy vấn database
 * - Hỗ trợ giá trị mặc định và định dạng JSON
 * - Xử lý lỗi và ghi log với BaseException
 * - Tích hợp với Filament để quản lý cài đặt
 */
class SettingService{

    /**
     * Dịch vụ cache
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param CacheService $cacheService
     */
    public function __construct(CacheService $cacheService){
        $this->cacheService = $cacheService;
    }

    /**
     * Lấy giá trị cài đặt
     *
     * @param string $key      Khóa cài đặt
     * @param mixed  $default  Giá trị mặc định nếu không tìm thấy
     * @param bool   $useCache Sử dụng cache hay không
     *
     * @return mixed
     * @throws BaseException
     */
    public function get(string $key, $default = NULL, bool $useCache = TRUE){
        try{
            $cacheKey = "setting_{$key}";

            if ($useCache){
                $cachedValue = $this->cacheService->get($cacheKey, NULL, ['settings']);
                if ($cachedValue !== NULL){
                    return $this->parseValue($cachedValue);
                }
            }

            $setting = Setting::where('key', $key)->first();
            $value   = $setting ? $this->parseValue($setting->value) : $default;

            if ($useCache && $value !== $default){
                $this->cacheService->put($cacheKey, $value, 86400, ['settings']); // Cache 24 giờ
            }

            return $value;
        }catch (Exception $e){
            $this->logError('get', [
                'key'       => $key,
                'use_cache' => $useCache,
            ], $e);
            throw new BaseException('Không thể lấy giá trị cài đặt.', 500, [], [], $e);
        }
    }

    /**
     * Lưu giá trị cài đặt
     *
     * @param string $key      Khóa cài đặt
     * @param mixed  $value    Giá trị cần lưu
     * @param bool   $useCache Cập nhật cache hay không
     *
     * @return bool
     * @throws BaseException
     */
    public function set(string $key, $value, bool $useCache = TRUE)
    : bool{
        try{
            DB::transaction(function () use ($key, $value, $useCache){
                $serializedValue = $this->serializeValue($value);

                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $serializedValue]
                );

                if ($useCache){
                    $cacheKey = "setting_{$key}";
                    $this->cacheService->put($cacheKey, $value, 86400, ['settings']);
                }
            });

            Log::info("Setting stored: {$key}", [
                'user_id' => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('set', [
                'key'       => $key,
                'use_cache' => $useCache,
            ], $e);
            throw new BaseException('Không thể lưu giá trị cài đặt.', 500, [], [], $e);
        }
    }

    /**
     * Xóa cài đặt
     *
     * @param string $key      Khóa cài đặt
     * @param bool   $useCache Xóa cache hay không
     *
     * @return bool
     * @throws BaseException
     */
    public function forget(string $key, bool $useCache = TRUE)
    : bool{
        try{
            DB::transaction(function () use ($key, $useCache){
                Setting::where('key', $key)->delete();

                if ($useCache){
                    $cacheKey = "setting_{$key}";
                    $this->cacheService->forget($cacheKey, ['settings']);
                }
            });

            Log::info("Setting deleted: {$key}", [
                'user_id' => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('forget', [
                'key'       => $key,
                'use_cache' => $useCache,
            ], $e);
            throw new BaseException('Không thể xóa cài đặt.', 500, [], [], $e);
        }
    }

    /**
     * Lấy tất cả cài đặt
     *
     * @param bool $useCache Sử dụng cache hay không
     *
     * @return array
     * @throws BaseException
     */
    public function all(bool $useCache = TRUE)
    : array{
        try{
            $cacheKey = 'settings_all';

            if ($useCache){
                $cachedValue = $this->cacheService->get($cacheKey, NULL, ['settings']);
                if ($cachedValue !== NULL){
                    return $cachedValue;
                }
            }

            $settings = Setting::all()->pluck('value', 'key')->map(function ($value){
                return $this->parseValue($value);
            })->toArray();

            if ($useCache){
                $this->cacheService->put($cacheKey, $settings, 86400, ['settings']);
            }

            return $settings;
        }catch (Exception $e){
            $this->logError('all', [
                'use_cache' => $useCache,
            ], $e);
            throw new BaseException('Không thể lấy tất cả cài đặt.', 500, [], [], $e);
        }
    }

    /**
     * Phân tích giá trị từ chuỗi serialized
     *
     * @param string $value Giá trị serialized
     *
     * @return mixed
     */
    protected function parseValue(string $value){
        try{
            $decoded = json_decode($value, TRUE);

            return $decoded !== NULL ? $decoded : $value;
        }catch (Exception $e){
            return $value;
        }
    }

    /**
     * Serialize giá trị để lưu trữ
     *
     * @param mixed $value Giá trị cần serialize
     *
     * @return string
     */
    protected function serializeValue($value)
    : string{
        return is_array($value) || is_object($value) ? json_encode($value,
            JSON_UNESCAPED_UNICODE) : (string) $value;
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (key, use_cache, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in SettingService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}