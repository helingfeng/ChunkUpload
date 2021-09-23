<?php

namespace ChunkUpload;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    public function responseJson($data = [], $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        is_string($data) && $data = ['message' => $data];
        return response()->json($data, $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
