<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use App\Base\Models\AuditLog;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * AuditLogService - Lớp tiện ích quản lý và truy xuất log audit
 *
 * Tính năng:
 * - Truy xuất log audit theo bản ghi, người dùng, hoặc hành động
 * - Tích hợp với HasAuditLog để truy vấn bảng audit_logs
 * - Hỗ trợ xuất log audit sang Excel/CSV
 * - Xử lý lỗi và ghi log với BaseException
 */
class AuditLogService{

    /**
     * Dịch vụ xuất dữ liệu
     *
     * @var ExportService
     */
    protected $exportService;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param ExportService $exportService
     */
    public function __construct(ExportService $exportService){
        $this->exportService = $exportService;
    }

    /**
     * Lấy log audit theo bản ghi
     *
     * @param string $modelClass Tên class model (ví dụ: AcademicYear::class)
     * @param string $uuid       UUID của bản ghi
     * @param array  $filters    Bộ lọc bổ sung (action, user_id)
     * @param int    $perPage    Số bản ghi mỗi trang
     *
     * @return LengthAwarePaginator
     * @throws BaseException
     */
    public function getLogsByRecord(
        string $modelClass,
        string $uuid,
        array $filters = [],
        int $perPage = 15)
    : LengthAwarePaginator{
        try{
            $query = $this->buildLogQuery($modelClass, $uuid, $filters);

            return $query->paginate(max(1, min($perPage, config('base.default_per_page', 15))));
        }catch (Exception $e){
            $this->logError('getLogsByRecord', [
                'model_class' => $modelClass,
                'uuid'        => $uuid,
                'filters'     => $filters,
            ], $e);
            throw new BaseException('Không thể lấy log audit.', 500, [], [], $e);
        }
    }

    /**
     * Lấy log audit theo người dùng
     *
     * @param int   $userId  ID người dùng
     * @param array $filters Bộ lọc bổ sung (action, model_class)
     * @param int   $perPage Số bản ghi mỗi trang
     *
     * @return LengthAwarePaginator
     * @throws BaseException
     */
    public function getLogsByUser(int $userId, array $filters = [], int $perPage = 15)
    : LengthAwarePaginator{
        try{
            $query = AuditLog::query()->where('user_id', $userId);
            $this->applyFilters($query, $filters);

            return $query->paginate(max(1, min($perPage, config('base.default_per_page', 15))));
        }catch (Exception $e){
            $this->logError('getLogsByUser', [
                'user_id' => $userId,
                'filters' => $filters,
            ], $e);
            throw new BaseException('Không thể lấy log audit theo người dùng.', 500, [], [], $e);
        }
    }

    /**
     * Xuất log audit sang Excel/CSV
     *
     * @param string|null $modelClass Tên class model (nếu null, xuất tất cả)
     * @param string|null $uuid       UUID của bản ghi (nếu null, xuất tất cả)
     * @param array       $filters    Bộ lọc (action, user_id)
     * @param string      $format     Định dạng (excel, csv)
     * @param string      $filename   Tên file xuất
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws BaseException
     */
    public function exportLogs(
        ?string $modelClass = NULL,
        ?string $uuid = NULL,
        array $filters = [],
        string $format = 'excel',
        string $filename = 'audit_logs'){
        try{
            $query = $modelClass && $uuid ? $this->buildLogQuery($modelClass, $uuid,
                $filters) : AuditLog::query();
            $this->applyFilters($query, $filters);

            $data = $query->get()->map(function ($log){
                return [
                    'id'         => $log->id,
                    'model'      => $log->auditable_type,
                    'uuid'       => $log->auditable_uuid,
                    'action'     => $log->action,
                    'user_id'    => $log->user_id,
                    'user_name'  => $log->user ? $log->user->name : '-',
                    'changes'    => json_encode($log->changes, JSON_UNESCAPED_UNICODE),
                    'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-',
                ];
            });

            $columns = [
                'id'         => 'ID',
                'model'      => 'Model',
                'uuid'       => 'UUID',
                'action'     => 'Hành động',
                'user_id'    => 'ID người dùng',
                'user_name'  => 'Tên người dùng',
                'changes'    => 'Thay đổi',
                'created_at' => 'Ngày tạo',
            ];

            // Tạm thời khởi tạo BaseService giả để ExportService hoạt động
            $baseService = new class extends BaseService{

                public function __construct(){
                    parent::__construct(new \Modules\Base\Models\BaseModel());
                }

                public function getPaginated(
                    array $filters = [],
                    int $perPage = 15,
                    array $relations = [])
                : LengthAwarePaginator{
                    return new LengthAwarePaginator([], 0, $perPage);
                }
            };

            return $this->exportService->exportToExcelOrCsv([], $columns, $format, $filename, [],
                $data);
        }catch (Exception $e){
            $this->logError('exportLogs', [
                'model_class' => $modelClass,
                'uuid'        => $uuid,
                'filters'     => $filters,
                'format'      => $format,
            ], $e);
            throw new BaseException("Không thể xuất log audit sang {$format}.", 500, [], [], $e);
        }
    }

    /**
     * Xây dựng query log audit cho bản ghi
     *
     * @param string $modelClass Tên class model
     * @param string $uuid       UUID của bản ghi
     * @param array  $filters    Bộ lọc bổ sung
     *
     * @return Builder
     */
    protected function buildLogQuery(string $modelClass, string $uuid, array $filters)
    : Builder{
        $query = AuditLog::query()
                         ->where('auditable_type', $modelClass)
                         ->where('auditable_uuid', $uuid);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Áp dụng bộ lọc cho query log audit
     *
     * @param Builder $query   Query Eloquent
     * @param array   $filters Bộ lọc (action, user_id)
     *
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters)
    : void{
        if (!empty($filters['action'])){
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['user_id'])){
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['model_class'])){
            $query->where('auditable_type', $filters['model_class']);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (model_class, uuid, filters, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in AuditLogService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}