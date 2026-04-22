<?php

namespace App\Base\Filament\Actions;

use App\Base\Exceptions\BaseException;
use App\Base\Services\LogService;
use App\Base\Services\NotificationService;
use App\Base\Services\PermissionService;
use App\Base\Services\ValidationService;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * BaseAction - Lớp cơ sở cho các action Filament
 *
 * Tính năng:
 * - Chuẩn hóa logic action (create, edit, delete, bulk)
 * - Tối ưu truy vấn với lazy loading và caching
 * - Tích hợp ValidationService, PermissionService, NotificationService
 * - Hỗ trợ format cột (date, number, status) và relationship
 * - Xử lý lỗi với BaseException và ghi log
 */
abstract class BaseAction extends Action{

    /**
     * Dịch vụ xác thực
     *
     * @var ValidationService
     */
    protected $validationService;

    /**
     * Dịch vụ quyền
     *
     * @var PermissionService
     */
    protected $permissionService;

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
     * Quyền yêu cầu cho action
     *
     * @var string|null
     */
    protected ?string $requiredPermission = NULL;

    /**
     * Preset validation (student, academic_year, score)
     *
     * @var string|null
     */
    protected ?string $validationPreset = NULL;

    /**
     * Constructor
     */
    public function __construct(){
        $this->validationService   = app(ValidationService::class);
        $this->permissionService   = app(PermissionService::class);
        $this->notificationService = app(NotificationService::class);
        $this->logService          = app(LogService::class);
    }

    /**
     * Kiểm tra quyền trước khi thực thi
     *
     * @return bool
     * @throws BaseException
     */
    protected function authorize()
    : bool{
        if ($this->requiredPermission && !$this->permissionService->hasPermission($this->requiredPermission)){
            throw new BaseException('Không có quyền thực thi hành động này.', 403);
        }

        return TRUE;
    }

    /**
     * Thực thi action với xử lý lỗi
     *
     * @param array      $data   Dữ liệu đầu vào
     * @param Model|null $record Bản ghi (nếu có)
     *
     * @return mixed
     * @throws BaseException
     */
    protected function handleAction(array $data, ?Model $record = NULL)
    : mixed{
        try{
            // Kiểm tra quyền
            $this->authorize();

            // Validate dữ liệu nếu có preset
            if ($this->validationPreset){
                $data = $this->validationService->validateWithPreset($data, $this->validationPreset,
                    FALSE);
            }

            // Thực thi logic action
            $result = $this->execute($data, $record);

            // Ghi log thành công
            $this->logService->logInfo("Action {$this->getName()} executed successfully", [
                'action'    => $this->getName(),
                'data'      => $this->sanitizeData($data),
                'record_id' => $record?->getKey(),
                'user_id'   => auth()->id() ?? 'system',
            ]);

            // Gửi thông báo
            $this->notificationService->sendFilamentNotification(
                'Thành công',
                "Hành động {$this->getLabel()} đã được thực thi.",
                'success'
            );

            return $result;
        }catch (BaseException $e){
            $this->handleError($data, $record, $e);
            throw $e;
        }catch (\Illuminate\Validation\ValidationException $e){
            $this->handleError($data, $record, $e);
            throw $e;
        }catch (\Exception $e){
            $this->handleError($data, $record, $e);
            throw new BaseException('Lỗi khi thực thi hành động.', 500, [], [], $e);
        }
    }

    /**
     * Logic thực thi action (phải ghi đè trong lớp con)
     *
     * @param array      $data   Dữ liệu đã validate
     * @param Model|null $record Bản ghi (nếu có)
     *
     * @return mixed
     */
    abstract protected function execute(array $data, ?Model $record)
    : mixed;

    /**
     * Xử lý lỗi
     *
     * @param array      $data      Dữ liệu đầu vào
     * @param Model|null $record    Bản ghi
     * @param \Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function handleError(array $data, ?Model $record, \Exception $exception)
    : void{
        $message = "Error in action {$this->getName()}: {$exception->getMessage()}";
        $this->logService->logCritical($message, [
            'action'    => $this->getName(),
            'data'      => $this->sanitizeData($data),
            'record_id' => $record?->getKey(),
            'user_id'   => auth()->id() ?? 'system',
            'trace'     => $exception->getTraceAsString(),
        ]);

        $this->notificationService->sendFilamentNotification(
            'Lỗi',
            $message,
            'error'
        );
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
     * Tối ưu truy vấn cho relationship
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $relationships
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function optimizeQuery($query, array $relationships = [])
    : \Illuminate\Database\Eloquent\Builder{
        // Cache relationship nếu cần
        $cacheKey = 'query_' . md5(serialize($query->toSql() . implode(',', $relationships)));
        $ttl      = config('cache.default_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($query, $relationships){
            // Chỉ load relationship cần thiết
            if ($relationships){
                $query->with($relationships);
            }

            // Select chỉ các cột cần thiết
            if ($this->getModel()){
                $model = app($this->getModel());
                $query->select($model->getFillable());
            }

            return $query;
        });
    }

    /**
     * Format cột dữ liệu
     *
     * @param string $column Tên cột
     * @param mixed  $value  Giá trị
     * @param string $type   Loại định dạng (date, currency, status, number)
     *
     * @return string
     */
    protected function formatColumn(string $column, $value, string $type = 'text')
    : string{
        switch ($type){
            case 'date':
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            case 'currency':
                return number_format($value, 0, ',', '.') . ' VND';
            case 'status':
                return \Modules\Base\Enums\StatusEnum::tryFrom($value)?->label() ?? $value;
            case 'number':
                return number_format($value, 2);
            default:
                return (string) $value;
        }
    }

    /**
     * Xử lý relationship trong bảng
     *
     * @param string $relation      Tên relationship
     * @param Model  $record        Bản ghi
     * @param string $displayColumn Cột hiển thị
     *
     * @return string
     */
    protected function formatRelation(
        string $relation,
        Model $record,
        string $displayColumn = 'name')
    : string{
        $related = $record->$relation;
        if ($related instanceof \Illuminate\Database\Eloquent\Collection){
            return $related->pluck($displayColumn)->implode(', ');
        }

        return $related ? $related->$displayColumn : '-';
    }
}