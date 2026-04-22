<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * ValidationService - Lớp tiện ích quản lý validation
 *
 * Tính năng:
 * - Validate dữ liệu với rule tùy chỉnh hoặc rule chung
 * - Tái sử dụng message lỗi tùy chỉnh
 * - Hỗ trợ validation lồng ghép (nested arrays)
 * - Thông báo lỗi qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException và LogService
 */
class ValidationService{

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
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Constructor
     *
     * @param NotificationService $notificationService
     * @param LogService          $logService
     */
    public function __construct(NotificationService $notificationService, LogService $logService){
        $this->notificationService = $notificationService;
        $this->logService          = $logService;
    }

    /**
     * Validate dữ liệu
     *
     * @param array $data       Dữ liệu cần validate
     * @param array $rules      Quy tắc validation
     * @param array $messages   Message lỗi tùy chỉnh
     * @param array $attributes Tên thuộc tính tùy chỉnh
     * @param bool  $notify     Gửi thông báo lỗi qua Filament
     *
     * @return array Dữ liệu đã validate
     * @throws ValidationException|BaseException
     */
    public function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = [],
        bool $notify = TRUE
    )
    : array{
        try{
            $validator = Validator::make($data, $rules, $messages, $attributes);

            if ($validator->fails()){
                if ($notify){
                    $this->notificationService->sendFilamentNotification(
                        'Lỗi xác thực',
                        implode(' ', $validator->errors()->all()),
                        'error'
                    );
                }

                $this->logService->logCritical(
                    'Validation failed',
                    [
                        'errors' => $validator->errors()->toArray(),
                        'data'   => $this->sanitizeData($data),
                        'rules'  => $rules,
                    ]
                );

                throw new ValidationException($validator);
            }

            Log::info('Validation successful', [
                'data'    => $this->sanitizeData($data),
                'rules'   => $rules,
                'user_id' => auth()->id() ?? 'system',
            ]);

            return $validator->validated();
        }catch (Exception $e){
            $this->handleError('validate', [
                'data'   => $this->sanitizeData($data),
                'rules'  => $rules,
                'notify' => $notify,
            ], $e);
            throw new BaseException('Không thể xác thực dữ liệu.', 500, [], [], $e);
        }
    }

    /**
     * Validate dữ liệu với rule chung
     *
     * @param array  $data   Dữ liệu cần validate
     * @param string $type   Loại rule (student, academic_year, score)
     * @param bool   $notify Gửi thông báo lỗi qua Filament
     *
     * @return array Dữ liệu đã validate
     * @throws ValidationException|BaseException
     */
    public function validateWithPreset(array $data, string $type, bool $notify = TRUE)
    : array{
        try{
            $rules      = $this->getPresetRules($type);
            $messages   = $this->getPresetMessages($type);
            $attributes = $this->getPresetAttributes($type);

            return $this->validate($data, $rules, $messages, $attributes, $notify);
        }catch (Exception $e){
            $this->handleError('validateWithPreset', [
                'data'   => $this->sanitizeData($data),
                'type'   => $type,
                'notify' => $notify,
            ], $e);
            throw new BaseException("Không thể xác thực dữ liệu với preset {$type}.", 500, [], [],
                $e);
        }
    }

    /**
     * Lấy rule chung theo loại
     *
     * @param string $type Loại rule
     *
     * @return array
     */
    public function getPresetRules(string $type)
    : array{
        $presets = [
            'student'       => [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:students,email'],
                'phone'    => ['nullable', 'string', 'regex:/^[0-9]{10,11}$/'],
                'class_id' => ['required', 'exists:classes,id'],
            ],
            'academic_year' => [
                'name'       => ['required', 'string', 'max:255'],
                'start_date' => ['required', 'date', 'before:end_date'],
                'end_date'   => ['required', 'date', 'after:start_date'],
            ],
            'score'         => [
                'student_id' => ['required', 'exists:students,id'],
                'subject_id' => ['required', 'exists:subjects,id'],
                'score'      => ['required', 'numeric', 'between:0,10'],
                'semester'   => ['required', 'in:1,2'],
            ],
        ];

        return Arr::get($presets, $type, []);
    }

    /**
     * Lấy message lỗi chung theo loại
     *
     * @param string $type Loại rule
     *
     * @return array
     */
    public function getPresetMessages(string $type)
    : array{
        return [
                   'student'       => [
                       'name.required'   => 'Tên sinh viên là bắt buộc.',
                       'email.unique'    => 'Email đã tồn tại.',
                       'phone.regex'     => 'Số điện thoại không hợp lệ.',
                       'class_id.exists' => 'Lớp không tồn tại.',
                   ],
                   'academic_year' => [
                       'start_date.before' => 'Ngày bắt đầu phải trước ngày kết thúc.',
                       'end_date.after'    => 'Ngày kết thúc phải sau ngày bắt đầu.',
                   ],
                   'score'         => [
                       'score.between' => 'Điểm phải từ 0 đến 10.',
                       'semester.in'   => 'Học kỳ không hợp lệ.',
                   ],
               ][$type] ?? [];
    }

    /**
     * Lấy tên thuộc tính chung theo loại
     *
     * @param string $type Loại rule
     *
     * @return array
     */
    public function getPresetAttributes(string $type)
    : array{
        return [
                   'student'       => [
                       'name'     => 'Tên sinh viên',
                       'email'    => 'Email',
                       'phone'    => 'Số điện thoại',
                       'class_id' => 'Lớp',
                   ],
                   'academic_year' => [
                       'name'       => 'Tên năm học',
                       'start_date' => 'Ngày bắt đầu',
                       'end_date'   => 'Ngày kết thúc',
                   ],
                   'score'         => [
                       'student_id' => 'Sinh viên',
                       'subject_id' => 'Môn học',
                       'score'      => 'Điểm số',
                       'semester'   => 'Học kỳ',
                   ],
               ][$type] ?? [];
    }

    /**
     * Loại bỏ thông tin nhạy cảm khỏi dữ liệu
     *
     * @param array $data Dữ liệu
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
     * Xử lý lỗi và gửi thông báo
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     * @throws \Modules\Base\Exceptions\BaseException
     */
    protected function handleError(string $method, array $context, Exception $exception)
    : void{
        $message = "Error in ValidationService::{$method}: {$exception->getMessage()}";
        $context = array_merge($context, [
            'user_id' => auth()->id() ?? 'system',
            'trace'   => $exception->getTraceAsString(),
        ]);

        $this->logService->logCritical($message, $context, $exception);

        if (!($exception instanceof ValidationException)){
            $this->notificationService->sendFilamentNotification(
                'Lỗi xác thực',
                $message,
                'error'
            );
        }
    }
}