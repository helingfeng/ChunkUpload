<?php
namespace ChunkUpload\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OSS\OssClient;

class UploadController extends Controller
{

    protected $ossClient;

    protected $bucket;

    public function __construct()
    {
        $access_id = env('OSS_ACCESS_ID');
        $access_key = env('OSS_ACCESS_KEY');
        $endpoint_internal = env('OSS_END_POINT_INTERNAL');
        $is_cdn = env('OSS_IS_CDN', true);
        $this->ossClient = new OssClient($access_id, $access_key, $endpoint_internal, $is_cdn);
        $this->bucket = env('OSS_BUCKET');
    }

    public function test()
    {
        return $this->ossClient->uploadFile($this->bucket, 'design/image/201904231411229999.txt', storage_path('app/a.txt'));
        // return $this->ossClient->getObject($this->bucket, 'design/image/201904231411229999.jpg');
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

        $allow_extension = ["png", "jpg", "jpeg", "gif"];
        $allow_max_file_size = 1000 * 1024 * 1024;
        $part_size = 1 * 1024 * 1024;
        if (!in_array($extension, $allow_extension)) {
            // return $this->fail('不支持上传文件格式:' . $extension);
        }
        if ($resource_size >= $allow_max_file_size) {
            // return $this->fail('文件大小超出限制:' . $allow_max_file_size);
        }
        $object = 'design_material/' . date('YmdHis') . rand(100000, 999999) . '.' . $extension;
        $upload_id = $this->ossClient->initiateMultipartUpload($this->bucket, $object);
        $pieces = $this->ossClient->generateMultiuploadParts($resource_size, $part_size);

        // return $this->ossClient::OSS_SEEK_TO;
        // return $this->ossClient::OSS_LENGTH;

        $data['object'] = $object;
        $data['upload_id'] = $upload_id;
        $data['pieces_count'] = count($pieces);
        $data['part_size'] = $part_size;
        $data['extension'] = $extension;
        $data['resource_size'] = $resource_size;
        $data['resource_name'] = $resource_name;

        $data['pieces'] = $pieces;

        return response()->json($data);
    }

    /**
     * 分片上传处理
     */
    public function uploading(Request $request)
    {
        // $fromPos = $uploadPosition + (integer)$piece[$ossClient::OSS_SEEK_TO];
        // $toPos = (integer)$piece[$ossClient::OSS_LENGTH] + $fromPos - 1;
        $chunk_file = $request->file('chunk_file');
        $part_index = $request->input('part_index');
        $chunk_count = $request->input('chunk_count');
        $object = $request->input('oss_object');
        $upload_id = $request->input('upload_id');
        $parts = $request->input('parts', '');

        // $filename = storage_path('app/public') . '/' . uniqid();
        // copy($chunk_file->getRealPath(), $filename);

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
            printf(__FUNCTION__ . ": initiateMultipartUpload, uploadPart - part#{$i} FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }

        $result = ['part_index' => $part_index, 'chunk_count' => $chunk_count, 'part' => $res_upload_part];
        // 是否完成所有分片上传
        if ($part_index >= $chunk_count) {

            $part_arr = explode(',', $parts);
            $upload_parts = array();
            $index = 1;
            foreach ($part_arr as $part) {
                if(!empty($part)){
                    $upload_parts[] = array(
                        'PartNumber' => $index++,
                        'ETag' => $part,
                    );
                }
            }
            $upload_parts[] = array(
                'PartNumber' => $index,
                'ETag' => $res_upload_part,
            );

            // var_dump($upload_parts);
            // exit();

            try {
                $this->ossClient->completeMultipartUpload($this->bucket, $object, $upload_id, $upload_parts);
                $result['resource'] = $object;
            } catch (OssException $e) {
                printf(__FUNCTION__ . ": completeMultipartUpload FAILED\n");
                printf($e->getMessage() . "\n");
                return;
            }
        }

        return response()->json($result);
    }

    /**
     * 取消上传
     */
    public function abortUpload()
    {
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
