<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * EmailService - Lớp tiện ích quản lý gửi email
 *
 * Tính năng:
 * - Gửi email với template động và dữ liệu tùy chỉnh
 * - Hỗ trợ gửi bất đồng bộ qua QueueService
 * - Gửi email hàng loạt cho nhiều người nhận
 * - Thông báo kết quả qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class EmailService{

    /**
     * Dịch vụ thông báo
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Dịch vụ hàng đợi
     *
     * @var QueueService
     */
    protected $queueService;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param NotificationService $notificationService
     * @param QueueService        $queueService
     */
    public function __construct(
        NotificationService $notificationService,
        QueueService $queueService){
        $this->notificationService = $notificationService;
        $this->queueService        = $queueService;
    }

    /**
     * Gửi email
     *
     * @param string|array $to          Địa chỉ người nhận (email hoặc mảng email)
     * @param string       $subject     Tiêu đề email
     * @param string       $view        Template Blade
     * @param array        $data        Dữ liệu cho template
     * @param bool         $queue       Sử dụng queue hay không
     * @param array        $cc          Địa chỉ CC
     * @param array        $bcc         Địa chỉ BCC
     * @param array        $attachments File đính kèm (path => name)
     *
     * @return bool
     * @throws BaseException
     */
    public function send(
        $to,
        string $subject,
        string $view,
        array $data = [],
        bool $queue = FALSE,
        array $cc = [],
        array $bcc = [],
        array $attachments = []
    )
    : bool{
        try{
            // Validate email
            $to        = is_array($to) ? $to : [$to];
            $validator = Validator::make(['to' => $to, 'cc' => $cc, 'bcc' => $bcc], [
                'to.*'  => ['required', 'email'],
                'cc.*'  => ['nullable', 'email'],
                'bcc.*' => ['nullable', 'email'],
            ]);

            if ($validator->fails()){
                throw new Exception($validator->errors()->first());
            }

            // Tạo mailable
            $mailable = new class($subject, $view, $data, $cc, $bcc, $attachments) extends Mailable{

                public function __construct($subject, $view, $data, $cc, $bcc, $attachments){
                    $this->subject($subject)
                         ->view($view)
                         ->with($data);

                    if ($cc){
                        $this->cc($cc);
                    }
                    if ($bcc){
                        $this->bcc($bcc);
                    }
                    foreach ($attachments as $path => $name){
                        $this->attach($path, ['as' => $name]);
                    }
                }
            };

            // Gửi email
            if ($queue){
                $this->queueService->dispatch(function () use ($mailable, $to){
                    Mail::to($to)->queue($mailable);
                }, 'emails');
            }else{
                Mail::to($to)->send($mailable);
            }

            $this->notificationService->sendFilamentNotification(
                'Gửi email thành công',
                "Email đã được gửi tới: " . implode(', ', $to),
                'success'
            );

            Log::info('Email sent', [
                'to'      => $to,
                'subject' => $subject,
                'view'    => $view,
                'user_id' => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('send', [
                'to'      => $to,
                'subject' => $subject,
                'view'    => $view,
                'queue'   => $queue,
            ], $e);
            throw new BaseException('Không thể gửi email.', 500, [], [], $e);
        }
    }

    /**
     * Gửi email hàng loạt
     *
     * @param Collection|array $recipients Danh sách người nhận (email => data)
     * @param string           $subject    Tiêu đề email
     * @param string           $view       Template Blade
     * @param bool             $queue      Sử dụng queue hay không
     * @param array            $commonData Dữ liệu chung cho template
     *
     * @return bool
     * @throws BaseException
     */
    public function sendBulk(
        $recipients,
        string $subject,
        string $view,
        bool $queue = TRUE,
        array $commonData = [])
    : bool{
        try{
            $recipients = $recipients instanceof Collection ? $recipients : collect($recipients);
            $failed     = [];

            foreach ($recipients as $email => $recipientData){
                try{
                    $data = array_merge($commonData,
                        is_array($recipientData) ? $recipientData : ['recipient' => $recipientData]);
                    $this->send($email, $subject, $view, $data, $queue);
                }catch (Exception $e){
                    $failed[] = $email;
                    Log::warning("Failed to send email to {$email}: {$e->getMessage()}");
                }
            }

            if ($failed){
                $this->notificationService->sendFilamentNotification(
                    'Gửi email hàng loạt có lỗi',
                    'Không thể gửi email tới: ' . implode(', ', $failed),
                    'warning'
                );

                return FALSE;
            }

            $this->notificationService->sendFilamentNotification(
                'Gửi email hàng loạt thành công',
                'Đã gửi email tới ' . $recipients->count() . ' người nhận.',
                'success'
            );

            Log::info('Bulk email sent', [
                'recipient_count' => $recipients->count(),
                'subject'         => $subject,
                'view'            => $view,
                'user_id'         => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('sendBulk', [
                'recipient_count' => $recipients->count(),
                'subject'         => $subject,
                'view'            => $view,
                'queue'           => $queue,
            ], $e);
            throw new BaseException('Không thể gửi email hàng loạt.', 500, [], [], $e);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (to, subject, view, queue, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in EmailService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}