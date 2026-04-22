<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LogService - Lớp tiện ích quản lý log hệ thống
 *
 * Tính năng:
 * - Lọc và truy xuất log từ file hoặc database
 * - Xuất log sang Excel/CSV qua ExportService
 * - Phân tích lỗi để cung cấp thông tin chi tiết
 * - Thông báo lỗi nghiêm trọng qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 *  Cấu hình log database (tùy chọn):
 * php artisan make:migration create_logs_table --path=Modules/Base/Database/Migrations
 * <?php
 *
 * use Illuminate\Database\Migrations\Migration;
 * use Illuminate\Database\Schema\Blueprint;
 * use Illuminate\Support\Facades\Schema;
 *
 * class CreateLogsTable extends Migration
 * {
 * public function up()
 * {
 * Schema::create('logs', function (Blueprint $table) {
 * $table->id();
 * $table->string('level');
 * $table->text('message');
 * $table->timestamp('datetime');
 * $table->string('user_id')->nullable();
 * $table->json('context')->nullable();
 * });
 * }
 *
 * public function down()
 * {
 * Schema::dropIfExists('logs');
 * }
 * }
 * php artisan migrate
 * LOG_CHANNEL=database
 */
class LogService{

    /**
     * Dịch vụ xuất dữ liệu
     *
     * @var ExportService
     */
    protected $exportService;

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
     * @param ExportService       $exportService
     * @param NotificationService $notificationService
     */
    public function __construct(
        ExportService $exportService,
        NotificationService $notificationService){
        $this->exportService       = $exportService;
        $this->notificationService = $notificationService;
    }

    /**
     * Lấy log từ file hoặc database
     *
     * @param array  $filters Bộ lọc (level, date_from, date_to, user_id, message)
     * @param string $source  Nguồn log (file, database)
     * @param int    $limit   Giới hạn số log
     *
     * @return Collection
     * @throws BaseException
     */
    public function getLogs(array $filters = [], string $source = 'file', int $limit = 1000)
    : Collection{
        try{
            if ($source === 'database'){
                return $this->getLogsFromDatabase($filters, $limit);
            }

            return $this->getLogsFromFile($filters, $limit);
        }catch (Exception $e){
            $this->logError('getLogs', [
                'filters' => $filters,
                'source'  => $source,
                'limit'   => $limit,
            ], $e);
            throw new BaseException('Không thể lấy danh sách log.', 500, [], [], $e);
        }
    }

    /**
     * Xuất log sang file
     *
     * @param array  $filters  Bộ lọc (level, date_from, date_to, user_id, message)
     * @param string $source   Nguồn log (file, database)
     * @param string $format   Định dạng xuất (excel, csv)
     * @param string $filename Tên file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws BaseException
     */
    public function exportLogs(
        array $filters = [],
        string $source = 'file',
        string $format = 'excel',
        string $filename = 'logs')
    : \Symfony\Component\HttpFoundation\BinaryFileResponse{
        try{
            $logs    = $this->getLogs($filters, $source);
            $columns = [
                'datetime' => 'Thời gian',
                'level'    => 'Mức độ',
                'message'  => 'Thông điệp',
                'user_id'  => 'Người dùng',
                'context'  => 'Ngữ cảnh',
            ];

            // Định dạng dữ liệu
            $formattedData = $logs->map(function ($log){
                return [
                    'datetime' => \Carbon\Carbon::parse($log['datetime'])->format('d/m/Y H:i:s'),
                    'level'    => Str::upper($log['level']),
                    'message'  => $log['message'],
                    'user_id'  => $log['user_id'] ?? '-',
                    'context'  => json_encode($log['context'] ?? [], JSON_UNESCAPED_UNICODE),
                ];
            });

            // Tạo BaseService tạm thời để sử dụng ExportService
            $baseService = new class extends BaseService{

                public function getPaginated(
                    array $filters = [],
                    int $perPage = 15,
                    array $relations = [])
                : \Illuminate\Pagination\LengthAwarePaginator{
                    return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
                }
            };

            return $this->exportService->exportToExcelOrCsv([], $columns, $format, $filename, [],
                $formattedData);
        }catch (Exception $e){
            $this->logError('exportLogs', [
                'filters'  => $filters,
                'source'   => $source,
                'format'   => $format,
                'filename' => $filename,
            ], $e);
            throw new BaseException("Không thể xuất log sang {$format}.", 500, [], [], $e);
        }
    }

