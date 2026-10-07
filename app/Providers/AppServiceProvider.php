<?php

namespace App\Providers;

use App\Http\Responses\PasskeyLoginResponse;
use App\Services\MinioService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();

        // Daftarkan MinioService sebagai singleton
        $this->app->singleton(MinioService::class, function ($app) {
            return new MinioService();
        });

        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);

        // Alias agar bisa dipanggil dengan 'minio'
        $this->app->alias(MinioService::class, 'minio');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        config([
            'passkeys.redirect' => '/dashboard',
            'passkeys.management_middleware' => [],
        ]);

        // Register S3 driver support untuk Flysystem
        // Ini memastikan AWS S3Client tersedia saat filesystem manager diakses
        \Illuminate\Support\Facades\Storage::resolved(function ($storage) {
            // S3 driver otomatis diload oleh League\Flysystem\AwsS3V3
        });
    }
}
