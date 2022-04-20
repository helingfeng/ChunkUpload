wx.chooseMessageFile({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success (res) {
        console.log(res)
        const tempFilePaths = res.tempFiles;

        const fs = wx.getFileSystemManager();

        var pieces = [];
        var part_index = 0;
        var upload_id = '';

        // 分片上传操作
        var uploading = function(file_path) {
          var piece = pieces[part_index]
          part_index++;

          console.log(part_index, piece);
          fs.readFile({
            filePath: file_path,
            position: piece['seekTo'],
            length: piece['length'],
            success(res) {
              fs.writeFile({
                filePath: `${wx.env.USER_DATA_PATH}/test_${part_index}`,
                data: res.data,
                success(wres) {
                  wx.uploadFile({
                    //仅为示例，非真实的接口地址
                    url: 'http://127.0.0.1:8000/uploading',
                    filePath: `${wx.env.USER_DATA_PATH}/test_${part_index}`,
                    name: 'chunk_file',
                    formData: {
                      'part_index': part_index,
                      'upload_id' : upload_id
                    },
                    success (res){
                      console.log(res)
                      var response = JSON.parse(res.data)
                      if(response.result_code === 'COMPLETE') {
                        console.log(response.file_path)
                      }else if(response.result_code === 'PART_DONE') {
                        setTimeout(function() {
                          uploading(file_path)
                        }, 500)
                      }
                    }
                  })
                },
                fail(wres) {
                  console.error(wres)
                }
              })
            },
            fail(res) {
              console.error(res)
            }
          })
        }
        
        // 上传预处理，获取文件分片信息
        wx.request({
          url: 'http://127.0.0.1:8000/preprocess',
          method: 'POST',
          data: {
            resource_name: tempFilePaths[0]["name"],
            resource_size: tempFilePaths[0]['size']
          },
          success (res) {
            console.log(res)
            upload_id = res.data.upload_id;
            pieces = res.data.pieces;
            uploading(tempFilePaths[0]['path'])
          }
        })
  
      }
    })
