<?php

namespace ChunkUpload;


use Illuminate\Http\UploadedFile;

interface MultipartUpload
{
    public function initiateUpload($filepath): string;

    public function generateParts($fileSize, $partSize): array;

    public function uploadPart(UploadedFile $file, $partIndex, $chunkCount, $filepath, $uploadId): array;
}
