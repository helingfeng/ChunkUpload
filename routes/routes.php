<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chunk Upload Routes
|--------------------------------------------------------------------------
|
*/
Route::namespace('\ChunkUpload\Controllers')->group(function (Router $router) {
    // 上传预处理
    $router->post('/preprocess', 'UploadController@preprocess')->name('chunk-preprocess');
    // 分块上传
    $router->post('/uploading', 'UploadController@uploading')->name('chunk-uploading');
    // 演示程序
    $router->get('/show-upload-example', 'ExampleController@index');
});
