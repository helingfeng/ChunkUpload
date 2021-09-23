<?php
namespace ChunkUpload\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @param Validator $validator
     */
    public function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->first();
        $allErrors = $validator->errors();

        $response = response()->json([
            'result_code' => 'param error',
            'message' => $error,
            'errors' => $allErrors
        ], 422)->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        throw new HttpResponseException($response);
    }
}
