<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/**
 * CronService - Lớp tiện ích quản lý tác vụ định kỳ
 *
 * Tính năng:
 * - Đăng ký và quản lý các tác vụ định kỳ với Laravel Scheduler
 * - Thực thi tác vụ thủ công để kiểm tra hoặc debug
 * - Ghi log kết quả qua LogService
 * - Thông báo lỗi hoặc kết quả qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class CronService{

    /**
     * Dịch vụ thông báo
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Dịch vụ log
     *
     * @var LogService
     */
    protected $logService;

    /**
     * Dịch vụ hàng đợi
     *
     * @var QueueService
     */
    protected $queueService;

    /**
     * Dịch vụ email
     *
     * @var EmailService
     */
    protected $emailService;

    /**
     * Dịch vụ sao lưu
     *
     * @var BackupService
     */
    protected $backupService;

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
     * @param LogService          $logService
     * @param QueueService        $queueService
     * @param EmailService        $emailService
     * @param BackupService       $backupService
     */
    public function __construct(
        NotificationService $notificationService,
        LogService $logService,
        QueueService $queueService,
        EmailService $emailService,
        BackupService $backupService
    ){
        $this->notificationService = $notificationService;
        $this->logService          = $logService;
        $this->queueService        = $queueService;
        $this->emailService        = $emailService;
        $this->backupService       = $backupService;
    }

    /**
     * Đăng ký các tác vụ định kỳ
     *
     * @return void
     */
    public function registerTasks()
    : void{
        try{
            // Sao lưu hệ thống hàng ngày lúc 2:00 sáng
            Schedule::call(function (){
                $this->backupService->createBackup([
                    'databases' => [config('database.default')],
                    'disk'      => 'backups',
                ]);
            })->dailyAt('02:00')->name('daily-backup');

            // Xóa bản sao lưu cũ hàng ngày lúc 3:00 sáng
            Schedule::call(function (){
                $this->backupService->cleanupBackups('backups', [
                    'keep_days'   => 7,
                    'keep_number' => 10,
                ]);
            })->dailyAt('03:00')->name('cleanup-backups');

            // Gửi email nhắc nhở sinh viên đóng học phí hàng tuần
            Schedule::call(function (){
                $this->queueService->dispatch(function (){
                    $students = \Modules\Student\Models\Student::where('due_date', '<',
                        Carbon::now()->addDays(7))
                                                               ->get(['email', 'name']);
                    $this->emailService->sendBulk(
                        $students->mapWithKeys(fn(
                            $student) => [$student->email => ['name' => $student->name]]),
                        'Nhắc nhở đóng học phí',
                        'emails.notification',
                        TRUE,
                        ['message' => 'Vui lòng đóng học phí trước hạn chót.']
                    );
                }, 'emails');
            })->weeklyOn(1, '08:00')->name('remind-tuition');

            // Xóa log cũ hàng tháng
            Schedule::call(function (){
                $this->clearOldLogs();
            })->monthlyOn(1, '04:00')->name('clear-old-logs');

            Log::info('Cron tasks registered successfully', [
                'tasks'   => Schedule::all()->pluck('name')->toArray(),
                'user_id' => auth()->id() ?? 'system',
            ]);
        }catch (Exception $e){
            $this->handleError('registerTasks', [], $e);
            throw new BaseException('Không thể đăng ký tác vụ định kỳ.', 500, [], [], $e);
        }
    }

    /**
     * Thực thi tác vụ thủ công
     *
     * @param string $taskName Tên tác vụ
     *
     * @return bool
     * @throws BaseException
     */
    public function runTask(string $taskName)
    : bool{
        try{
            $tasks = [
                'daily-backup'    => fn() => $this->backupService->createBackup(['databases' => [config('database.default')], 'disk' => 'backups']),
                'cleanup-backups' => fn() => $this->backupService->cleanupBackups('backups',
                    ['keep_days' => 7, 'keep_number' => 10]),
                'remind-tuition'  => fn() => $this->queueService->dispatch(function (){
                    $students = \Modules\Student\Models\Student::where('due_date', '<',
                        Carbon::now()->addDays(7))
                                                               ->get(['email', 'name']);
                    $this->emailService->sendBulk(
                        $students->mapWithKeys(fn(
                            $student) => [$student->email => ['name' => $student->name]]),
                        'Nhắc nhở đóng học phí',
                        'emails.notification',
                        TRUE,
                        ['message' => 'Vui lòng đóng học phí trước hạn chót.']
                    );
                }, 'emails'),
                'clear-old-logs'  => fn() => $this->clearOldLogs(),
            ];

            if (!isset($tasks[$taskName])){
                throw new Exception("Tác vụ {$taskName} không tồn tại.");
            }

            $tasks[$taskName]();

            $this->notificationService->sendFilamentNotification(
                'Thực thi thành công',
                "Tác vụ {$taskName} đã được thực thi.",
                'success'
            );

            $this->logService->logCritical(
                "Cron task {$taskName} executed successfully",
                ['task' => $taskName, 'user_id' => auth()->id() ?? 'system']
            );

            return TRUE;
        }catch (Exception $e){
            $this->handleError('runTask', ['task_name' => $taskName], $e);
            throw new BaseException("Không thể thực thi tác vụ {$taskName}.", 500, [], [], $e);
        }
    }

    /**
     * Xóa log cũ
     *
     * @return void
     * @throws Exception
     */
    protected function clearOldLogs()
    : void{
        try{
            // Xóa log database
            if (config('logging.default') === 'database'){
                \Illuminate\Support\Facades\DB::table('logs')
                                              ->where('datetime', '<', Carbon::now()->subMonths(3))
                                              ->delete();
            }

            // Xóa log file
            $logPath = storage_path('logs');
            $files   = glob($logPath . '/laravel-*.log');
            foreach ($files as $file){
                if (Carbon::createFromTimestamp(filemtime($file))->lt(Carbon::now()->subMonths(3))){
                    \Illuminate\Support\Facades\File::delete($file);
                }
            }

            Log::info('Old logs cleared successfully', [
                'user_id' => auth()->id() ?? 'system',
            ]);
        }catch (Exception $e){
            throw new Exception("Failed to clear old logs: {$e->getMessage()}");
        }
    }

    /**
     * Xử lý lỗi và gửi thông báo
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function handleError(string $method, array $context, Exception $exception)
    : void{
        $message = "Error in CronService::{$method}: {$exception->getMessage()}";
        $context = array_merge($context, [
            'user_id' => auth()->id() ?? 'system',
            'trace'   => $exception->getTraceAsString(),
        ]);

        $this->logService->logCritical($message, $context, $exception);

        $this->notificationService->sendFilamentNotification(
            'Lỗi tác vụ định kỳ',
            $message,
            'error'
        );
    }
}