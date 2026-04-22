<?php

namespace App\Base\Models\Traits;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasTimestamps - Cung cấp chức năng xử lý và định dạng timestamps cho các model
 *
 * Tính năng:
 * - Định dạng ngày giờ theo nhiều định dạng
 * - Tính toán khoảng thời gian tương đối (diffForHumans)
 * - Hỗ trợ múi giờ (timezone)
 * - Cung cấp accessor tiện ích cho UI và API
 * - Xử lý lỗi khi ngày giờ không hợp lệ
 */
trait HasTimestamps{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected static $throwCustomExceptions = TRUE;

    /**
     * Múi giờ mặc định cho hệ thống
     *
     * @var string
     */
    protected $defaultTimezone = 'Asia/Ho_Chi_Minh';

    /**
     * Định dạng ngày giờ mặc định
     *
     * @var string
     */
    protected $defaultDateTimeFormat = 'd/m/Y H:i';

    /**
     * Định dạng ngày mặc định
     *
     * @var string
     */
    protected $defaultDateFormat = 'd/m/Y';

    /**
     * Định dạng ngày giờ đầy đủ
     *
     * @var string
     */
    protected $fullDateTimeFormat = 'd/m/Y H:i:s';

    /**
     * Boot trait - Gắn các sự kiện nếu cần
     */
    protected static function bootHasTimestamps(){
        // Không cần sự kiện boot mặc định, nhưng để sẵn cho mở rộng
    }

    /**
     * Định dạng ngày giờ
     *
     * @param mixed       $dateTime
     * @param string      $format
     * @param string|null $timezone
     *
     * @return string
     */
    public function formatDateTime($dateTime, string $format = NULL, ?string $timezone = NULL)
    : string{
        try{
            if (empty($dateTime)){
                return '';
            }

            $format   = $format ?? $this->defaultDateTimeFormat;
            $timezone = $timezone ?? $this->getTimezone();

            $carbon = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);

            return $carbon->setTimezone($timezone)->format($format);
        }catch (Exception $e){
            Log::error("Failed to format datetime for {$this->getTable()} ID {$this->id}: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * Định dạng ngày
     *
     * @param mixed  $date
     * @param string $format
     *
     * @return string
     */
    public function formatDate($date, string $format = NULL)
    : string{
        try{
            if (empty($date)){
                return '';
            }

            $format = $format ?? $this->defaultDateFormat;
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

            return $carbon->format($format);
        }catch (Exception $e){
            Log::error("Failed to format date for {$this->getTable()} ID {$this->id}: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * Lấy khoảng thời gian tương đối
     *
     * @param string $field
     * @param bool   $full
     *
     * @return string
     */
    public function getTimeAgo(string $field = 'created_at', bool $full = FALSE)
    : string{
        try{
            if (empty($this->{$field})){
                return '';
            }

            $carbon = $this->{$field} instanceof Carbon ? $this->{$field} : Carbon::parse($this->{$field});

            return $full ? $carbon->diffForHumans(NULL, TRUE, TRUE) : $carbon->diffForHumans();
        }catch (Exception $e){
            Log::error("Failed to get time ago for {$this->getTable()} ID {$this->id} field {$field}: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * Lấy múi giờ cho model
     *
     * @return string
     */
    protected function getTimezone()
    : string{
        try{
            // Ưu tiên múi giờ của user nếu có
            if (auth()->check() && !empty(auth()->user()->timezone)){
                return auth()->user()->timezone;
            }

            // Lấy từ config hoặc mặc định
            return config('app.timezone', $this->defaultTimezone);
        }catch (Exception $e){
            Log::error("Failed to get timezone for {$this->getTable()} ID {$this->id}: {$e->getMessage()}");

            return $this->defaultTimezone;
        }
    }

    /**
     * Accessor: Định dạng created_at đầy đủ
     *
     * @return string
     */
    public function getCreatedAtFullAttribute()
    : string{
        return $this->formatDateTime($this->created_at, $this->fullDateTimeFormat);
    }

    /**
     * Accessor: Định dạng updated_at đầy đủ
     *
     * @return string
     */
    public function getUpdatedAtFullAttribute()
    : string{
        return $this->formatDateTime($this->updated_at, $this->fullDateTimeFormat);
    }

    /**
     * Accessor: Khoảng thời gian từ created_at
     *
     * @return string
     */
    public function getCreatedAtAgoAttribute()
    : string{
        return $this->getTimeAgo('created_at');
    }

    /**
     * Accessor: Khoảng thời gian từ updated_at
     *
     * @return string
     */
    public function getUpdatedAtAgoAttribute()
    : string{
        return $this->getTimeAgo('updated_at');
    }

    /**
     * Kiểm tra ngày giờ hợp lệ
     *
     * @param mixed $dateTime
     *
     * @return bool
     */
    public function isValidDateTime($dateTime)
    : bool{
        try{
            if (empty($dateTime)){
                return FALSE;
            }
            Carbon::parse($dateTime);

            return TRUE;
        }catch (Exception $e){
            return FALSE;
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