<?php

namespace App\Base\Models;

use App\Base\Enums\StatusEnum;
use App\Base\Models\Traits\HasAuditLog;
use App\Base\Models\Traits\HasTimestamps;
use App\Base\Models\Traits\HasUuid;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * BaseModel - Model cơ sở cho tất cả các model trong hệ thống quản lý đào tạo
 *
 * Tính năng:
 * - Sử dụng UUID làm khóa chính
 * - Hỗ trợ xóa mềm (soft deletes)
 * - Ghi nhật ký thay đổi (audit logging)
 * - Định dạng timestamp
 * - Quản lý trạng thái (status) với StatusEnum
 * - Query scopes linh hoạt
 * - Validation rules động
 * - Xử lý lỗi toàn diện (tùy chọn ngoại lệ tùy chỉnh)
 * - Hỗ trợ export/import dữ liệu
 * - Tối ưu cho Filament
 */
abstract class BaseModel extends Model{

    use HasFactory, SoftDeletes, HasUuid, HasAuditLog, HasTimestamps;

    /**
     * Khóa chính không phải là auto-increment integer
     *
     * @var bool
     */
    public $incrementing = FALSE;

    /**
     * Kiểu dữ liệu của khóa chính
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Các trường không được phép gán hàng loạt
     *
     * @var array
     */
    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Các trường bị ẩn khi serialize (JSON, Array)
     *
     * @var array
     */
    protected $hidden = ['deleted_at', 'created_by', 'updated_by'];

    /**
     * Các trường được cast sang kiểu dữ liệu cụ thể
     *
     * @var array
     */
    protected $casts = [
        'id'         => 'string',
        'uuid'       => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active'  => 'boolean',
        'status'     => StatusEnum::class,
    ];

    /**
     * Các trường date để mutate
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected static $throwCustomExceptions = TRUE;

    /**
     * Boot method - Khởi tạo các sự kiện của model
     */
    protected static function boot(){
        parent::boot();

        // Tự động set status = ACTIVE và is_active = true khi tạo mới
        static::creating(function ($model){
            if (!isset($model->status)){
                $model->status = StatusEnum::ACTIVE;
            }
            if (!isset($model->is_active)){
                $model->is_active = TRUE;
            }
        });

        // Ghi log khi tạo mới
        static::created(function ($model){
            try{
                Log::info("Model {$model->getTable()} created", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid,
                    'data'    => $model->toArray(),
                    'user_id' => auth()->id() ?? 'system',
                ]);
            }catch (Exception $e){
                Log::error("Failed to log creation of {$model->getTable()}: {$e->getMessage()}");
            }
        });

