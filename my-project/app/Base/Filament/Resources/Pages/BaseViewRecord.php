<?php

namespace App\Base\Filament\Resources\Pages;

use App\Base\Services\BaseService;
use Exception;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * BaseViewRecord - Lớp cơ sở cho trang xem chi tiết bản ghi trong Filament
 *
 * Tính năng:
 * - Hiển thị thông tin chi tiết bản ghi với schema form từ BaseResource
 * - Tải trước quan hệ từ BaseService
 * - Gửi thông báo Filament khi thực hiện hành động
 * - Xử lý lỗi và ghi log với BaseException
 * - Hỗ trợ tùy chỉnh giao diện và logic
 */
class BaseViewRecord extends ViewRecord{

    /**
     * Dịch vụ liên kết với trang
     *
     * @var BaseService|null
     */
    protected $service;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param BaseService|null $service
     */
    public function __construct(BaseService $service = NULL){
        $this->service = $service;
        parent::__construct();
    }

    /**
     * Cấu hình form xem chi tiết bản ghi
     *
     * @param Form $form
     *
     * @return Form
     */
    public function form(Form $form)
    : Form{
        try{
            return static::$resource::form($form)->disabled();
        }catch (Exception $e){
            Log::error("Failed to configure form for {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->throwException('Không thể cấu hình form xem chi tiết bản ghi.', 500, $e);
        }
    }

    /**
     * Lấy bản ghi để xem
     *
     * @return Model
     */
    protected function getRecord()
    : Model{
        try{
            $uuid = $this->getRecordId();
            if (!$this->service){
                return $this->getModel()::findOrFail($uuid);
            }

            $relations = static::$resource::getRelations();

            return $this->service->findByUuidOrFail($uuid, $relations);
        }catch (Exception $e){
            Log::error("Failed to get record for {$this->getResourceName()} UUID {$uuid}: {$e->getMessage()}",
                [
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->sendNotification('Lỗi', 'Không thể tải bản ghi.', 'danger');
            $this->throwException('Không thể tải bản ghi.', 404, $e);
        }
    }

    /**
     * Gửi thông báo Filament
     *
     * @param string $title
     * @param string $message
     * @param string $type ('success', 'error', 'warning', 'info')
     *
     * @return void
     */
    protected function sendNotification(string $title, string $message, string $type = 'success')
    : void{
        try{
            Notification::make()
                        ->title($title)
                        ->body($message)
                        ->{$type}()
                        ->send();
        }catch (Exception $e){
            Log::error("Failed to send notification in {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'title'   => $title,
                    'message' => $message,
                    'user_id' => auth()->id() ?? 'system',
                ]);
        }
    }

    /**
     * Lấy tên tài nguyên
     *
     * @return string
     */
    protected function getResourceName()
    : string{
        return class_basename(static::$resource);
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
    protected function throwException(string $message, int $code = 400, ?Exception $previous = NULL)
    : void{
        if ($this->throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, [], [], $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}