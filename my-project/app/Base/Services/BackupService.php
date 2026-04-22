<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Tasks\Backup\BackupJob;
use Spatie\Backup\Tasks\Cleanup\CleanupJob;

/**
 * BackupService - Lớp tiện ích quản lý sao lưu hệ thống
 *
 * Tính năng:
 * - Tạo bản sao lưu database và file
 * - Lên lịch sao lưu tự động
 * - Xóa bản sao lưu cũ
 * - Thông báo kết quả qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class BackupService{

    /**
     * Dịch vụ thông báo
     *
     * @var NotificationService
     */
    protected $notificationService;

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
     */
    public function __construct(NotificationService $notificationService){
        $this->notificationService = $notificationService;

        // Kiểm tra package Spatie\Laravel-Backup
        if (!class_exists(\Spatie\Backup\BackupJob::class)){
            throw new Exception('Package Spatie\Laravel-Backup chưa được cài đặt. Vui lòng chạy: composer require spatie/laravel-backup');
        }

        // Đăng ký listener cho sự kiện backup
        $this->registerBackupListeners();
    }

    /**
     * Tạo bản sao lưu
     *
     * @param array $options Tùy chọn sao lưu (databases, files, disk)
     *
     * @return bool
     * @throws BaseException
     */
    public function createBackup(array $options = [])
    : bool{
        try{
            $backupJob = new BackupJob();

            // Cấu hình tùy chọn
            if (!empty($options['databases'])){
                $backupJob->onlyDbName($options['databases']);
            }
            if (!empty($options['files'])){
                $backupJob->onlyFiles($options['files']);
            }
            if (!empty($options['disk'])){
                $backupJob->setBackupDestinations([BackupDestination::fromDisk($options['disk'])]);
            }

            // Chạy sao lưu
            $backupJob->run();

            Log::info('Backup created successfully', [
                'options' => $options,
                'user_id' => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('createBackup', [
                'options' => $options,
            ], $e);
            throw new BaseException('Không thể tạo bản sao lưu.', 500, [], [], $e);
        }
    }

    /**
     * Xóa bản sao lưu cũ
     *
     * @param string $disk    Disk lưu trữ backup
     * @param array  $options Tùy chọn cleanup (keep_days, keep_number)
     *
     * @return bool
     * @throws BaseException
     */
    public function cleanupBackups(string $disk, array $options = [])
    : bool{
        try{
            $cleanupJob = new CleanupJob($disk);

            // Cấu hình tùy chọn
            if (!empty($options['keep_days'])){
                $cleanupJob->setMaximumAgeInDays($options['keep_days']);
            }
            if (!empty($options['keep_number'])){
                $cleanupJob->setMaximumNumberOfBackups($options['keep_number']);
            }

            // Chạy cleanup
            $cleanupJob->run();

            Log::info('Old backups cleaned successfully', [
                'disk'    => $disk,
                'options' => $options,
                'user_id' => auth()->id() ?? 'system',
            ]);

            return TRUE;
        }catch (Exception $e){
            $this->logError('cleanupBackups', [
                'disk'    => $disk,
                'options' => $options,
            ], $e);
            throw new BaseException('Không thể xóa bản sao lưu cũ.', 500, [], [], $e);
        }
    }

    /**
     * Đăng ký listener cho sự kiện backup
     *
     * @return void
     */
    protected function registerBackupListeners()
    : void{
        \Illuminate\Support\Facades\Event::listen(BackupWasSuccessful::class,
            function (BackupWasSuccessful $event){
                try{
                    $this->notificationService->sendFilamentNotification(
                        'Sao lưu thành công',
                        "Bản sao lưu đã được tạo: {$event->backupDestination->backupName()}",
                        'success'
                    );
                    Log::info('Backup successful', [
                        'backup_name' => $event->backupDestination->backupName(),
                        'user_id'     => auth()->id() ?? 'system',
                    ]);
                }catch (Exception $e){
                    Log::error("Failed to handle BackupWasSuccessful event: {$e->getMessage()}");
                }
            });

        \Illuminate\Support\Facades\Event::listen(BackupHasFailed::class,
            function (BackupHasFailed $event){
                try{
                    $this->notificationService->sendFilamentNotification(
                        'Sao lưu thất bại',
                        "Sao lưu thất bại: {$event->exception->getMessage()}",
                        'error'
                    );
                    Log::error('Backup failed', [
                        'exception' => $event->exception->getMessage(),
                        'user_id'   => auth()->id() ?? 'system',
                    ]);
                }catch (Exception $e){
                    Log::error("Failed to handle BackupHasFailed event: {$e->getMessage()}");
                }
            });
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (options, disk, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in BackupService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}