<?php

return [

    // OSS ACCESS_ID
    'access_id' => env('OSS_ACCESS_ID', ''),
    // OSS ACCESS_KEY
    'access_key' => env('OSS_ACCESS_KEY', ''),
    // OSS bucket
    'bucket' => env('OSS_BUCKET', ''),
    // OSS endpoint
    'endpoint' => env('OSS_END_POINT', ''),
    // OSS endpoint_internal
    'endpoint_internal' => env('OSS_END_POINT_INTERNAL', ''),

    'cdnDomain' => env('OSS_CDN_DOMAIN', ''),

    // 是否开启 HTTPS
    'ssl' => env('OSS_SSL', true),
    // 是否开启 CDN
    'is_cdn' => env('OSS_IS_CDN', true),
    // 是否调试模式
    'debug' => env('OSS_DEBUG', true),

    // 上传文件格式限制
    'allow_extension' => ["png", "jpg", "jpeg", "gif"],
    // 上传文件大小限制
    'allow_max_file_size' => 5000 * 1024 * 1024,
    // 分块大小
    'part_size' => 1 * 1024 * 1024,
    // 上传文件 OSS 路径
    'upload_path' => 'chunk_file',

];
