<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>测试大文件上传</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css"
        integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <script src="http://libs.baidu.com/jquery/1.9.0/jquery.min.js"></script>
</head>

<body>
    <h1>文件上传</h1>


    <input type="file" name="upload_file" id="upload_file" />
    <div>
        <span id="console_output"> console output </span>
    </div>
</body>
<script>
    ; (function ($) {
        $.fn.chunkUpload = function (options) {
            var dft = {
                // 预处理
                preprocessRoute: '',
                // 上传分块文件
                uploadingRoute: '',
                // 选中文件
                start: function (file) {
                    console.log(file);
                },
                // 分块上传进度
                progress: function (idx, size) {
                    console.log(idx, size);
                },
                // 文件上传完成
                success: function (resource) {
                    console.log(resource);
                }
            };
            var setting = $.extend(dft, options);

            var file, fileName, fileSize, fileExtension;
            var chunkIndex = 0;
            var chunkCount = 0;

            var chunkSize = 2 * 1024 * 1024;
            var uploadId = 'uploadId';
            var ossObject;
            var upload_parts = [];

            var preprocess = function () {
                $.ajax({
                    url: setting.preprocessRoute,
                    type: 'POST',
                    dataType: 'json',
                    xhrFields: {
                        withCredentials: true
                    },
                    crossDomain: true,
                    data: {
                        resource_name: fileName,
                        resource_size: fileSize
                    },
                    success: function (rst) {
                        uploadId = rst.upload_id;
                        chunkCount = rst.pieces_count;
                        chunkSize = rst.part_size;
                        fileExtension = rst.extension;
                        ossObject = rst.object;
                        uploading();
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        console.log('preprocess ajax error.');
                    }
                });
            };

            var uploading = function () {
                var start = chunkIndex * chunkSize;
                var end = fileSize <= chunkSize ? fileSize : ((fileSize - start) > chunkSize ? (chunkSize + start) : (fileSize));
                end = end - 1;
                console.log(start, end);

                var form = new FormData();
                form.append('chunk_file', file.slice(start, end));
                form.append('extension', fileExtension);
                form.append('part_index', chunkIndex + 1);
                form.append('chunk_count', chunkCount);
                form.append('upload_id', uploadId);
                form.append('oss_object', ossObject);
                form.append('parts', upload_parts.join(','));

                $.ajax({
                    url: setting.uploadingRoute,
                    type: 'POST',
                    data: form,
                    dataType: 'json',
                    xhrFields: {
                        withCredentials: true
                    },
                    cache: false,
                    crossDomain: true,
                    // async: false,
                    processData: false,
                    contentType: false,
                    success: function (rst) {
                        setting.progress(rst.part_index, rst.chunk_count);
                        upload_parts.push(rst.part);
                        // 所有分块都已经上传
                        if (Number(rst.part_index) >= Number(rst.chunk_count)) {
                            setting.success(rst.resource);
                        } else {
                            // 继续上传分块
                            chunkIndex++;
                            setTimeout(function () { uploading() }, 1);
                        }
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        console.log('uploading ajax error.');
                    }
                });
            };

            // 绑定事件
            $(this).change(function (event) {
                file = event.target.files[0];
                fileName = file.name;
                fileSize = file.size;
                setting.start(file);
                preprocess();
            });
        };
    })(jQuery);

    $(function () {
        $("#upload_file").chunkUpload({
            preprocessRoute: "{{ route('chunk-preprocess') }}",
            uploadingRoute: "{{ route('chunk-uploading') }}",
            progress: function(idx, size){
                $('#console_output').text(((idx/size)*100).toFixed(0) + '%');
            }
        });
    });
</script>

</html>