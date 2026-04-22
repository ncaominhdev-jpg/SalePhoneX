<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ImportService - Lớp tiện ích xử lý nhập dữ liệu từ Excel/CSV
 *
 * Tính năng:
 * - Nhập dữ liệu từ file Excel/CSV với Maatwebsite\Excel (nếu có)
 * - Validation dữ liệu trước khi nhập
 * - Tích hợp với BaseService để lưu dữ liệu
 * - Xử lý lỗi và ghi log với BaseException
 * - Thông báo kết quả nhập qua NotificationService
 */
class ImportService{

    /**
     * Dịch vụ cơ sở để lưu dữ liệu
     *
     * @var BaseService
     */
    protected $service;

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
     * @param BaseService         $service
     * @param NotificationService $notificationService
     */
    public function __construct(BaseService $service, NotificationService $notificationService){
        $this->service             = $service;
        $this->notificationService = $notificationService;
    }

    /**
     * Nhập dữ liệu từ file Excel/CSV
     *
     * @param \Illuminate\Http\UploadedFile $file               File Excel/CSV
     * @param array                         $validationRules    Quy tắc validation cho mỗi bản ghi
     * @param array                         $validationMessages Thông điệp validation tùy chỉnh
     * @param array                         $fieldMapping       Ánh xạ cột file với trường dữ liệu
     *
     * @return array Kết quả nhập (success_count, error_count, errors)
     * @throws BaseException
     */
    public function importFromFile(
        $file,
        array $validationRules = [],
        array $validationMessages = [],
        array $fieldMapping = [])
    : array{
        try{
            // Kiểm tra package Maatwebsite\Excel
            if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)){
                throw new Exception('Package Maatwebsite\Excel chưa được cài đặt. Vui lòng chạy: composer require maatwebsite/excel');
            }

            // Kiểm tra file hợp lệ
            $this->validateFile($file);

            // Tạo class import động
            $importClass = new class($validationRules, $validationMessages, $fieldMapping, $this->service, $this->notificationService) implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow{

                private $validationRules;
                private $validationMessages;
                private $fieldMapping;
                private $service;
                private $notificationService;
                private $successCount = 0;
                private $errorCount = 0;
                private $errors = [];

                public function __construct(
                    $validationRules,
                    $validationMessages,
                    $fieldMapping,
                    $service,
                    $notificationService){
                    $this->validationRules     = $validationRules;
                    $this->validationMessages  = $validationMessages;
                    $this->fieldMapping        = $fieldMapping;
                    $this->service             = $service;
                    $this->notificationService = $notificationService;
                }

                public function collection(Collection $rows){
                    foreach ($rows as $index => $row){
                        try{
                            // Ánh xạ dữ liệu từ cột file sang trường dữ liệu
                            $data = $this->mapRowToData($row->toArray(), $this->fieldMapping);

                            // Validate dữ liệu
                            $validator = Validator::make($data, $this->validationRules,
                                $this->validationMessages);

                            if ($validator->fails()){
                                $this->errorCount ++;
                                $this->errors[] = [
                                    'row'    => $index + 2, // +2 vì có heading row và index bắt đầu từ 0
                                    'errors' => $validator->errors()->all(),
                                    'data'   => $data,
                                ];
                                continue;
                            }

                            // Lưu dữ liệu
                            $this->service->create($data);
                            $this->successCount ++;
                        }catch (Exception $e){
                            $this->errorCount ++;
                            $this->errors[] = [
                                'row'    => $index + 2,
                                'errors' => [$e->getMessage()],
                                'data'   => $data ?? [],
                            ];
                        }
                    }

                    // Gửi thông báo kết quả
                    $this->sendImportResultNotification();
                }

                private function mapRowToData(array $row, array $fieldMapping)
                : array{
                    $data = [];
                    foreach ($fieldMapping as $field => $column){
                        $data[$field] = $row[$column] ?? NULL;
                    }

                    return $data;
                }

                private function sendImportResultNotification(){
                    try{
                        $message = "Nhập dữ liệu hoàn tất: {$this->successCount} bản ghi thành công, {$this->errorCount} bản ghi lỗi.";
                        $type    = $this->errorCount > 0 ? 'warning' : 'success';
                        $this->notificationService->sendFilamentNotification('Kết quả nhập dữ liệu',
                            $message, $type);
                    }catch (Exception $e){
                        Log::error("Failed to send import result notification: {$e->getMessage()}");
                    }
                }

                public function getResults()
                : array{
                    return [
                        'success_count' => $this->successCount,
                        'error_count'   => $this->errorCount,
                        'errors'        => $this->errors,
                    ];
                }
            };

            // Thực hiện nhập file
            \Maatwebsite\Excel\Facades\Excel::import($importClass, $file);

            // Trả về kết quả
            $results = $importClass->getResults();
            if ($results['error_count'] > 0){
                Log::warning('Import completed with errors', $results);
            }

            return $results;
        }catch (Exception $e){
            $this->logError('importFromFile', [
                'file'             => $file->getClientOriginalName(),
                'validation_rules' => $validationRules,
            ], $e);
            throw new BaseException('Không thể nhập dữ liệu từ file.', 500, [], [], $e);
        }
    }

    /**
     * Kiểm tra file hợp lệ
     *
     * @param \Illuminate\Http\UploadedFile $file File cần kiểm tra
     *
     * @return void
     * @throws BaseException
     */
    protected function validateFile($file)
    : void{
        try{
            if (!$file->isValid()){
                throw new Exception('File không hợp lệ hoặc bị lỗi khi tải lên.');
            }

            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension         = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)){
                throw new Exception("Định dạng file không được hỗ trợ: {$extension}.");
            }
        }catch (Exception $e){
            throw new BaseException('File không hợp lệ.', 422, [], [], $e);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (file, validation_rules, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in ImportService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}