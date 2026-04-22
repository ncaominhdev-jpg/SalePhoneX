<?php

namespace App\Base\Filament\Resources\Pages;

use App\Base\Services\BaseService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

/**
 * BaseListRecords - Lớp cơ sở cho trang danh sách bản ghi trong Filament
 *
 * Tính năng:
 * - Cấu hình bảng danh sách bản ghi với cột và bộ lọc
 * - Hỗ trợ tìm kiếm, phân trang, và sắp xếp
 * - Tích hợp hành động hàng loạt (xóa, khôi phục)
 * - Tùy chỉnh giao diện và thông báo
 * - Tích hợp với BaseService để lấy dữ liệu
 */
class BaseListRecords extends ListRecords{

    /**
     * Dịch vụ liên kết với trang
     *
     * @var BaseService|null
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
     * @param BaseService|null $service
     */
    public function __construct(BaseService $service = NULL){
        $this->service = $service;
        parent::__construct();
    }

    /**
     * Cấu hình bảng danh sách
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table)
    : Table{
        try{
            $table = static::$resource::table($table);

            // Tùy chỉnh thêm nếu cần
            $table->defaultSort('created_at', 'desc');

            return $table;
        }catch (Exception $e){
            Log::error("Failed to configure table for {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->throwException('Không thể cấu hình bảng danh sách.', 500, $e);
        }
    }

    /**
     * Lấy dữ liệu cho bảng
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function getTableQuery(){
        try{
            if (!$this->service){
                return static::$resource::getEloquentQuery()
                                        ->paginate($this->getTableRecordsPerPage());
            }

            $filters   = $this->getTableFilters();
            $relations = static::$resource::getRelations();

            return $this->service->getPaginated($filters, $this->getTableRecordsPerPage(),
                $relations);
        }catch (Exception $e){
            Log::error("Failed to get table data for {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'filters' => $filters ?? [],
                    'user_id' => auth()->id() ?? 'system',
                ]);
            $this->throwException('Không thể lấy dữ liệu bảng.', 500, $e);
        }
    }

    /**
     * Lấy bộ lọc từ bảng
     *
     * @return array
     */
    protected function getTableFilters()
    : array{
        $filters = [];

        foreach ($this->getTable()->getFilters() as $filter){
            $filterName  = $filter->getName();
            $filterState = $this->getTable()->getFilterState($filterName);

            if ($filterState !== NULL){
                $filters[$filterName] = $filterState;
            }
        }

        // Thêm tìm kiếm nếu có
        if ($search = $this->getTable()->getSearch()){
            $filters['search'] = $search;
        }

        // Thêm sắp xếp nếu có
        if ($sortColumn = $this->getTable()->getSortColumn()){
            $filters['sort_by']        = $sortColumn;
            $filters['sort_direction'] = $this->getTable()->getSortDirection();
        }

        return $filters;
    }

    /**
     * Gửi thông báo Filament
     *
     * @param string $title
     * @param string $message
     * @param string $type ('success', 'error', 'warning', 'info')
     *
     * @return void
     */
    protected function sendNotification(string $title, string $message, string $type = 'success')
    : void{
        try{
            Notification::make()
                        ->title($title)
                        ->body($message)
                        ->{$type}()
                        ->send();
        }catch (Exception $e){
            Log::error("Failed to send notification in {$this->getResourceName()}: {$e->getMessage()}",
                [
                    'title'   => $title,
                    'message' => $message,
                    'user_id' => auth()->id() ?? 'system',
                ]);
        }
    }

    /**
     * Lấy tên tài nguyên
     *
     * @return string
     */
    protected function getResourceName()
    : string{
        return class_basename(static::$resource);
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     *
     * @return void
     * @throws Exception
     */
    protected function throwException(string $message, int $code = 400, ?Exception $previous = NULL)
    : void{
        if ($this->throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, [], [], $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}