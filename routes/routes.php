<?php
use Illuminate\Routing\Router;

/*
|--------------------------------------------------------------------------
| Chunk Upload Routes
|--------------------------------------------------------------------------
|
*/

Route::group([], function(Router $router){
    
    $router->get('/example', function () {
        return view('chunk-upload::example');
    });

    // 上传预处理
    $router->post('/preprocess', '\ChunkUpload\Controllers\UploadController@preprocess')->name('chunk-preprocess');
    // 分块上传
    $router->post('/uploading', '\ChunkUpload\Controllers\UploadController@uploading')->name('chunk-uploading');

});