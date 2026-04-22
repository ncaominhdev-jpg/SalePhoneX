<?php

namespace App\Base\Providers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * BaseServiceProvider - Lớp cung cấp dịch vụ cho module Base
 *
 * Tính năng:
 * - Đăng ký binding cho BaseService và các dịch vụ con
 * - Cấu hình route, middleware, và tài nguyên Filament
 * - Tải cấu hình, migration, và translation
 * - Đăng ký lệnh artisan
 * - Khởi tạo module Base trong ứng dụng Laravel
 */
class BaseServiceProvider extends ServiceProvider{

    /**
     * Namespace cho module
     *
     * @var string
     */
    protected $namespace = 'Modules\\Base\\Http\\Controllers';

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected $throwCustomExceptions = TRUE;

    /**
     * Đăng ký các dịch vụ
     *
     * @return void
     * @throws \Exception
     */
    public function register(){
        try{
            // Đăng ký binding cho BaseService
            $this->app->bind(\Modules\Base\Services\BaseService::class, function ($app){
                return new \Modules\Base\Services\BaseService($app->make(\Modules\Base\Models\BaseModel::class));
            });

            // Tải cấu hình module
            $this->mergeConfigFrom(__DIR__ . '/../../config/base.php', 'base');
        }catch (Exception $e){
            Log::error("Failed to register BaseServiceProvider: {$e->getMessage()}", [
                'user_id' => auth()->id() ?? 'system',
            ]);
            $this->throwException('Không thể đăng ký dịch vụ module Base.', 500, $e);
        }
    }

    /**
     * Khởi tạo các dịch vụ
     *
     * @return void
     */
    public function boot(){
        try{
            // Tải route
            $this->registerRoutes();

            // Tải migration
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

            // Tải translation
            $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'base');

            // Tải view
            $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'base');

            // Xuất bản cấu hình
            $this->publishes([
                __DIR__ . '/../../config/base.php' => config_path('base.php'),
            ], 'base-config');

            // Xuất bản migration
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'base-migrations');

            // Đăng ký tài nguyên Filament
            $this->registerFilamentResources();

            // Đăng ký lệnh artisan
            if ($this->app->runningInConsole()){
                $this->commands([
                    // Thêm lệnh artisan nếu cần, ví dụ: \Modules\Base\Console\GenerateBaseCommand::class
                ]);
            }
        }catch (Exception $e){
            Log::error("Failed to boot BaseServiceProvider: {$e->getMessage()}", [
                'user_id' => auth()->id() ?? 'system',
            ]);
            $this->throwException('Không thể khởi tạo module Base.', 500, $e);
        }
    }

    /**
     * Đăng ký route cho module
     *
     * @return void
     */
    protected function registerRoutes(){
        Route::prefix('api/base')
             ->middleware(['api'])
             ->namespace($this->namespace)
             ->group(__DIR__ . '/../../routes/api.php');

        Route::prefix('base')
             ->middleware(['web'])
             ->namespace($this->namespace)
             ->group(__DIR__ . '/../../routes/web.php');
    }

    /**
     * Đăng ký tài nguyên Filament
     *
     * @return void
     */
    protected function registerFilamentResources(){
        // Đăng ký tài nguyên Filament nếu cần
        // Ví dụ: Filament::registerResources([\Modules\Base\Filament\Resources\BaseResource::class]);
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