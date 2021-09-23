<?php
/**
 * User: helingfeng
 */

namespace ChunkUpload;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OSS\OssClient;

class LocalUploadClient implements MultipartUpload
{
    protected $uploadDir;

    public function __construct()
    {
        $this->uploadDir = storage_path('app/public/' . trim(config('chunk_upload.upload_path'), '/'));
    }

    public function initiateUpload($filepath): string
    {
        $uploadId = strtoupper(md5(uniqid()));
        mkdir($this->uploadDir . DIRECTORY_SEPARATOR . $uploadId, 0755, true);
        return $uploadId;
    }

    public function generateMultiuploadParts($fileSize, $partSize = 5242880): array
    {
        $i = 0;
        $sizeCount = $fileSize;
        $values = array();
        $partSize = $this->computePartSize($partSize);
        while ($sizeCount > 0) {
            $sizeCount = $sizeCount - $partSize;
            $values[] = array(
                OssClient::OSS_SEEK_TO => ($partSize * $i),
                OssClient::OSS_LENGTH => (($sizeCount > 0) ? $partSize : ($sizeCount + $partSize)),
            );
            $i++;
        }
        return $values;
    }

    private function computePartSize($partSize): int
    {
        $partSize = (integer)$partSize;
        if ($partSize <= OssClient::OSS_MIN_PART_SIZE) {
            $partSize = OssClient::OSS_MIN_PART_SIZE;
        } elseif ($partSize > OssClient::OSS_MAX_PART_SIZE) {
            $partSize = OssClient::OSS_MAX_PART_SIZE;
        }
        return $partSize;
    }

    public function generateParts($fileSize, $partSize): array
    {
        return $this->generateMultiuploadParts($fileSize, $partSize);
    }

    protected function completeUpload($uploadId, $filepath, $chunkCount)
    {
        for ($i = 1; $i <= $chunkCount; $i++) {
            $partTag = $uploadId . '_' . $i;
            $partFilepath = $this->uploadDir . DIRECTORY_SEPARATOR . $uploadId . DIRECTORY_SEPARATOR . $partTag;
            file_put_contents(storage_path("app/public/{$filepath}"), file_get_contents($partFilepath), FILE_APPEND);
        }
    }

    public function uploadPart(UploadedFile $file, $partIndex, $chunkCount, $filepath, $uploadId): array
    {
        $partTag = $uploadId . '_' . $partIndex;
        $partFilepath = $this->uploadDir . DIRECTORY_SEPARATOR . $uploadId . DIRECTORY_SEPARATOR . $partTag;
        file_put_contents($partFilepath, file_get_contents($file->getRealPath()));

        if ($partIndex >= $chunkCount) {
            $this->completeUpload($uploadId, $filepath, $chunkCount);
            return ['result_code' => 'COMPLETE', 'part_tag' => $partTag, 'file_path' => $filepath, 'file_url' => Storage::disk('public')->url($filepath)];
        }
        return ['result_code' => 'PART_DONE', 'part_tag' => $partTag];
    }
}
