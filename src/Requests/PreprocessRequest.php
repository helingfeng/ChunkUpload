<?php
namespace ChunkUpload\Requests;

class PreprocessRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'resource_name' => 'required',
            'resource_size' => 'required',
        ];
    }
}
