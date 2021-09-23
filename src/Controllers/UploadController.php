<?php

namespace ChunkUpload\Controllers;

use ChunkUpload\BaseController;
use ChunkUpload\MultipartUpload;
use ChunkUpload\Requests\PartUploadRequest;
use ChunkUpload\Requests\PreprocessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class UploadController extends BaseController
{
    /**
     * 大文件预处理，得到 UploadId 和 Pieces 信息
     * @param PreprocessRequest $request
     * @param MultipartUpload $multipartUpload
     * @return JsonResponse
     */
    public function preprocess(PreprocessRequest $request, MultipartUpload $multipartUpload): JsonResponse
    {
        $resourceName = $request->input('resource_name');
        $resourceSize = $request->input('resource_size', 0);
        $extension = explode('.', $resourceName);
        $extension = end($extension);

        $allowExtension = config('chunk_upload.allow_extension');
        $allowMaxFileSize = config('chunk_upload.allow_max_file_size');
        $partSize = config('chunk_upload.part_size');

        if (!in_array($extension, $allowExtension)) {
            return $this->responseJson('不支持上传文件格式:' . $extension, 400);
        }
        if ($resourceSize >= $allowMaxFileSize) {
            return $this->responseJson('文件大小超出限制:' . $allowMaxFileSize, 400);
        }
        $filepath = config('chunk_upload.upload_path') . DIRECTORY_SEPARATOR . uniqid() . '.' . $extension;

        $uploadId = $multipartUpload->initiateUpload($filepath);
        $pieces = $multipartUpload->generateParts($resourceSize, $partSize);

        $data['file_path'] = $filepath;
        $data['upload_id'] = $uploadId;
        $data['part_size'] = $partSize;
        $data['extension'] = $extension;
        $data['resource_size'] = $resourceSize;
        $data['resource_name'] = $resourceName;
        $data['pieces'] = $pieces;
        $data['pieces_count'] = count($pieces);

        Cache::put("{$uploadId}_preprocess", $data);
        return $this->responseJson($data);
    }

    /**
     * 分片上传处理
     * @param PartUploadRequest $request
     * @param MultipartUpload $multipartUpload
     * @return JsonResponse|void
     */
    public function uploading(PartUploadRequest $request, MultipartUpload $multipartUpload): JsonResponse
    {
        $partFile = $request->file('chunk_file');
        $uploadId = $request->input('upload_id');
        $partIndex = $request->input('part_index');

        $preprocess = Cache::get("{$uploadId}_preprocess");
        $result = $multipartUpload->uploadPart($partFile, $partIndex, $preprocess['pieces_count'], $preprocess['file_path'], $uploadId);
        return $this->responseJson($result);
    }
}
