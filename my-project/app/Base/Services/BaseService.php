<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use App\Base\Models\BaseModel;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * BaseService - Lớp dịch vụ cơ sở xử lý logic nghiệp vụ
 *
 * Tính năng:
 * - Cung cấp các phương thức CRUD (Create, Read, Update, Delete) và tìm kiếm/phân trang
 * - Tích hợp validation dữ liệu đầu vào với Laravel Validator
 * - Hỗ trợ tìm kiếm, lọc, và preload quan hệ
 * - Ghi audit log cho các hành động (nếu bật)
 * - Xử lý lỗi thống nhất với BaseException
 * - Dễ dàng mở rộng cho các dịch vụ con (như AcademicYearService)
 */
class BaseService{

    /**
     * Model liên kết với dịch vụ
     *
     * @var BaseModel
     */
    protected $model;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param BaseModel $model
     */
    public function __construct(BaseModel $model){
        $this->model = $model;
    }

    /**
     * Lấy danh sách phân trang với bộ lọc và quan hệ
     *
     * @param array $filters   Bộ lọc (search, is_active, status, sort_by, sort_direction)
     * @param int   $perPage   Số bản ghi mỗi trang
     * @param array $relations Các quan hệ cần preload
     *
     * @return LengthAwarePaginator
     * @throws BaseException
     */
    public function getPaginated(array $filters = [], int $perPage = 15, array $relations = [])
    : LengthAwarePaginator{
        try{
            $query = $this->model->query();

            // Áp dụng bộ lọc
            $this->applyFilters($query, $filters);

            // Preload quan hệ
            if (!empty($relations)){
                $query->with($relations);
            }

            // Đảm bảo perPage hợp lệ
            $perPage = max(1, min($perPage, config('base.default_per_page', 15)));

            return $query->paginate($perPage);
        }catch (Exception $e){
            $this->logError('getPaginated', ['filters' => $filters], $e);
            throw new BaseException('Không thể lấy danh sách dữ liệu.', 500, [], [], $e);
        }
    }

    /**
     * Tìm bản ghi theo UUID hoặc ném lỗi
     *
     * @param string $uuid      UUID của bản ghi
     * @param array  $relations Các quan hệ cần preload
     *
     * @return BaseModel
     * @throws BaseException
     */
    public function findByUuidOrFail(string $uuid, array $relations = [])
    : BaseModel{
        try{
            // Kiểm tra UUID hợp lệ
            if (!$this->isValidUuid($uuid)){
                throw BaseException::notFound($this->getModelName(), $uuid);
            }

            $query = $this->model->query()->where('uuid', $uuid);

            if (!empty($relations)){
                $query->with($relations);
            }

            $record = $query->first();

            if (!$record){
                throw BaseException::notFound($this->getModelName(), $uuid);
            }

            return $record;
        }catch (Exception $e){
            $this->logError('findByUuidOrFail', ['uuid' => $uuid], $e);
            throw $e instanceof BaseException ? $e : new BaseException('Không thể tìm thấy bản ghi.',
                404, [], [], $e);
        }
    }

    /**
     * Tạo bản ghi mới
     *
     * @param array $data Dữ liệu đầu vào
     *
     * @return BaseModel
     * @throws BaseException
     */
    public function create(array $data)
    : BaseModel{
        try{
            $validatedData = $this->validateData($data, 'create');

            // Ghi audit log cho người tạo
            if (auth()->check() && method_exists($this->model, 'setCreatedBy')){
                $validatedData['created_by'] = auth()->id();
            }

            $record = $this->model->create($validatedData);

            $this->logAudit('create', $record);

            return $record->fresh();
        }catch (ValidationException $e){
            throw BaseException::validation($e->errors(), 'Dữ liệu không hợp lệ');
        }catch (Exception $e){
            $this->logError('create', ['data' => $data], $e);
            throw new BaseException('Không thể tạo bản ghi.', 500, [], [], $e);
        }
    }

    /**
     * Cập nhật bản ghi theo UUID
     *
     * @param string $uuid UUID của bản ghi
     * @param array  $data Dữ liệu cần cập nhật
     *
     * @return BaseModel
     * @throws BaseException
     */
    public function update(string $uuid, array $data)
    : BaseModel{
        try{
            $record = $this->findByUuidOrFail($uuid);

            $validatedData = $this->validateData($data, 'update');

            // Ghi audit log cho người cập nhật
            if (auth()->check() && method_exists($this->model, 'setUpdatedBy')){
                $validatedData['updated_by'] = auth()->id();
            }

            $record->update($validatedData);

            $this->logAudit('update', $record);

            return $record->fresh();
        }catch (ValidationException $e){
            throw BaseException::validation($e->errors(), 'Dữ liệu không hợp lệ');
        }catch (Exception $e){
            $this->logError('update', ['uuid' => $uuid, 'data' => $data], $e);
            throw $e instanceof BaseException ? $e : new BaseException('Không thể cập nhật bản ghi.',
                500, [], [], $e);
        }
    }

