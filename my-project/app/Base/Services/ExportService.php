<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ExportService - Lớp tiện ích xử lý xuất dữ liệu (Excel, CSV, PDF)
 *
 * Tính năng:
 * - Xuất dữ liệu sang Excel/CSV với Maatwebsite\Excel
 * - Xuất PDF với Barryvdh\DomPDF
 * - Tùy chỉnh cột và dữ liệu dựa trên tài nguyên
 * - Xử lý lỗi và ghi log với BaseException
 * - Tích hợp với BaseService để lấy dữ liệu
 */
class ExportService{

    /**
     * Dịch vụ cơ sở để lấy dữ liệu
     *
     * @var BaseService
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
     * @param BaseService $service
     */
    public function __construct(BaseService $service){
        $this->service = $service;
    }

    /**
     * Xuất dữ liệu sang Excel hoặc CSV
     *
     * @param array  $filters   Bộ lọc dữ liệu
     * @param array  $columns   Cột cần xuất (key => label)
     * @param string $format    Định dạng (excel, csv)
     * @param string $filename  Tên file xuất
     * @param array  $relations Quan hệ cần preload
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws BaseException
     */
    public function exportToExcelOrCsv(
        array $filters = [],
        array $columns = [],
        string $format = 'excel',
        string $filename = 'export',
        array $relations = []){
        try{
            // Validate định dạng
            if (!in_array($format, ['excel', 'csv'])){
                throw new Exception("Định dạng không hợp lệ: {$format}.");
            }

            // Lấy dữ liệu từ BaseService
            $data = $this->service->getPaginated($filters, 10000, $relations)->getCollection();

            // Chuẩn hóa dữ liệu cho export
            $exportData = $this->formatDataForExport($data, $columns);

            // Tạo class export động
            $exportClass = new class($exportData, $columns) implements FromCollection, WithHeadings{

                private $data;
                private $columns;

                public function __construct(Collection $data, array $columns){
                    $this->data    = $data;
                    $this->columns = $columns;
                }

                public function collection(){
                    return $this->data;
                }

                public function headings()
                : array{
                    return array_values($this->columns);
                }
            };

            // Xuất file
            $extension = $format === 'excel' ? 'xlsx' : 'csv';

            return Excel::download($exportClass, "{$filename}.{$extension}");
        }catch (Exception $e){
            $this->logError('exportToExcelOrCsv', [
                'filters'  => $filters,
                'columns'  => $columns,
                'format'   => $format,
                'filename' => $filename,
            ], $e);
            throw new BaseException("Không thể xuất dữ liệu sang {$format}.", 500, [], [], $e);
        }
    }

    /**
     * Xuất dữ liệu sang PDF
     *
     * @param array  $filters   Bộ lọc dữ liệu
     * @param array  $columns   Cột cần xuất (key => label)
     * @param string $filename  Tên file xuất
     * @param array  $relations Quan hệ cần preload
     * @param string $view      Blade view để render PDF
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws BaseException
     */
    public function exportToPdf(
        array $filters = [],
        array $columns = [],
        string $filename = 'export',
        array $relations = [],
        string $view = 'base::exports.pdf'){
        try{
            // Lấy dữ liệu từ BaseService
            $data = $this->service->getPaginated($filters, 10000, $relations)->getCollection();

            // Chuẩn hóa dữ liệu cho export
            $exportData = $this->formatDataForExport($data, $columns);

            // Tạo PDF
            $pdf = Pdf::loadView($view, [
                'data'    => $exportData,
                'columns' => $columns,
                'title'   => $filename,
            ]);

            // Xuất file
            return $pdf->download("{$filename}.pdf");
        }catch (Exception $e){
            $this->logError('exportToPdf', [
                'filters'  => $filters,
                'columns'  => $columns,
                'filename' => $filename,
                'view'     => $view,
            ], $e);
            throw new BaseException('Không thể xuất dữ liệu sang PDF.', 500, [], [], $e);
        }
    }

    /**
     * Chuẩn hóa dữ liệu cho export
     *
     * @param Collection $data    Dữ liệu đầu vào
     * @param array      $columns Cột cần xuất (key => label)
     *
     * @return Collection
     */
    protected function formatDataForExport(Collection $data, array $columns)
    : Collection{
        return $data->map(function ($item) use ($columns){
            $row = [];
            foreach ($columns as $key => $label){
                // Xử lý quan hệ và định dạng đặc biệt
                if (str_contains($key, '.')){
                    $value = data_get($item, $key, '-');
                }else{
                    $value = $item->$key ?? '-';
                }

                // Định dạng đặc biệt cho một số trường
                if ($key === 'is_active'){
                    $value = $value ? 'Hoạt động' : 'Không hoạt động';
                }elseif ($key === 'status'){
                    $value = \Modules\Base\Enums\StatusEnum::tryFrom($value)?->getLabel() ?? $value;
                }elseif (in_array($key, ['created_at', 'updated_at'])){
                    $value = $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
                }

                $row[$key] = $value;
            }

            return $row;
        });
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (filters, columns, format, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in ExportService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}