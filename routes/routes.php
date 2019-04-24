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

    $router->post('/preprocess', '\ChunkUpload\Controllers\UploadController@preprocess')->name('chunk-preprocess');
    $router->post('/uploading', '\ChunkUpload\Controllers\UploadController@uploading')->name('chunk-uploading');

    $router->get('/test', '\ChunkUpload\Controllers\UploadController@test');

    
});