    /**
     * Xóa bản ghi theo UUID
     *
     * @param string $uuid UUID của bản ghi
     *
     * @return void
     * @throws BaseException
     */
    public function delete(string $uuid)
    : void{
        try{
            $record = $this->findByUuidOrFail($uuid);

            $this->logAudit('delete', $record);

            $record->delete();
        }catch (Exception $e){
            $this->logError('delete', ['uuid' => $uuid], $e);
            throw $e instanceof BaseException ? $e : new BaseException('Không thể xóa bản ghi.',
                500, [], [], $e);
        }
    }

    /**
     * Validate dữ liệu đầu vào
     *
     * @param array  $data   Dữ liệu cần validate
     * @param string $action Hành động (create, update)
     *
     * @return array Dữ liệu đã validate
     * @throws ValidationException
     */
    protected function validateData(array $data, string $action = 'create')
    : array{
        try{
            $rules    = $this->getValidationRules($action);
            $messages = $this->getValidationMessages($action);

            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()){
                throw new ValidationException($validator);
            }

            return $validator->validated();
        }catch (Exception $e){
            $this->logError('validateData', ['data' => $data, 'action' => $action], $e);
            throw $e instanceof ValidationException ? $e : new BaseException('Lỗi xác thực dữ liệu.',
                422, [], [], $e);
        }
    }

    /**
     * Lấy quy tắc validation
     *
     * @param string $action Hành động (create, update)
     *
     * @return array Quy tắc validation
     */
    protected function getValidationRules(string $action)
    : array{
        // Quy tắc mặc định, có thể override trong class con
        return [
            'is_active' => ['sometimes', 'boolean'],
            'status'    => ['sometimes', 'in:' . implode(',',
                    collect(\Modules\Base\Enums\StatusEnum::cases())->pluck('value')->toArray())],
        ];
    }

    /**
     * Lấy thông điệp validation tùy chỉnh
     *
     * @param string $action Hành động (create, update)
     *
     * @return array Thông điệp validation
     */
    protected function getValidationMessages(string $action)
    : array{
        // Thông điệp mặc định, có thể override trong class con
        return [
            'is_active.boolean' => 'Trạng thái phải là boolean.',
            'status.in'         => 'Trạng thái hệ thống không hợp lệ.',
        ];
    }

    /**
     * Áp dụng bộ lọc cho query
     *
     * @param Builder $query   Query Eloquent
     * @param array   $filters Bộ lọc
     *
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters)
    : void{
        // Tìm kiếm toàn văn
        if (!empty($filters['search']) && method_exists($this->model, 'search')){
            $query->search(trim($filters['search']));
        }

        // Lọc theo is_active
        if (isset($filters['is_active']) && is_bool($filters['is_active'])){
            $query->where('is_active', $filters['is_active']);
        }

        // Lọc theo status
        if (!empty($filters['status']) && in_array($filters['status'],
                collect(\Modules\Base\Enums\StatusEnum::cases())->pluck('value')->toArray())){
            $query->where('status', $filters['status']);
        }

        // Sắp xếp
        if (!empty($filters['sort_by']) && !empty($filters['sort_direction']) && in_array($filters['sort_direction'],
                ['asc', 'desc'])){
            $query->orderBy($filters['sort_by'], $filters['sort_direction']);
        }
    }

    /**
     * Ghi log audit
     *
     * @param string    $action Hành động (create, update, delete)
     * @param BaseModel $record Bản ghi
     *
     * @return void
     */
    protected function logAudit(string $action, BaseModel $record)
    : void{
        try{
            if (config('base.audit_logging', TRUE) && method_exists($record, 'logAudit')){
                $record->logAudit($action, auth()->user());
            }
        }catch (Exception $e){
            Log::error("Failed to log audit for {$this->getModelName()} UUID {$record->uuid}: {$e->getMessage()}",
                [
                    'action'  => $action,
                    'user_id' => auth()->id() ?? 'system',
                ]);
        }
    }

    /**
     * Kiểm tra UUID hợp lệ
     *
     * @param string $uuid UUID cần kiểm tra
     *
     * @return bool
     */
    protected function isValidUuid(string $uuid)
    : bool{
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $uuid) === 1;
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (data, uuid, filters)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in {$this->getModelName()}::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }

    /**
     * Lấy tên model
     *
     * @return string
     */
    protected function getModelName()
    : string{
        return class_basename($this->model);
    }
}