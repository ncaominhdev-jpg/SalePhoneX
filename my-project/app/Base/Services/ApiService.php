<?php

namespace App\Base\Services;

use App\Base\Exceptions\BaseException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * ApiService - Lớp tiện ích quản lý giao tiếp với API bên ngoài
 *
 * Tính năng:
 * - Gửi yêu cầu HTTP (GET, POST, PUT, DELETE) tới API
 * - Xử lý retry khi gặp lỗi (timeout, rate limit)
 * - Quản lý authentication (API key, OAuth, Bearer token)
 * - Thông báo lỗi nghiêm trọng qua NotificationService
 * - Xử lý lỗi và ghi log với BaseException và LogService
 */
class ApiService{

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
     * Dịch vụ cài đặt
     *
     * @var SettingService
     */
    protected $settingService;

    /**
     * Client HTTP
     *
     * @var Client
     */
    protected $client;

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
     * @param SettingService      $settingService
     */
    public function __construct(
        NotificationService $notificationService,
        LogService $logService,
        SettingService $settingService){
        $this->notificationService = $notificationService;
        $this->logService          = $logService;
        $this->settingService      = $settingService;
        $this->client              = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Gửi yêu cầu HTTP tới API
     *
     * @param string $method   Phương thức HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint URL API endpoint
     * @param array  $data     Dữ liệu gửi (body hoặc query params)
     * @param array  $headers  Headers tùy chỉnh
     * @param array  $config   Cấu hình API (api_key, token, base_url, retry_attempts)
     *
     * @return array Phản hồi từ API
     * @throws BaseException
     */
    public function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        array $config = [])
    : array{
        try{
            // Lấy cấu hình từ SettingService hoặc config
            $baseUrl       = Arr::get($config, 'base_url',
                $this->settingService->get('api_base_url', env('API_BASE_URL')));
            $apiKey        = Arr::get($config, 'api_key',
                $this->settingService->get('api_key', env('API_KEY')));
            $token         = Arr::get($config, 'token',
                $this->settingService->get('api_token', env('API_TOKEN')));
            $retryAttempts = Arr::get($config, 'retry_attempts', 3);

            // Xây dựng headers
            $defaultHeaders = [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if ($apiKey){
                $defaultHeaders['X-API-Key'] = $apiKey;
            }
            if ($token){
                $defaultHeaders['Authorization'] = "Bearer {$token}";
            }
            $headers = array_merge($defaultHeaders, $headers);

            // Xây dựng URL
            $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

            // Thực hiện yêu cầu với retry
            $response = $this->executeWithRetry($method, $url, $data, $headers, $retryAttempts);

            // Ghi log
            Log::info("API request successful: {$method} {$url}", [
                'response_status' => $response['status'],
                'user_id'         => auth()->id() ?? 'system',
            ]);

            return $response;
        }catch (Exception $e){
            $this->handleError('request', [
                'method'   => $method,
                'endpoint' => $endpoint,
                'data'     => $data,
                'headers'  => $this->sanitizeHeaders($headers),
            ], $e);
            throw new BaseException('Không thể gửi yêu cầu API.', 500, [], [], $e);
        }
    }

    /**
     * Thực hiện yêu cầu với retry
     *
     * @param string $method        Phương thức HTTP
     * @param string $url           URL đầy đủ
     * @param array  $data          Dữ liệu
     * @param array  $headers       Headers
     * @param int    $retryAttempts Số lần retry
     *
     * @return array
     * @throws Exception
     */
    protected function executeWithRetry(
        string $method,
        string $url,
        array $data,
        array $headers,
        int $retryAttempts)
    : array{
        $attempt = 0;
        $delay   = 1000; // ms

        while ($attempt < $retryAttempts){
            try{
                $options = [
                    'headers' => $headers,
                ];

                if (in_array(strtoupper($method), ['GET', 'DELETE'])){
                    $options['query'] = $data;
                }else{
                    $options['json'] = $data;
                }

                $response   = $this->client->request(strtoupper($method), $url, $options);
                $statusCode = $response->getStatusCode();
                $body       = json_decode($response->getBody()->getContents(), TRUE);

                return [
                    'status'  => $statusCode,
                    'data'    => $body,
                    'headers' => $response->getHeaders(),
                ];
            }catch (RequestException $e){
                $attempt ++;
                if ($attempt >= $retryAttempts || ($e->hasResponse() && $e->getResponse()
                                                                          ->getStatusCode() < 500)){
                    throw $e;
                }
                usleep($delay * 1000);
                $delay *= 2; // Exponential backoff
            }
        }

        throw new Exception('Yêu cầu API thất bại sau nhiều lần thử.');
    }

    /**
     * Gửi yêu cầu GET
     *
     * @param string $endpoint URL endpoint
     * @param array  $params   Query params
     * @param array  $headers  Headers tùy chỉnh
     * @param array  $config   Cấu hình API
     *
     * @return array
     * @throws BaseException
     */
    public function get(
        string $endpoint,
        array $params = [],
        array $headers = [],
        array $config = [])
    : array{
        return $this->request('GET', $endpoint, $params, $headers, $config);
    }

    /**
     * Gửi yêu cầu POST
     *
     * @param string $endpoint URL endpoint
     * @param array  $data     Dữ liệu body
     * @param array  $headers  Headers tùy chỉnh
     * @param array  $config   Cấu hình API
     *
     * @return array
     * @throws BaseException
     */
    public function post(
        string $endpoint,
        array $data = [],
        array $headers = [],
        array $config = [])
    : array{
        return $this->request('POST', $endpoint, $data, $headers, $config);
    }

    /**
     * Gửi yêu cầu PUT
     *
     * @param string $endpoint URL endpoint
     * @param array  $data     Dữ liệu body
     * @param array  $headers  Headers tùy chỉnh
     * @param array  $config   Cấu hình API
     *
     * @return array
     * @throws BaseException
     */
    public function put(string $endpoint, array $data = [], array $headers = [], array $config = [])
    : array{
        return $this->request('PUT', $endpoint, $data, $headers, $config);
    }

    /**
     * Gửi yêu cầu DELETE
     *
     * @param string $endpoint URL endpoint
     * @param array  $params   Query params
     * @param array  $headers  Headers tùy chỉnh
     * @param array  $config   Cấu hình API
     *
     * @return array
     * @throws BaseException
     */
    public function delete(
        string $endpoint,
        array $params = [],
        array $headers = [],
        array $config = [])
    : array{
        return $this->request('DELETE', $endpoint, $params, $headers, $config);
    }

    /**
     * Xử lý lỗi và gửi thông báo
     *
     * @param string    $method    Phương thức gặp lỗi
     * @param array     $context   Ngữ cảnh
     * @param Exception $exception Ngoại lệ
     *
     * @return void
     */
    protected function handleError(string $method, array $context, Exception $exception)
    : void{
        $message = "Error in ApiService::{$method}: {$exception->getMessage()}";
        $context = array_merge($context, [
            'user_id' => auth()->id() ?? 'system',
            'trace'   => $exception->getTraceAsString(),
        ]);

        $this->logService->logCritical($message, $context, $exception);

        $this->notificationService->sendFilamentNotification(
            'Lỗi API',
            $message,
            'error'
        );
    }

    /**
     * Loại bỏ thông tin nhạy cảm khỏi headers
     *
     * @param array $headers Headers
     *
     * @return array
     */
    protected function sanitizeHeaders(array $headers)
    : array{
        $sanitized = $headers;
        if (isset($sanitized['Authorization'])){
            $sanitized['Authorization'] = '****';
        }
        if (isset($sanitized['X-API-Key'])){
            $sanitized['X-API-Key'] = '****';
        }

        return $sanitized;
    }
}