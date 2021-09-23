<?php

namespace ChunkUpload;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OSS\Core\OssException;
use OSS\OssClient;

class OssUploadClient implements MultipartUpload
{
    // OSS SDK 客户端
    protected $ossClient;

    // 上传 Bucket
    protected $bucket;


    /**
     * OssUploadClient constructor.
     * @throws OssException
     */
    public function __construct()
    {
        $access_id = config('chunk_upload.oss.access_id');
        $access_key = config('chunk_upload.oss.access_key');
        $endpoint_internal = config('chunk_upload.oss.endpoint_internal');
        $is_cdn = config('chunk_upload.oss.is_cdn');
        $this->bucket = config('chunk_upload.oss.bucket');

        $this->ossClient = new OssClient($access_id, $access_key, $endpoint_internal, $is_cdn);
    }

    /**
     * @param $filepath
     * @return string
     * @throws OssException
     */
    public function initiateUpload($filepath): string
    {
        return $this->ossClient->initiateMultipartUpload($this->bucket, $filepath);
    }

    public function generateParts($fileSize, $partSize): array
    {
        return $this->ossClient->generateMultiuploadParts($fileSize, $partSize);
    }

    /**
     * @param $uploadId
     * @param $filepath
     * @param $chunkCount
     * @throws OssException
     */
    protected function completeUpload($uploadId, $filepath, $chunkCount)
    {
        $uploadParts = [];
        for ($i = 1; $i <= $chunkCount; $i++) {
            $tag = Cache::get("{$uploadId}_{$i}");
            Log::debug("{$uploadId}:{$i}:{$tag}");
            array_push($uploadParts, [
                'PartNumber' => $i,
                'ETag' => $tag,
            ]);
        }
        $this->ossClient->completeMultipartUpload($this->bucket, $filepath, $uploadId, $uploadParts);
    }

    /**
     * @param UploadedFile $file
     * @param $partIndex
     * @param $chunkCount
     * @param $filepath
     * @param $uploadId
     * @return array
     * @throws OssException
     */
    public function uploadPart(UploadedFile $file, $partIndex, $chunkCount, $filepath, $uploadId): array
    {
        $options = array(
            $this->ossClient::OSS_FILE_UPLOAD => $file->getRealPath(),
            $this->ossClient::OSS_PART_NUM => $partIndex,
            $this->ossClient::OSS_SEEK_TO => 0,
            $this->ossClient::OSS_LENGTH => filesize($file->getRealPath()) - 1,
            $this->ossClient::OSS_CHECK_MD5 => false,
        );
        // upload_part 是由每个分片的 ETag 和 分片号（PartNumber）组成的数组。
        $partTag = $this->ossClient->uploadPart($this->bucket, $filepath, $uploadId, $options);
        $partTag = trim($partTag, '"');
        Cache::put("{$uploadId}_{$partIndex}", $partTag);

        if ($partIndex >= $chunkCount) {
            $this->completeUpload($uploadId, $filepath, $chunkCount);
            return [
                'result_code' => 'COMPLETE',
                'part_tag' => $partTag,
                'file_path' => $filepath,
                'file_url' => 'https://' . config('chunk_upload.oss.endpoint') . '/' . $filepath
            ];
        }
        return ['result_code' => 'PART_DONE', 'part_tag' => $partTag];
    }
}
