<?php
namespace ChunkUpload\Requests;

class PartUploadRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'chunk_file' => 'required',
            'part_index' => 'required',
            'upload_id' => 'required',
        ];
    }
}
