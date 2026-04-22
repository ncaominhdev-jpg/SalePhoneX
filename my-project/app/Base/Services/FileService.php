<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * FileService - Lớp tiện ích quản lý file
 *
 * Tính năng:
 * - Upload file với validation và lưu trữ trên disk
 * - Tải xuống file hoặc tạo URL tạm thời
 * - Xóa file khỏi storage
 * - Thông báo kết quả qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException
 */
class FileService{

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
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService){
        $this->notificationService = $notificationService;
    }

    /**
     * Upload file lên storage
     *
     * @param UploadedFile $file            File cần upload
     * @param string       $disk            Disk lưu trữ (local, s3)
     * @param string       $path            Đường dẫn lưu trữ
     * @param array        $validationRules Quy tắc validation (mimes, max)
     *
     * @return array Thông tin file (path, url, name)
     * @throws BaseException
     */
    public function upload(
        UploadedFile $file,
        string $disk = 'local',
        string $path = 'uploads',
        array $validationRules = [])
    : array{
        try{
            // Validation mặc định
            $defaultRules = [
                'file' => ['required', 'file', 'max:10240', 'mimes:jpg,png,pdf,doc,docx,xls,xlsx,csv'], // 10MB max
            ];

            // Gộp rules tùy chỉnh
            $rules     = array_merge($defaultRules, ['file' => $validationRules]);
            $validator = Validator::make(['file' => $file], $rules);

            if ($validator->fails()){
                throw new Exception($validator->errors()->first());
            }

            // Tạo tên file duy nhất
            $extension = $file->getClientOriginalExtension();
            $filename  = Str::uuid() . '.' . $extension;
            $fullPath  = rtrim($path, '/') . '/' . $filename;

            // Lưu file
            Storage::disk($disk)->put($fullPath, file_get_contents($file->getRealPath()));

            // Lấy URL
            $url = $disk === 's3' ? Storage::disk($disk)
                                           ->temporaryUrl($fullPath,
                                               now()->addHours(24)) : Storage::disk($disk)
                                                                             ->url($fullPath);

            $this->notificationService->sendFilamentNotification(
                'Upload thành công',
                "File {$file->getClientOriginalName()} đã được upload.",
                'success'
            );

            Log::info("File uploaded: {$fullPath}", [
                'disk'          => $disk,
                'original_name' => $file->getClientOriginalName(),
                'user_id'       => auth()->id() ?? 'system',
            ]);

            return [
                'path'          => $fullPath,
                'url'           => $url,
                'name'          => $filename,
                'original_name' => $file->getClientOriginalName(),
            ];
        }catch (Exception $e){
            $this->logError('upload', [
                'disk'          => $disk,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
            ], $e);
            throw new BaseException('Không thể upload file.', 500, [], [], $e);
        }
    }

    /**
     * Tải xuống file từ storage
     *
     * @param string      $path     Đường dẫn file
     * @param string      $disk     Disk lưu trữ
     * @param string|null $filename Tên file khi tải xuống (mặc định: tên gốc)
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws BaseException
     */
    public function download(string $path, string $disk = 'local', ?string $filename = NULL)
    : \Symfony\Component\HttpFoundation\StreamedResponse{
        try{
            if (!Storage::disk($disk)->exists($path)){
                throw new Exception('File không tồn tại.');
            }

            $filename = $filename ?? basename($path);

            Log::info("File downloaded: {$path}", [
                'disk'     => $disk,
                'filename' => $filename,
                'user_id'  => auth()->id() ?? 'system',
            ]);

            return Storage::disk($disk)->download($path, $filename);
        }catch (Exception $e){
            $this->logError('download', [
                'path'     => $path,
                'disk'     => $disk,
                'filename' => $filename,
            ], $e);
            throw new BaseException('Không thể tải xuống file.', 500, [], [], $e);
        }
    }

    /**
     * Xóa file khỏi storage
     *
     * @param string $path Đường dẫn file
     * @param string $disk Disk lưu trữ
     *
     * @return bool
     * @throws BaseException
     */
    public function delete(string $path, string $disk = 'local')
    : bool{
        try{
            if (!Storage::disk($disk)->exists($path)){
                return TRUE; // File không tồn tại, coi như xóa thành công
            }

            $result = Storage::disk($disk)->delete($path);

            if ($result){
                $this->notificationService->sendFilamentNotification(
                    'Xóa thành công',
                    "File {$path} đã được xóa.",
                    'success'
                );
            }

            Log::info("File deleted: {$path}", [
                'disk'    => $disk,
                'user_id' => auth()->id() ?? 'system',
            ]);

            return $result;
        }catch (Exception $e){
            $this->logError('delete', [
                'path' => $path,
                'disk' => $disk,
            ], $e);
            throw new BaseException('Không thể xóa file.', 500, [], [], $e);
        }
    }

    /**
     * Lấy URL tạm thời cho file (cho S3)
     *
     * @param string $path     Đường dẫn file
     * @param string $disk     Disk lưu trữ
     * @param int    $expireIn Giờ hết hạn (giờ)
     *
     * @return string
     * @throws BaseException
     */
    public function getTemporaryUrl(string $path, string $disk = 's3', int $expireIn = 24)
    : string{
        try{
            if (!Storage::disk($disk)->exists($path)){
                throw new Exception('File không tồn tại.');
            }

            $url = Storage::disk($disk)->temporaryUrl($path, now()->addHours($expireIn));

            Log::info("Temporary URL generated: {$path}", [
                'disk'      => $disk,
                'expire_in' => $expireIn,
                'user_id'   => auth()->id() ?? 'system',
            ]);

            return $url;
        }catch (Exception $e){
            $this->logError('getTemporaryUrl', [
                'path'      => $path,
                'disk'      => $disk,
                'expire_in' => $expireIn,
            ], $e);
            throw new BaseException('Không thể tạo URL tạm thời.', 500, [], [], $e);
        }
    }

    /**
     * Ghi log lỗi
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh (path, disk, filename, v.v.)
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function logError(string $method, array $context, Exception $exception)
    : void{
        Log::error("Error in FileService::{$method}: {$exception->getMessage()}",
            array_merge($context, [
                'user_id' => auth()->id() ?? 'system',
                'trace'   => $exception->getTraceAsString(),
            ]));
    }
}