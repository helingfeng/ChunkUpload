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
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>文件上传</h2>
            <input type="file" name="upload_file" id="upload_file"/>
        </div>

        <div class="col-md-12">
            <div class="progress" style="margin-top: 15px;">
                <div class="progress-bar" style="width: 0%;">
                    0%
                </div>
            </div>

            <div style="margin-top: 15px;">
                文件上传结果：<span id="file_url">-</span>
            </div>
        </div>
    </div>
</div>

</body>
<script>
    ;(function ($) {
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
            var pieces = [];

            var chunkSize = 2 * 1024 * 1024;
            var uploadId = 'uploadId';

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
                        pieces = rst.pieces;
                        chunkIndex = 0;
                        uploading();
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        console.log('preprocess ajax error.');
                    }
                });
            };

            var uploading = function () {
                var piece = pieces[chunkIndex];
                var start = piece['seekTo'];
                var end = piece['seekTo'] + piece['length'];

                console.log(start, end);

                var form = new FormData();
                form.append('chunk_file', file.slice(start, end));
                form.append('part_index', ++chunkIndex);
                form.append('upload_id', uploadId);

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
                        setting.progress(chunkIndex, chunkCount);
                        // 所有分块都已经上传
                        if (rst.result_code === 'COMPLETE') {
                            setting.success(rst.file_url);
                        } else if (rst.result_code === 'PART_DONE') {
                            // 继续上传分块
                            setTimeout(uploading, 100);
                        } else {
                            console.log(rst);
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
            progress: function (idx, size) {
                console.log(idx, size);
                var precent = ((idx / size) * 100).toFixed(0) + '%';
                $('.progress-bar').css('width', precent);
                $('.progress-bar').text(precent);
            },
            success: function (resource) {
                $('#file_url').html(`<a href="${resource}">${resource}</a>`);
            }
        });
    });
</script>

</html>
