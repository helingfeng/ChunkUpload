# ChunkUpload
Laravel 超大文件上传 OSS 存储，前端对文件进行分块上传，并监听每个文件块的上传事件，后端接收文件块并合并为最终文件

## Composer 安装

```
composer require helingfeng/laravel-chunk-upload-oss
```

Laravel>=5.5 不需要配置 Provider， 如果不是，请在 `config/app.php` 下添加 `ChunkUpload\ChunkUploadServiceProvider::class`

```
php artisan vendor:publish --provider "ChunkUpload\ChunkUploadServiceProvider"
```

## 配置 OSS 存储 ACCESS_ID 与 ACCESS_KEY

打开 `config/chunk_upload.php` 配置文件，并在 `.env` 添加相应配置项
```php
// OSS ACCESS_ID
'access_id' => env('OSS_ACCESS_ID', ''),
// OSS ACCESS_KEY
'access_key' => env('OSS_ACCESS_KEY', ''),
// OSS bucket
'bucket' => env('OSS_BUCKET', ''),
...
```

## Example 示例

启动项目，并访问路径：https://yourhosts/example


<video src="./demo.mp4" width="320" height="180" controls="controls"></video>

