<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ReportService - Lớp tiện ích tạo và xuất báo cáo tùy chỉnh
 *
 * Tính năng:
 * - Tạo báo cáo dựa trên truy vấn động hoặc dữ liệu tổng hợp
 * - Xuất báo cáo sang Excel/CSV/PDF qua ExportService
 * - Hỗ trợ bộ lọc và định dạng dữ liệu
 * - Thông báo kết quả qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class ReportService{

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
     * Tạo báo cáo tổng hợp (ví dụ: thống kê)
     *
     * @param callable $dataCallback Callback để lấy dữ liệu tổng hợp
     * @param array    $columns      Cột cần xuất (key => label)
     * @param string   $format       Định dạng xuất (excel, csv, pdf)
     * @param string   $filename     Tên file báo cáo
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws BaseException
     */
    public function generateReportFromQuery(
        Builder $query,
        array $columns,
        array $filters = [],
        string $format = 'excel',
        string $filename = 'report',
        array $relations = []
    ){
        try{
            // Áp dụng bộ lọc
            $this->applyFilters($query, $filters);

            // Lấy dữ liệu
            $data = $query->with($relations)->get();

            // Định dạng dữ liệu
            $formattedData = $this->formatDataForReport($data, $columns);

            // Xuất báo cáo
            if ($format === 'pdf'){
                return $this->exportService->exportToPdf([], $columns, $filename, [],
                    'base::exports.report', $formattedData);
            }

            return $this->exportService->exportToExcelOrCsv([], $columns, $format, $filename, [],
                $formattedData);
        }catch (Exception $e){
            $this->logError('generateReportFromQuery', [
                'columns'  => $columns,
                'filters'  => $filters,
                'format'   => $format,
                'filename' => $filename,
            ], $e);
            throw new BaseException("Không thể tạo báo cáo sang {$format}.", 500, [], [], $e);
        }
    }

    /**
     * Định dạng dữ liệu cho báo cáo
     *
     * @param Collection $data    Dữ liệu đầu vào
     * @param array      $columns Cột cần xuất (key => label)
     *
     * @return Collection
     */
    protected function formatDataForReport(Collection $data, array $columns)
    : Collection{
        return $data->map(function ($item) use ($columns){
            $row = [];
            foreach ($columns as $key => $label){
                // Xử lý quan hệ và giá trị đặc biệt
                if (str_contains($key, '.')){
                    $value = data_get($item, $key, '-');
                }else{
                    $value = is_object($item) ? ($item->$key ?? '-') : ($item[$key] ?? '-');
                }

                // Định dạng đặc biệt
                if ($key === 'is_active'){
                    $value = $value ? 'Hoạt động' : 'Không hoạt động';
                }elseif ($key === 'status'){
                    $value = \Modules\Base\Enums\StatusEnum::tryFrom($value)?->getLabel() ?? $value;
                }elseif (in_array($key, ['created_at', 'updated_at'])){
                    $value = $value && $value !== '-' ? \Carbon\Carbon::parse($value)
                                                                      ->format('d/m/Y H:i') : '-';
                }elseif (is_numeric($value)){
                    $value = number_format($value, 2, ',', '.');
                }

                $row[$key] = $value;
            }

            return $row;
        });
    }

    /**
     * Áp dụng bộ lọc cho truy vấn
     *
     * @param Builder $query   Truy vấn Eloquent
     * @param array   $filters Bộ lọc
     *
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters)
    : void{
        foreach ($filters as $key => $value){
            if ($value !== NULL && $value !== ''){
                if (in_array($key, ['is_active', 'status'])){
                    $query->where($key, $value);
                }elseif (str_contains($key, '_at')){
                    $query->whereDate($key, $value);
                }else{
                    $query->where($key, 'like', "%{$value}%");
                }
            }
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (columns, filters, format, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in ReportService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}