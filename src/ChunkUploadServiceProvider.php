<?php

namespace ChunkUpload;

use Illuminate\Support\ServiceProvider;

class ChunkUploadServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../views', 'chunk-upload');
        $this->publishes([
            __DIR__ . '/../config/chunk_upload.php' => config_path('chunk_upload.php'),
        ], 'chunk_upload');

        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/../routes/routes.php';
        }
    }

    public function register()
    {
        $this->app->bind(MultipartUpload::class, function ($app) {
            $driver = $app['config']->get('chunk_upload.driver');
            switch (strtoupper($driver)) {
                case 'OSS':
                    return new OssUploadClient();
                default:
                    return new LocalUploadClient();
            }
        });
    }

}
