<?php

namespace App\Base\Filament\Filters;

use App\Base\Exceptions\BaseException;
use App\Base\Services\CacheService;
use App\Base\Services\LogService;
use App\Base\Services\PermissionService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * BaseFilter - Lớp cơ sở cho các bộ lọc Filament
 *
 * Tính năng:
 * - Chuẩn hóa bộ lọc (text, select, date, relationship)
 * - Tối ưu truy vấn với caching và lazy loading
 * - Tích hợp PermissionService, CacheService, LogService
 * - Hỗ trợ format dữ liệu lọc (status, date)
 * - Xử lý lỗi với BaseException
 */
abstract class BaseFilter extends Filter{

    /**
     * Quyền yêu cầu để sử dụng bộ lọc
     *
     * @var string|null
     */
    protected static ?string $requiredPermission = NULL;

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
     * Dịch vụ quyền
     *
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * Constructor
     */
    public function __construct(){
        parent::__construct();
        $this->cacheService      = app(CacheService::class);
        $this->logService        = app(LogService::class);
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * Kiểm tra quyền sử dụng bộ lọc
     *
     * @return bool
     */
    public static function canView()
    : bool{
        if (static::$requiredPermission && !app(PermissionService::class)->hasPermission(static::$requiredPermission)){
            app(LogService::class)->logWarning('Permission denied for filter: ' . static::class, [
                'permission' => static::$requiredPermission,
                'user_id'    => auth()->id() ?? 'guest',
            ]);

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Tạo bộ lọc text
     *
     * @param string $name   Tên bộ lọc
     * @param string $column Cột database
     *
     * @return static
     */
    public static function makeTextFilter(string $name, string $column)
    : static{
        return static::make($name)
                     ->form([
                         TextInput::make($column)
                                  ->label(__("filter.{$name}"))
                                  ->placeholder(__("filter.{$name}_placeholder")),
                     ])
                     ->query(function (Builder $query, array $data) use ($column){
                         if ($value = $data[$column]){
                             $query->where($column, 'like', "%{$value}%");
                         }
                     })
                     ->indicateUsing(function (array $data) use ($column, $name){
                         if ($value = $data[$column]){
                             return [__("filter.{$name}") => $value];
                         }

                         return [];
                     });
    }

    /**
     * Tạo bộ lọc select cho enum hoặc relationship
     *
     * @param string         $name    Tên bộ lọc
     * @param string         $column  Cột database hoặc relationship
     * @param array|\Closure $options Danh sách tùy chọn
     *
     * @return SelectFilter
     */
    public static function makeSelectFilter(string $name, string $column, $options)
    : SelectFilter{
        $filter = SelectFilter::make($name)
                              ->label(__("filter.{$name}"))
                              ->options($options instanceof \Closure ? $options() : $options)
                              ->query(function (Builder $query, array $data) use ($column){
                                  if ($value = $data['value']){
                                      if (str_contains($column, '.')){
                                          // Xử lý relationship
                                          [$relation, $field] = explode('.', $column);
                                          $query->whereHas($relation,
                                              fn($q) => $q->where($field, $value));
                                      }else{
                                          $query->where($column, $value);
                                      }
                                  }
                              })
                              ->indicateUsing(function (array $data) use ($name){
                                  if ($value = $data['value']){
                                      return [__("filter.{$name}") => $value];
                                  }

                                  return [];
                              });

        return $filter;
    }

    /**
     * Tạo bộ lọc ngày
     *
     * @param string $name   Tên bộ lọc
     * @param string $column Cột database
     *
     * @return static
     */
    public static function makeDateFilter(string $name, string $column)
    : static{
        return static::make($name)
                     ->form([
                         DatePicker::make($column)
                                   ->label(__("filter.{$name}"))
                                   ->displayFormat('d/m/Y')
                                   ->placeholder(__("filter.{$name}_placeholder")),
                     ])
                     ->query(function (Builder $query, array $data) use ($column){
                         if ($value = $data[$column]){
                             $query->whereDate($column, Carbon::parse($value));
                         }
                     })
                     ->indicateUsing(function (array $data) use ($column, $name){
                         if ($value = $data[$column]){
                             return [__("filter.{$name}") => Carbon::parse($value)
                                                                   ->format('d/m/Y')];
                         }

                         return [];
                     });
    }

    /**
     * Tạo bộ lọc trạng thái (ternary: true/false/null)
     *
     * @param string $name   Tên bộ lọc
     * @param string $column Cột database
     *
     * @return TernaryFilter
     */
    public static function makeStatusFilter(string $name, string $column)
    : TernaryFilter{
        return TernaryFilter::make($name)
                            ->label(__("filter.{$name}"))
                            ->trueLabel(__('filter.active'))
                            ->falseLabel(__('filter.inactive'))
                            ->placeholder(__('filter.all'))
                            ->query(function (Builder $query, array $data) use ($column){
                                if ($data['value'] !== NULL){
                                    $query->where($column, $data['value']);
                                }
                            })
                            ->indicateUsing(function (array $data) use ($name){
                                if ($data['value'] !== NULL){
                                    return [__("filter.{$name}") => $data['value'] ? __('filter.active') : __('filter.inactive')];
                                }

                                return [];
                            });
    }

    /**
     * Tối ưu truy vấn với caching
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
        $cacheKey = 'filter_query_' . md5($query->toSql() . implode(',', $relationships));
        $ttl      = config('cache.default_ttl', 3600);

        return Cache::remember($cacheKey, $ttl,
            function () use ($query, $relationships, $selectColumns){
                if ($relationships){
                    $query->with($relationships);
                }
                if ($selectColumns){
                    $query->select($selectColumns);
                }

                return $query;
            });
    }

    /**
     * Ghi log khi áp dụng bộ lọc
     *
     * @param string $name Tên bộ lọc
     * @param array  $data Dữ liệu lọc
     *
     * @return void
     */
    protected function logFilterApplication(string $name, array $data)
    : void{
        $this->logService->logInfo("Filter applied: {$name}", [
            'filter'  => $name,
            'data'    => $this->sanitizeData($data),
            'user_id' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Loại bỏ thông tin nhạy cảm khỏi dữ liệu
     *
     * @param array $data
     *
     * @return array
     */
    protected function sanitizeData(array $data)
    : array{
        $sensitiveFields = ['password', 'token', 'api_key'];
        $sanitized       = $data;

        foreach ($sensitiveFields as $field){
            if (isset($sanitized[$field])){
                $sanitized[$field] = '****';
            }
        }

        return $sanitized;
    }

    /**
     * Xử lý lỗi
     *
     * @param \Exception $exception
     *
     * @return void
     * @throws BaseException
     */
    protected function handleError(\Exception $exception)
    : void{
        $message = "Error in filter " . static::class . ": {$exception->getMessage()}";
        $this->logService->logCritical($message, [
            'filter'  => static::class,
            'user_id' => auth()->id() ?? 'system',
            'trace'   => $exception->getTraceAsString(),
        ]);

        throw new BaseException('Lỗi khi áp dụng bộ lọc.', 500, [], [], $exception);
    }
}