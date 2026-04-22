<?php

namespace App\Base\Filament\Widgets;

use App\Base\Exceptions\BaseException;
use App\Base\Services\CacheService;
use App\Base\Services\LogService;
use App\Base\Services\NotificationService;
use App\Base\Services\PermissionService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * BaseWidget - Lớp cơ sở cho các widget Filament
 *
 * Tính năng:
 * - Chuẩn hóa logic widget (thống kê, biểu đồ)
 * - Tối ưu truy vấn với caching và lazy loading
 * - Tích hợp PermissionService, NotificationService, CacheService
 * - Hỗ trợ format dữ liệu (currency, percentage, date) và relationship
 * - Hỗ trợ biểu đồ Chart.js
 * - Xử lý lỗi với BaseException
 */
abstract class BaseWidget extends Widget{

    /**
     * Quyền yêu cầu để xem widget
     *
     * @var string|null
     */
    protected static ?string $requiredPermission = NULL;

    /**
     * Thời gian cache (giây)
     *
     * @var int
     */
    protected int $cacheTtl = 3600;

    /**
     * Dịch vụ cache
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Dịch vụ log
     *
     * @var LogService
     */
    protected $logService;

    /**
     * Constructor
     */
    public function __construct(){
        $this->cacheService = app(CacheService::class);
        $this->logService   = app(LogService::class);
    }

    /**
     * Kiểm tra quyền xem widget
     *
     * @return bool
     */
    public static function canView()
    : bool{
        if (static::$requiredPermission && !app(PermissionService::class)->hasPermission(static::$requiredPermission)){
            // Không thể gửi notification trong context static, ghi log thay thế
            app(LogService::class)->logWarning('Permission denied for widget: ' . static::class, [
                'permission' => static::$requiredPermission,
                'user_id'    => auth()->id() ?? 'guest',
            ]);

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Lấy dữ liệu widget với caching
     *
     * @return array
     * @throws BaseException
     */
    protected function getData()
    : array{
        try{
            $cacheKey = 'widget_' . static::class . '_' . (auth()->id() ?? 'guest');
            $data     = $this->cacheService->remember($cacheKey, $this->cacheTtl, function (){
                return $this->fetchData();
            });

            $this->logService->logInfo("Widget data fetched: " . static::class, [
                'cache_key' => $cacheKey,
                'user_id'   => auth()->id() ?? 'system',
            ]);

            return $data;
        }catch (\Exception $e){
            $this->handleError($e);
            throw new BaseException('Không thể tải dữ liệu widget.', 500, [], [], $e);
        }
    }

    /**
     * Lấy dữ liệu từ database (phải ghi đè trong lớp con)
     *
     * @return array
     */
    abstract protected function fetchData()
    : array;

    /**
     * Tối ưu truy vấn
     *
     * @param Builder $query
     * @param array   $relationships
     * @param array   $selectColumns
     *
     * @return Builder
     */
    protected function optimizeQuery(
        Builder $query,
        array $relationships = [],
        array $selectColumns = [])
    : Builder{
        // Chỉ load relationship cần thiết
        if ($relationships){
            $query->with($relationships);
        }

        // Select cột cụ thể để giảm dữ liệu
        if ($selectColumns){
            $query->select($selectColumns);
        }

        return $query;
    }

    /**
     * Format dữ liệu
     *
     * @param mixed  $value Giá trị
     * @param string $type  Loại định dạng (currency, percentage, date, number)
     *
     * @return string
     */
    protected function formatData($value, string $type = 'text')
    : string{
        switch ($type){
            case 'currency':
                return number_format($value, 0, ',', '.') . ' VND';
            case 'percentage':
                return number_format($value, 2) . '%';
            case 'date':
                return Carbon::parse($value)->format('d/m/Y');
            case 'number':
                return number_format($value, 2);
            default:
                return (string) $value;
        }
    }

    /**
     * Xử lý relationship
     *
     * @param string                              $relation      Tên relationship
     * @param \Illuminate\Database\Eloquent\Model $record        Bản ghi
     * @param string                              $displayColumn Cột hiển thị
     *
     * @return string
     */
    protected function formatRelation(string $relation, $record, string $displayColumn = 'name')
    : string{
        $related = $record->$relation;
        if ($related instanceof \Illuminate\Database\Eloquent\Collection){
            return $related->pluck($displayColumn)->implode(', ');
        }

        return $related ? $related->$displayColumn : '-';
    }

    /**
     * Tạo cấu hình biểu đồ
     *
     * @param array  $data Dữ liệu biểu đồ
     * @param string $type Loại biểu đồ (bar, line, pie)
     *
     * @return array
     */
    protected function buildChartConfig(array $data, string $type = 'bar')
    : array{
        $labels   = Arr::get($data, 'labels', []);
        $datasets = Arr::get($data, 'datasets', []);

        return [
            'type'    => $type,
            'data'    => [
                'labels'   => $labels,
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => TRUE,
                'scales'     => [
                    'y' => ['beginAtZero' => TRUE],
                ],
            ],
        ];
    }

    /**
     * Xử lý lỗi
     *
     * @param \Exception $exception
     *
     * @return void
     */
    protected function handleError(\Exception $exception)
    : void{
        $message = "Error in widget " . static::class . ": {$exception->getMessage()}";
        $this->logService->logCritical($message, [
            'widget'  => static::class,
            'user_id' => auth()->id() ?? 'system',
            'trace'   => $exception->getTraceAsString(),
        ]);

        app(NotificationService::class)->sendFilamentNotification(
            'Lỗi',
            'Không thể tải widget.',
            'error'
        );
    }
}