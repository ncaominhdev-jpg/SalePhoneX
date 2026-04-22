<?php

namespace App\Base\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BaseException - Lớp ngoại lệ tùy chỉnh cho module Base
 *
 * Tính năng:
 * - Chuẩn hóa thông điệp lỗi, mã trạng thái, và dữ liệu bổ sung
 * - Hỗ trợ phản hồi JSON cho API
 * - Ghi log lỗi chi tiết với ngữ cảnh
 * - Tích hợp với BaseController và các lớp khác
 * - Dễ dàng mở rộng cho các ngoại lệ cụ thể
 */
class BaseException extends Exception{

    /**
     * Dữ liệu lỗi bổ sung (validation errors, v.v.)
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Metadata bổ sung cho phản hồi
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Constructor
     *
     * @param string         $message
     * @param int            $code
     * @param array          $errors
     * @param array          $metadata
     * @param Exception|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = 400,
        array $errors = [],
        array $metadata = [],
        Exception $previous = NULL
    ){
        parent::__construct($message, $code, $previous);
        $this->errors   = $errors;
        $this->metadata = $metadata;

        // Ghi log lỗi
        $this->logError();
    }

    /**
     * Ghi log lỗi với ngữ cảnh
     *
     * @return void
     */
    protected function logError()
    : void{
        try{
            Log::error("BaseException: {$this->getMessage()}", [
                'code'     => $this->getCode(),
                'errors'   => $this->errors,
                'metadata' => $this->metadata,
                'user_id'  => auth()->id() ?? 'system',
                'request'  => request()->all(),
                'trace'    => $this->getTraceAsString(),
            ]);
        }catch (Exception $e){
            // Bỏ qua nếu không ghi được log
        }
    }

    /**
     * Trả về phản hồi JSON cho API
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function render(Request $request)
    : JsonResponse{
        $response = [
            'success' => FALSE,
            'message' => $this->getMessage() ?: 'Có lỗi xảy ra',
            'code'    => $this->getCode(),
        ];

        if (!empty($this->errors)){
            $response['errors'] = $this->errors;
        }

        if (!empty($this->metadata)){
            $response['metadata'] = $this->metadata;
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Thêm lỗi bổ sung
     *
     * @param array $errors
     *
     * @return $this
     */
    public function withErrors(array $errors)
    : self{
        $this->errors = array_merge($this->errors, $errors);

        return $this;
    }

    /**
     * Thêm metadata bổ sung
     *
     * @param array $metadata
     *
     * @return $this
     */
    public function withMetadata(array $metadata)
    : self{
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    /**
     * Tạo ngoại lệ cho validation
     *
     * @param array  $errors
     * @param string $message
     *
     * @return self
     */
    public static function validation(array $errors, string $message = 'Dữ liệu không hợp lệ')
    : self{
        return new self($message, 422, $errors);
    }

    /**
     * Tạo ngoại lệ cho not found
     *
     * @param string $resource
     * @param string $identifier
     *
     * @return self
     */
    public static function notFound(string $resource, string $identifier)
    : self{
        return new self("Không tìm thấy {$resource} với định danh {$identifier}", 404);
    }

    /**
     * Tạo ngoại lệ cho unauthorized
     *
     * @param string $message
     *
     * @return self
     */
    public static function unauthorized(string $message = 'Không có quyền truy cập')
    : self{
        return new self($message, 403);
    }
}