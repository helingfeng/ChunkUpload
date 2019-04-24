<?php

namespace ChunkUpload;

use Illuminate\Support\ServiceProvider;

class ChunkUploadServiceProvider extends ServiceProvider
{

    protected $defer = false;

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
        //
    }

}
