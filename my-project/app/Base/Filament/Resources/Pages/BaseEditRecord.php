<?php

namespace App\Base\Filament\Resources\Pages;

use App\Base\Services\BaseService;
use Exception;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * BaseEditRecord - Lớp cơ sở cho trang chỉnh sửa bản ghi trong Filament
 *
 * Tính năng:
 * - Tái sử dụng schema form từ BaseResource
 * - Xử lý cập nhật dữ liệu với validation qua BaseService
 * - Gửi thông báo Filament khi cập nhật thành công/thất bại
 * - Xử lý lỗi và ghi log với BaseException
 * - Hỗ trợ tùy chỉnh giao diện và logic
 */
class BaseEditRecord extends EditRecord{

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
     * Cấu hình form chỉnh sửa bản ghi
     *
     * @param Form $form
     *
     * @return Form
     */
    public function form(Form $form)
    : Form{
        try{
            return static::$resource::form($form);
        }catch (Exception $e){
            Log::error("Failed to configure form for {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->throwException('Không thể cấu hình form chỉnh sửa bản ghi.', 500, $e);
        }
    }

    /**
     * Xử lý dữ liệu khi cập nhật bản ghi
     *
     * @param array $data
     *
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data)
    : Model{
        try{
            if (!$this->service){
                $record->update($data);

                return $record;
            }

            return $this->service->update($record->uuid, $data);
        }catch (Exception $e){
            Log::error("Failed to update record for {$this->getResourceName()} UUID {$record->uuid}: {$e->getMessage()}",
                [
                    'data'    => $data,
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->sendNotification('Lỗi', 'Không thể cập nhật bản ghi.', 'danger');
            $this->throwException('Không thể cập nhật bản ghi.', 500, $e);
        }
    }

    /**
     * Gửi thông báo sau khi cập nhật thành công
     *
     * @return void
     */
    protected function afterSave()
    : void{
        $this->sendNotification('Thành công', 'Bản ghi đã được cập nhật.');
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