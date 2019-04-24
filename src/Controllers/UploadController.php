<?php
namespace ChunkUpload\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OSS\OssClient;

class UploadController extends Controller
{
    // OSS SDK 客户端
    protected $ossClient;

    // 上传 Bucket
    protected $bucket;

    public function __construct()
    {
        $access_id = config('chunk_upload.access_id');
        $access_key = config('chunk_upload.access_key');
        $endpoint_internal = config('chunk_upload.endpoint_internal');
        $is_cdn = config('chunk_upload.is_cdn');
        $this->bucket = config('chunk_upload.bucket');
        $this->ossClient = new OssClient($access_id, $access_key, $endpoint_internal, $is_cdn);
    }

    /**
     * 大文件预处理，得到 UploadId 和 Pieces 信息
     */
    public function preprocess(Request $request)
    {
        $resource_name = $request->input('resource_name');
        $resource_size = $request->input('resource_size', 0);
        $extension = explode('.', $resource_name);
        $extension = end($extension);

        $allow_extension = config('chunk_upload.allow_extension');
        $allow_max_file_size = config('chunk_upload.allow_max_file_size');
        $part_size = config('chunk_upload.part_size');

        if (!in_array($extension, $allow_extension)) {
            // return $this->fail('不支持上传文件格式:' . $extension);
        }
        if ($resource_size >= $allow_max_file_size) {
            // return $this->fail('文件大小超出限制:' . $allow_max_file_size);
        }
        $object = config('chunk_upload.upload_path') . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR . $extension;
        // 初始化分块上传，得到 upload_id
        $upload_id = $this->ossClient->initiateMultipartUpload($this->bucket, $object);
        $pieces = $this->ossClient->generateMultiuploadParts($resource_size, $part_size);

        $data['object'] = $object;
        $data['upload_id'] = $upload_id;
        $data['part_size'] = $part_size;
        $data['extension'] = $extension;
        $data['resource_size'] = $resource_size;
        $data['resource_name'] = $resource_name;
        $data['pieces'] = $pieces;
        $data['pieces_count'] = count($pieces);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 分片上传处理
     */
    public function uploading(Request $request)
    {
        $chunk_file = $request->file('chunk_file');
        $part_index = $request->input('part_index');
        $chunk_count = $request->input('chunk_count');
        // 分块所属文件对象
        $object = $request->input('oss_object');
        // 分块所属 upload_id
        $upload_id = $request->input('upload_id');
        // 历史分块标签 Tags
        $parts = $request->input('parts', '');

        $options = array(
            $this->ossClient::OSS_FILE_UPLOAD => $chunk_file->getRealPath(),
            $this->ossClient::OSS_PART_NUM => $part_index,
            $this->ossClient::OSS_SEEK_TO => 0,
            $this->ossClient::OSS_LENGTH => filesize($chunk_file->getRealPath()) - 1,
            $this->ossClient::OSS_CHECK_MD5 => false,
        );

        try {
            // upload_part 是由每个分片的 ETag 和 分片号（PartNumber）组成的数组。
            $res_upload_part = $this->ossClient->uploadPart($this->bucket, $object, $upload_id, $options);
        } catch (OssException $e) {
            printf($e->getMessage() . "\n");
            return;
        }

        $result = ['part_index' => $part_index, 'chunk_count' => $chunk_count, 'part' => $res_upload_part];
        // 是否完成所有分片上传
        if ($part_index >= $chunk_count) {
            // 组装所有分块 Tags 信息
            $part_arr = explode(',', $parts);
            $upload_parts = array();
            $index = 1;
            foreach ($part_arr as $part) {
                if (!empty($part)) {
                    $upload_parts[] = array(
                        'PartNumber' => $index++,
                        'ETag' => $part,
                    );
                }
            }
            // 最后一个完成上传的分块
            $upload_parts[] = array(
                'PartNumber' => $index,
                'ETag' => $res_upload_part,
            );
            try {
                $this->ossClient->completeMultipartUpload($this->bucket, $object, $upload_id, $upload_parts);
                $result['resource'] = $object;
            } catch (OssException $e) {
                printf($e->getMessage() . "\n");
                return;
            }
        }
        return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 取消上传
     */
    public function abortUpload()
    {
        // todo
        // try{
        //     $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        //     $ossClient->abortMultipartUpload($bucket, $object, $upload_id);
        // } catch(OssException $e) {
        //     printf(__FUNCTION__ . ": FAILED\n");
        //     printf($e->getMessage() . "\n");
        //     return;
        // }
    }

}