        // Ghi log khi xóa mềm
        static::softDeleted(function ($model){
            try{
                Log::info("Model {$model->getTable()} soft deleted", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid,
                    'user_id' => auth()->id() ?? 'system',
                ]);
            }catch (Exception $e){
                Log::error("Failed to log soft deletion of {$model->getTable()}: {$e->getMessage()}");
            }
        });

        // Ghi log khi khôi phục
        static::restored(function ($model){
            try{
                Log::info("Model {$model->getTable()} restored", [
                    'id'      => $model->id,
                    'uuid'    => $model->uuid,
                    'user_id' => auth()->id() ?? 'system',
                ]);
            }catch (Exception $e){
                Log::error("Failed to log restoration of {$model->getTable()}: {$e->getMessage()}");
            }
        });
    }

    // ===============================================
    // ACCESSORS & MUTATORS
    // ===============================================

    /**
     * Accessor: Định dạng ngày tạo
     *
     * @return string
     */
    public function getCreatedAtFormattedAttribute()
    : string{
        return $this->formatDateTime($this->created_at, 'd/m/Y H:i');
    }

    /**
     * Accessor: Định dạng ngày cập nhật
     *
     * @return string
     */
    public function getUpdatedAtFormattedAttribute()
    : string{
        return $this->formatDateTime($this->updated_at, 'd/m/Y H:i');
    }

    /**
     * Accessor: Lấy nhãn hiển thị của trạng thái
     *
     * @return string
     */
    public function getStatusLabelAttribute()
    : string{
        return $this->status?->getLabel() ?? 'Không xác định';
    }

    /**
     * Accessor: Lấy màu của trạng thái cho UI
     *
     * @return string
     */
    public function getStatusColorAttribute()
    : string{
        return $this->status?->getColor() ?? 'gray';
    }

    /**
     * Mutator: Chuẩn hóa email (lowercase)
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setEmailAttribute($value)
    : void{
        $this->attributes['email'] = !empty($value) ? strtolower(trim($value)) : NULL;
    }

    /**
     * Mutator: Chuẩn hóa trường dạng chuỗi (trim và null nếu rỗng)
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return void
     */
    public function setStringAttribute(string $attribute, $value)
    : void{
        $this->attributes[$attribute] = !empty($value) ? trim($value) : NULL;
    }

    // ===============================================
    // QUERY SCOPES
    // ===============================================

    /**
     * Scope: Lấy các bản ghi đang hoạt động
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query)
    : Builder{
        return $query->where('is_active', TRUE);
    }

    /**
     * Scope: Lấy các bản ghi không hoạt động
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeInactive(Builder $query)
    : Builder{
        return $query->where('is_active', FALSE);
    }

    /**
     * Scope: Lọc theo trạng thái
     *
     * @param Builder           $query
     * @param StatusEnum|string $status
     *
     * @return Builder
     */
    public function scopeByStatus(Builder $query, $status)
    : Builder{
        $statusValue = $status instanceof StatusEnum ? $status : StatusEnum::from($status);

        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Tìm kiếm theo nhiều trường
     *
     * @param Builder     $query
     * @param string|null $term
     *
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $term)
    : Builder{
        if (empty($term)){
            return $query;
        }

        $searchableFields = $this->getSearchableFields();
        if (empty($searchableFields)){
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $searchableFields){
            foreach ($searchableFields as $field){
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
    }

    /**
     * Scope: Sắp xếp theo ngày tạo mới nhất
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeLatest(Builder $query)
    : Builder{
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Sắp xếp theo ngày tạo cũ nhất
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeOldest(Builder $query)
    : Builder{
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope: Lấy bản ghi với quan hệ
     *
     * @param Builder      $query
     * @param string|array $relations
     *
     * @return Builder
     */
    public function scopeWithRelations(Builder $query, $relations)
    : Builder{
        return $query->with(is_array($relations) ? $relations : func_get_args());
    }

    /**
     * Scope: Lấy bản ghi kể cả đã xóa mềm
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeWithTrashed(Builder $query)
    : Builder{
        return $query->withTrashed();
    }

    // ===============================================
    // HELPER METHODS
    // ===============================================

    /**
     * Lấy tên bảng dạng human-readable
     *
     * @return string
     */
    public function getReadableTableName()
    : string{
        return Str::title(str_replace('_', ' ', $this->getTable()));
    }

    /**
     * Lấy các trường có thể tìm kiếm
     * Override trong model con nếu cần
     *
     * @return array
     */
    protected function getSearchableFields()
    : array{
        return ['name', 'title', 'description', 'code'];
    }

    /**
     * Lấy các trường có thể export
     * Override trong model con nếu cần
     *
     * @return array
     */
    public function getExportableFields()
    : array{
        return array_merge($this->fillable,
            ['created_at_formatted', 'updated_at_formatted', 'status_label']);
    }

    /**
     * Kiểm tra xem bản ghi có đang hoạt động không
     *
     * @return bool
     */
    public function isActive()
    : bool{
        return $this->is_active === TRUE;
    }

    /**
     * Kiểm tra xem bản ghi có bị xóa mềm không
     *
     * @return bool
     */
    public function isDeleted()
    : bool{
        return $this->deleted_at !== NULL;
    }

    /**
     * Kích hoạt bản ghi
     *
     * @return bool
     * @throws Exception|ValidationException
     */
    public function activate()
    : bool{
        try{
            return $this->update(['is_active' => TRUE]);
        }catch (Exception $e){
            Log::error("Failed to activate {$this->getTable()} ID {$this->id}: {$e->getMessage()}");
            $this->throwException('Không thể kích hoạt bản ghi.', 500, $e);
        }
    }

    /**
     * Hủy kích hoạt bản ghi
     *
     * @return bool
     * @throws Exception|ValidationException
     */
    public function deactivate()
    : bool{
        try{
            return $this->update(['is_active' => FALSE]);
        }catch (Exception $e){
            Log::error("Failed to deactivate {$this->getTable()} ID {$this->id}: {$e->getMessage()}");
            $this->throwException('Không thể hủy kích hoạt bản ghi.', 500, $e);
        }
    }

    /**
     * Chuyển đổi trạng thái active/inactive
     *
     * @return bool
     * @throws Exception|ValidationException
     */
    public function toggleStatus()
    : bool{
        return $this->isActive() ? $this->deactivate() : $this->activate();
    }

    /**
     * Lấy thông tin audit trail
     *
     * @return array
     */
    public function getAuditTrail()
    : array{
        return [
            'created_by' => $this->creator?->name ?? 'Hệ thống',
            'updated_by' => $this->updater?->name ?? 'Hệ thống',
            'created_at' => $this->created_at_formatted,
            'updated_at' => $this->updated_at_formatted,
        ];
    }

    /**
     * Validate dữ liệu trước khi tạo/cập nhật
     *
     * @param array  $data
     * @param string $context ('create' hoặc 'update')
     *
     * @return array
     * @throws ValidationException
     */
    public function validateData(array $data, string $context = 'create')
    : array{
        $rules = $context === 'create' ? static::getCreateValidationRules() : static::getUpdateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()){
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Quan hệ: Người tạo
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(){
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class),
            'created_by');
    }

    /**
     * Quan hệ: Người cập nhật
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater(){
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class),
            'updated_by');
    }

    // ===============================================
    // VALIDATION RULES
    // ===============================================

    /**
     * Lấy validation rules cơ bản
     * Override trong model con để thêm rules cụ thể
     *
     * @return array
     */
    public static function getValidationRules()
    : array{
        return [
            'is_active' => 'boolean',
            'status'    => 'string|in:' . implode(',', array_column(StatusEnum::cases(), 'value')),
        ];
    }

    /**
     * Lấy validation rules cho tạo mới
     *
     * @return array
     */
    public static function getCreateValidationRules()
    : array{
        return static::getValidationRules();
    }

    /**
     * Lấy validation rules cho cập nhật
     *
     * @return array
     */
    public static function getUpdateValidationRules()
    : array{
        return static::getValidationRules();
    }

    // ===============================================
    // SERIALIZATION
    // ===============================================

    /**
     * Tùy chỉnh JSON serialization
     *
     * @return array
     */
    public function toArray()
    : array{
        $array = parent::toArray();

        // Thêm các computed attributes
        $array['created_at_formatted'] = $this->created_at_formatted;
        $array['updated_at_formatted'] = $this->updated_at_formatted;
        $array['status_label']         = $this->status_label;
        $array['status_color']         = $this->status_color;

        return $array;
    }

    /**
     * Export dữ liệu cho Excel/CSV
     *
     * @return array
     */
    public function toExportArray()
    : array{
        $fields = $this->getExportableFields();
        $data   = [];

        foreach ($fields as $field){
            $data[$field] = $this->getAttribute($field) ?? $this->{$field} ?? NULL;
        }

        return $data;
    }

    /**
     * Tìm bản ghi theo UUID hoặc throw exception nếu không tìm thấy
     *
     * @param string $uuid
     *
     * @return static
     * @throws Exception
     */
    public static function findByUuidOrFail(string $uuid)
    : static{
        $model = static::findByUuid($uuid);

        if (!$model){
            static::throwException("Không tìm thấy bản ghi với UUID: {$uuid}", 404);
        }

        return $model;
    }

    /**
     * Tìm bản ghi theo UUID
     *
     * @param string $uuid
     *
     * @return static|null
     */
    public static function findByUuid(string $uuid)
    : ?static{
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Lấy tất cả bản ghi với phân trang và tùy chọn quan hệ
     *
     * @param int               $perPage
     * @param array|string|null $relations
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function getPaginated(int $perPage = 15, $relations = NULL){
        $query = static::query();
        if ($relations){
            $query->withRelations($relations);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     * @param array|null     $errors
     *
     * @return void
     * @throws Exception
     */
    protected static function throwException(
        string $message,
        int $code = 400,
        ?Exception $previous = NULL,
        ?array $errors = NULL)
    : void{
        if (static::$throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, $previous, $errors);
        }

        throw new Exception($message, $code, $previous);
    }
}