<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * NotificationService - Lớp tiện ích xử lý thông báo trong hệ thống
 *
 * Tính năng:
 * - Gửi thông báo Filament (success, error, warning, info)
 * - Gửi email thông báo với mailable tùy chỉnh
 * - Hỗ trợ mở rộng cho các kênh khác (SMS, push notification)
 * - Xử lý lỗi và ghi log với BaseException
 * - Tái sử dụng trong BaseController, BaseListRecords, v.v.
 */
class NotificationService{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Gửi thông báo Filament
     *
     * @param string $title   Tiêu đề thông báo
     * @param string $message Nội dung thông báo
     * @param string $type    Loại thông báo (success, error, warning, info)
     *
     * @return void
     * @throws BaseException
     */
    public function sendFilamentNotification(
        string $title,
        string $message,
        string $type = 'success')
    : void{
        try{
            $types = ['success', 'error', 'warning', 'info'];
            if (!in_array($type, $types)){
                throw new Exception("Loại thông báo không hợp lệ: {$type}.");
            }

            FilamentNotification::make()
                                ->title($title)
                                ->body($message)
                                ->{$type}()
                                ->send();
        }catch (Exception $e){
            $this->logError('sendFilamentNotification', [
                'title'   => $title,
                'message' => $message,
                'type'    => $type,
            ], $e);
            throw new BaseException('Không thể gửi thông báo Filament.', 500, [], [], $e);
        }
    }

    /**
     * Gửi email thông báo
     *
     * @param string|array $recipients Danh sách email người nhận
     * @param Mailable     $mailable   Đối tượng mailable
     *
     * @return void
     * @throws BaseException
     */
    public function sendEmailNotification($recipients, Mailable $mailable)
    : void{
        try{
            // Chuẩn hóa recipients thành mảng
            $recipients = is_array($recipients) ? $recipients : [$recipients];

            // Kiểm tra email hợp lệ
            $validator = Validator::make(['emails' => $recipients], [
                'emails.*' => 'required|email',
            ]);

            if ($validator->fails()){
                throw new Exception('Email người nhận không hợp lệ: ' . implode(', ', $recipients));
            }

            Mail::to($recipients)->send($mailable);
        }catch (Exception $e){
            $this->logError('sendEmailNotification', [
                'recipients' => $recipients,
                'mailable'   => get_class($mailable),
            ], $e);
            throw new BaseException('Không thể gửi email thông báo.', 500, [], [], $e);
        }
    }

    /**
     * Gửi thông báo qua kênh khác (SMS, push notification, v.v.)
     *
     * @param string       $channel    Kênh thông báo (sms, push, v.v.)
     * @param string|array $recipients Người nhận
     * @param string       $message    Nội dung thông báo
     * @param array        $options    Tùy chọn bổ sung
     *
     * @return void
     * @throws BaseException
     */
    public function sendCustomNotification(
        string $channel,
        $recipients,
        string $message,
        array $options = [])
    : void{
        try{
            // Placeholder cho các kênh khác, có thể triển khai sau
            switch ($channel){
                case 'sms':
                    // Triển khai gửi SMS (ví dụ: sử dụng Twilio)
                    throw new Exception('Kênh SMS chưa được triển khai.');
                case 'push':
                    // Triển khai push notification
                    throw new Exception('Kênh push notification chưa được triển khai.');
                default:
                    throw new Exception("Kênh thông báo không hỗ trợ: {$channel}.");
            }
        }catch (Exception $e){
            $this->logError('sendCustomNotification', [
                'channel'    => $channel,
                'recipients' => $recipients,
                'message'    => $message,
                'options'    => $options,
            ], $e);
            throw new BaseException("Không thể gửi thông báo qua kênh {$channel}.", 500, [], [],
                $e);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (title, message, recipients, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in NotificationService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => Auth::id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}