    /**
     * Ghi log lỗi nghiêm trọng và gửi thông báo
     *
     * @param string         $message   Thông điệp lỗi
     * @param array          $context   Ngữ cảnh
     * @param Exception|null $exception Ngoại lệ
     *
     * @return void
     * @throws BaseException
     */
    public function logCritical(string $message, array $context = [], ?Exception $exception = NULL)
    : void{
        try{
            Log::critical($message, array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception ? $exception->getTraceAsString() : NULL,
            ]));

            $this->notificationService->sendFilamentNotification(
                'Lỗi nghiêm trọng',
                $message,
                'error'
            );
        }catch (Exception $e){
            $this->logError('logCritical', [
                'message' => $message,
                'context' => $context,
            ], $e);
            throw new BaseException('Không thể ghi log lỗi nghiêm trọng.', 500, [], [], $e);
        }
    }

    /**
     * Lấy log từ database
     *
     * @param array $filters Bộ lọc
     * @param int   $limit   Giới hạn
     *
     * @return Collection
     */
    protected function getLogsFromDatabase(array $filters, int $limit)
    : Collection{
        $query = DB::table('logs');

        if (!empty($filters['level'])){
            $query->where('level', $filters['level']);
        }
        if (!empty($filters['date_from'])){
            $query->where('datetime', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])){
            $query->where('datetime', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_id'])){
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['message'])){
            $query->where('message', 'like', "%{$filters['message']}%");
        }

        return $query->orderByDesc('datetime')->take($limit)->get()->map(function ($log){
            return [
                'datetime' => $log->datetime,
                'level'    => $log->level,
                'message'  => $log->message,
                'user_id'  => $log->user_id,
                'context'  => json_decode($log->context, TRUE) ?? [],
            ];
        });
    }

    /**
     * Lấy log từ file
     *
     * @param array $filters Bộ lọc
     * @param int   $limit   Giới hạn
     *
     * @return Collection
     */
    protected function getLogsFromFile(array $filters, int $limit)
    : Collection{
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)){
            return collect([]);
        }

        $logs       = collect();
        $lines      = array_reverse(file($logFile));
        $currentLog = NULL;

        foreach ($lines as $line){
            if (preg_match('/^\[(.*?)\]\s+([^\.]+)\.([^\:]+)\:\s*(.*)/', $line, $matches)){
                if ($currentLog){
                    $logs->push($currentLog);
                }
                $currentLog = [
                    'datetime' => $matches[1],
                    'level'    => strtolower($matches[3]),
                    'message'  => $matches[4],
                    'user_id'  => NULL,
                    'context'  => [],
                ];
            }elseif ($currentLog){
                $currentLog['context'][] = trim($line);
            }

            if ($logs->count() >= $limit){
                break;
            }
        }

        if ($currentLog){
            $logs->push($currentLog);
        }

        return $logs->filter(function ($log) use ($filters){
            if (!empty($filters['level']) && $log['level'] !== strtolower($filters['level'])){
                return FALSE;
            }
            if (!empty($filters['date_from']) && $log['datetime'] < $filters['date_from']){
                return FALSE;
            }
            if (!empty($filters['date_to']) && $log['datetime'] > $filters['date_to']){
                return FALSE;
            }
            if (!empty($filters['user_id']) && $log['user_id'] != $filters['user_id']){
                return FALSE;
            }
            if (!empty($filters['message']) && !Str::contains($log['message'],
                    $filters['message'])){
                return FALSE;
            }

            return TRUE;
        })->take($limit);
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (filters, source, format, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in LogService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}