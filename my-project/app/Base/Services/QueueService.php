<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * QueueService - Lớp tiện ích quản lý hàng đợi (job queue)
 *
 * Tính năng:
 * - Gửi job vào hàng đợi Laravel (email, xuất dữ liệu, v.v.)
 * - Theo dõi trạng thái job (chờ, chạy, hoàn thành, thất bại)
 * - Thông báo kết quả job qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class QueueService{

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

        // Đăng ký listener cho sự kiện job
        $this->registerJobListeners();
    }

    /**
     * Gửi job vào hàng đợi
     *
     * @param ShouldQueue $job   Job cần gửi
     * @param string      $queue Tên hàng đợi (mặc định: null)
     *
     * @return string ID của job
     * @throws BaseException
     */
    public function dispatchJob(ShouldQueue $job, string $queue = NULL)
    : string{
        try{
            $jobId      = Str::uuid()->toString();
            $job->jobId = $jobId; // Gán ID tùy chỉnh cho job

            if ($queue){
                $job->onQueue($queue);
            }

            Queue::push($job);

            Log::info("Job dispatched: {$jobId}", [
                'job_class' => get_class($job),
                'queue'     => $queue ?? 'default',
                'user_id'   => auth()->id() ?? 'system',
            ]);

            return $jobId;
        }catch (Exception $e){
            $this->logError('dispatchJob', [
                'job_class' => get_class($job),
                'queue'     => $queue,
            ], $e);
            throw new BaseException('Không thể gửi job vào hàng đợi.', 500, [], [], $e);
        }
    }

    /**
     * Tạo job xuất dữ liệu bất đồng bộ
     *
     * @param ExportService $exportService Dịch vụ xuất dữ liệu
     * @param array         $filters       Bộ lọc dữ liệu
     * @param array         $columns       Cột cần xuất
     * @param string        $format        Định dạng (excel, csv)
     * @param string        $filename      Tên file
     * @param array         $relations     Quan hệ cần preload
     * @param string        $queue         Tên hàng đợi
     *
     * @return string ID của job
     * @throws BaseException
     */
    public function dispatchExportJob(
        ExportService $exportService,
        array $filters,
        array $columns,
        string $format,
        string $filename,
        array $relations = [],
        string $queue = 'exports'
    )
    : string{
        try{
            $job = new class($exportService, $filters, $columns, $format, $filename, $relations) implements ShouldQueue{

                use Queueable;

                public $jobId;
                private $exportService;
                private $filters;
                private $columns;
                private $format;
                private $filename;
                private $relations;

                public function __construct(
                    $exportService,
                    $filters,
                    $columns,
                    $format,
                    $filename,
                    $relations){
                    $this->exportService = $exportService;
                    $this->filters       = $filters;
                    $this->columns       = $columns;
                    $this->format        = $format;
                    $this->filename      = $filename;
                    $this->relations     = $relations;
                }

                public function handle(){
                    $this->exportService->exportToExcelOrCsv($this->filters, $this->columns,
                        $this->format, $this->filename, $this->relations);
                }
            };

            return $this->dispatchJob($job, $queue);
        }catch (Exception $e){
            $this->logError('dispatchExportJob', [
                'filters'  => $filters,
                'format'   => $format,
                'filename' => $filename,
            ], $e);
            throw new BaseException('Không thể tạo job xuất dữ liệu.', 500, [], [], $e);
        }
    }

    /**
     * Tạo job nhập dữ liệu bất đồng bộ
     *
     * @param ImportService                 $importService      Dịch vụ nhập dữ liệu
     * @param \Illuminate\Http\UploadedFile $file               File Excel/CSV
     * @param array                         $validationRules    Quy tắc validation
     * @param array                         $validationMessages Thông điệp validation
     * @param array                         $fieldMapping       Ánh xạ cột
     * @param string                        $queue              Tên hàng đợi
     *
     * @return string ID của job
     * @throws BaseException
     */
    public function dispatchImportJob(
        ImportService $importService,
        $file,
        array $validationRules,
        array $validationMessages,
        array $fieldMapping,
        string $queue = 'imports'
    )
    : string{
        try{
            $job = new class($importService, $file, $validationRules, $validationMessages, $fieldMapping) implements ShouldQueue{

                use Queueable;

                public $jobId;
                private $importService;
                private $file;
                private $validationRules;
                private $validationMessages;
                private $fieldMapping;

                public function __construct(
                    $importService,
                    $file,
                    $validationRules,
                    $validationMessages,
                    $fieldMapping){
                    $this->importService      = $importService;
                    $this->file               = $file;
                    $this->validationRules    = $validationRules;
                    $this->validationMessages = $validationMessages;
                    $this->fieldMapping       = $fieldMapping;
                }

                public function handle(){
                    $this->importService->importFromFile(
                        $this->file,
                        $this->validationRules,
                        $this->validationMessages,
                        $this->fieldMapping
                    );
                }
            };

            return $this->dispatchJob($job, $queue);
        }catch (Exception $e){
            $this->logError('dispatchImportJob', [
                'file'             => $file->getClientOriginalName(),
                'validation_rules' => $validationRules,
            ], $e);
            throw new BaseException('Không thể tạo job nhập dữ liệu.', 500, [], [], $e);
        }
    }

    /**
     * Đăng ký listener cho sự kiện job
     *
     * @return void
     */
    protected function registerJobListeners()
    : void{
        Queue::after(function (JobProcessed $event){
            try{
                $job   = unserialize($event->job->payload()['data']['command']);
                $jobId = $job->jobId ?? $event->job->getJobId();
                $this->notificationService->sendFilamentNotification(
                    'Job hoàn thành',
                    "Job {$jobId} đã hoàn thành thành công.",
                    'success'
                );
                Log::info("Job processed: {$jobId}", [
                    'job_class' => get_class($job),
                    'user_id'   => auth()->id() ?? 'system',
                ]);
            }catch (Exception $e){
                Log::error("Failed to handle JobProcessed event: {$e->getMessage()}");
            }
        });

        Queue::failing(function (JobFailed $event){
            try{
                $job   = unserialize($event->job->payload()['data']['command']);
                $jobId = $job->jobId ?? $event->job->getJobId();
                $this->notificationService->sendFilamentNotification(
                    'Job thất bại',
                    "Job {$jobId} đã thất bại: {$event->exception->getMessage()}",
                    'error'
                );
                Log::error("Job failed: {$jobId}", [
                    'job_class' => get_class($job),
                    'exception' => $event->exception->getMessage(),
                    'user_id'   => auth()->id() ?? 'system',
                ]);
            }catch (Exception $e){
                Log::error("Failed to handle JobFailed event: {$e->getMessage()}");
            }
        });
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (job_class, queue, file, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in QueueService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}