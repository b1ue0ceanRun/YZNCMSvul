layui.use(['layer', 'form', 'tableSelect', 'dragsort', 'tagsinput', 'colorpicker'], function() {
    var layer = layui.layer,
        form = layui.form,
        tagsinput = layui.tagsinput,
        dragsort = layui.dragsort,
        colorpicker = layui.colorpicker,
        tableSelect = layui.tableSelect;

    // 文件上传集合
    var webuploader = [];
    // 当前上传对象
    var curr_uploader = {};

    //裁剪图片
    $(document).on('click', '.cropper', function() {
        var inputId = $(this).attr("data-input-id");
        var image = $(this).closest(".thumbnail").children('img').data('original');
        //console.log(inputId);
        var dataId = $(this).data("id");
        var index = layer.open({
            type: 2,
            shadeClose: true,
            shade: false,
            area: ['880px', '620px'],
            title: '图片裁剪',
            content: GV.jcrop_upload_url + '?url=' + image,
            success: function(layero, index) {
                $(layero).data("arr", [inputId, dataId]);
            }
        });

    });

    //颜色
    $('.layui-color-box').each(function() {
        colorpicker.render({
            elem: $(this),
            color: $('.test-form-input').val(),
            done: function(color) {
                $('.test-form-input').val(color);
            }
        });
    });

    //tags标签
    $('.form-tags').each(function() {
        $(this).tagsInput({
            width: 'auto',
            defaultText: $(this).data('remark'),
            height: '26px',
        })
    })

    //图片选择
    if ($('.fachoose-image').length > 0) {
        $.each($('.fachoose-image'), function(i, v) {
            var input_id = $(this).data("input-id") ? $(this).data("input-id") : "",
                inputObj = $("#" + input_id),
                multiple = $(this).data("multiple") ? 'checkbox' : 'radio';
            var $file_list = $('#file_list_' + input_id);
            tableSelect.render({
                elem: "#fachoose-" + input_id,
                searchKey: 'keyword',
                searchPlaceholder: '请输入图片名称',
                table: {
                    url: GV.image_select_url,
                    cols: [
                        [
                            { type: multiple },
                            { field: 'id', title: 'ID' },
                            { field: 'url', minWidth: 120, search: false, title: '图片', imageHeight: 40, align: "center", templet: '<div><img src="{{d.path}}" height="100%"></div>' },
                            { field: 'name', width: 120, title: '名称' },
                            { field: 'mime', width: 120, title: 'Mime类型' },
                            { field: 'create_time', width: 180, title: '上传时间', align: "center", search: 'range' },
                        ]
                    ]
                },
                done: function(e, data) {
                    var selectedList = [];
                    $.each(data.data, function(index, val) {
                        selectedList[index] = {
                            file_id: val.id,
                            file_path: val.path
                        };
                    });
                    selectedList.forEach(function(item) {
                        var $li = '<div class="file-item thumbnail"><img data-image class="' + input_id + "-" + item.file_id + '" data-original="' + item.file_path + '" src="' + item.file_path + '"><div class="file-panel">';
                        if (multiple == 'checkbox') {
                            $li += '<i class="iconfont icon-yidong move-picture"></i> ';
                        }
                        $li += '<i class="iconfont icon-tailor cropper" data-input-id="' + item.file_id + '" data-id="' + input_id + '"></i> <i class="iconfont icon-trash remove-picture" data-id="' + item.file_id + '"></i></div></div>';
                        if (multiple == 'checkbox') {
                            if (inputObj.val()) {
                                inputObj.val(inputObj.val() + ',' + item.file_id);
                            } else {
                                inputObj.val(item.file_id);
                            }
                            $file_list.append($li);
                        } else {
                            inputObj.val(item.file_id);
                            $file_list.html($li);
                        }
                    });
                }
            })
        });
    }

    // 文件上传
    $('.js-upload-file,.js-upload-files').each(function() {
        var $input_file = $(this).find('input');
        var $input_file_name = $input_file.attr('id');
        // 是否多文件上传
        var $multiple = $input_file.data('multiple');
        // 允许上传的后缀
        var $ext = $input_file.data('ext');
        // 文件限制大小
        var $size = $input_file.data('size') * 1024;
        // 文件列表
        var $file_list = $('#file_list_' + $input_file_name);
        // 实例化上传
        var uploader = WebUploader.create({
            // 选完文件后，是否自动上传。
            auto: true,
            // 去重
            duplicate: true,
            // swf文件路径
            swf: GV.WebUploader_swf,
            // 文件接收服务端。
            server: GV.file_upload_url,
            // 选择文件的按钮。可选。
            // 内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: {
                id: '#picker_' + $input_file_name,
                multiple: $multiple
            },
            // 文件限制大小
            fileSingleSizeLimit: $size,
            // 只允许选择文件文件。
            accept: {
                title: 'Files',
                extensions: $ext
            }
        });

        // 当有文件添加进来的时候
        uploader.on('fileQueued', function(file) {
            var $li = '<tr id="' + file.id + '" class="file-item"><td>' + file.name + '</td>' +
                '<td class="file-state">正在读取文件信息...</td><td><div class="layui-progress"><div class="layui-progress-bar" lay-percent="0%"></div></div></td>' +
                '<td><a href="javascript:void(0);" class="layui-btn download-file layui-btn layui-btn-xs">下载</a> <a href="javascript:void(0);" class="layui-btn remove-file layui-btn layui-btn-xs layui-btn-danger">删除</a></td></tr>';

            if ($multiple) {
                $file_list.find('.file-box').append($li);
            } else {
                $file_list.find('.file-box').html($li);
                // 清空原来的数据
                $input_file.val('');
            }
            // 设置当前上传对象
            curr_uploader = uploader;
        });

        // 文件上传成功
        uploader.on('uploadSuccess', function(file, response) {
            var $li = $('#' + file.id);
            if (response.code == 0) {
                if ($multiple) {
                    if ($input_file.val()) {
                        $input_file.val($input_file.val() + ',' + response.id);
                    } else {
                        $input_file.val(response.id);
                    }
                    $li.find('.remove-file').attr('data-id', response.id);
                } else {
                    $input_file.val(response.id);
                }
            }
            // 加入提示信息
            $li.find('.file-state').html('<span class="text-' + response.class + '">' + response.info + '</span>');
            // 添加下载链接
            $li.find('.download-file').attr('href', response.path);
        });

        // 文件上传过程中创建进度条实时显示。
        uploader.on('uploadProgress', function(file, percentage) {
            var $percent = $('#' + file.id).find('.layui-progress-bar');
            $percent.css('width', percentage * 100 + '%');
        });

        // 文件上传失败，显示上传出错。
        uploader.on('uploadError', function(file) {
            var $li = $('#' + file.id);
            $li.find('.file-state').html('<span class="text-danger">服务器发生错误~</span>');
        });

        // 文件验证不通过
        uploader.on('error', function(type) {
            switch (type) {
                case 'Q_TYPE_DENIED':
                    layer.alert('图片类型不正确，只允许上传后缀名为：' + $ext + '，请重新上传！', { icon: 5 })
                    break;
                case 'F_EXCEED_SIZE':
                    layer.alert('图片不得超过' + ($size / 1024) + 'kb，请重新上传！', { icon: 5 })
                    break;
            }
        });
        // 删除文件
        $file_list.delegate('.remove-file', 'click', function() {
            if ($multiple) {
                var id = $(this).data('id'),
                    ids = $input_file.val().split(',');

                if (id) {
                    for (var i = 0; i < ids.length; i++) {
                        if (ids[i] == id) {
                            ids.splice(i, 1);
                            break;
                        }
                    }
                    $input_file.val(ids.join(','));
                }
            } else {
                $input_file.val('');
            }
            $(this).closest('.file-item').remove();
        });
        // 将上传实例存起来
        webuploader.push(uploader);
    });

    //图片上传
    $('.js-upload-image,.js-upload-images').each(function() {
        var $input_file = $(this).find('input');
        var $input_file_name = $input_file.attr('id');
        // 图片列表
        var $file_list = $('#file_list_' + $input_file_name);
        // 缩略图参数
        var $thumb = $input_file.data('thumb');
        // 水印参数
        var $watermark = $input_file.data('watermark');
        // 是否多图片上传
        var $multiple = $input_file.data('multiple');
        // 允许上传的后缀
        var $ext = $input_file.data('ext');
        // 图片限制大小
        var $size = $input_file.data('size') * 1024;
        // 优化retina, 在retina下这个值是2
        var ratio = window.devicePixelRatio || 1;
        // 缩略图大小
        var thumbnailWidth = 100 * ratio;
        var thumbnailHeight = 100 * ratio;

        var uploader = WebUploader.create({
            // 选完图片后，是否自动上传。
            auto: true,
            // 去重
            duplicate: true,
            // 不压缩图片
            resize: false,
            compress: false,
            // swf文件路径
            swf: GV.WebUploader_swf,
            pick: {
                id: '#picker_' + $input_file_name,
                multiple: $multiple
            },
            server: GV.image_upload_url,
            // 图片限制大小
            fileSingleSizeLimit: $size,
            // 只允许选择图片文件。
            accept: {
                title: 'Images',
                extensions: $ext,
                mimeTypes: 'image/jpg,image/jpeg,image/bmp,image/png,image/gif'
            },
            // 自定义参数
            formData: {
                thumb: $thumb,
                watermark: $watermark
            }

        })
        //console.log(uploader);
        // 当有文件添加进来的时候
        uploader.on('fileQueued', function(file) {
            var $li = $(
                    '<div id="' + file.id + '" class="file-item js-gallery thumbnail">' +
                    '<img data-image>' +
                    '<div class="info">' + file.name + '</div>' +
                    '<div class="file-panel">' +
                    ($multiple ? ' <i class="iconfont icon-yidong move-picture"></i> ' : '') +
                    '<i class="iconfont icon-tailor cropper"></i> <i class="iconfont icon-trash remove-picture"></i></div><div class="progress progress-mini remove-margin active">' +
                    '<div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>' +
                    '</div>' +
                    '<div class="file-state img-state"><div class="layui-bg-blue">正在读取...</div>' +
                    '</div>'
                ),
                $img = $li.find('img');

            if ($multiple) {
                $file_list.append($li);
            } else {
                $file_list.html($li);
                $input_file.val('');
            }
            // 创建缩略图
            // 如果为非图片文件，可以不用调用此方法。
            // thumbnailWidth x thumbnailHeight 为 100 x 100
            uploader.makeThumb(file, function(error, src) {
                if (error) {
                    $img.replaceWith('<span>不能预览</span>');
                    return;
                }
                $img.attr('src', src);
            }, thumbnailWidth, thumbnailHeight);
            // 设置当前上传对象
            curr_uploader = uploader;
        });

        // 文件上传过程中创建进度条实时显示。
        uploader.on('uploadProgress', function(file, percentage) {
            var $percent = $('#' + file.id).find('.progress-bar');
            //console.log($percent);
            $percent.css('width', percentage * 100 + '%');
        });

        // 文件上传成功
        uploader.on('uploadSuccess', function(file, response) {
            var $li = $('#' + file.id);
            if (response.code == 0) {
                if ($multiple) {
                    if ($input_file.val()) {
                        $input_file.val($input_file.val() + ',' + response.id);
                    } else {
                        $input_file.val(response.id);
                    }
                    $li.find('.remove-picture').attr('data-id', response.id);
                } else {
                    $input_file.val(response.id);
                }
            }
            $li.find('.file-state').html('<div class="layui-bg-green">' + response.info + '</div>');
            $li.find('img').attr('data-original', response.path).addClass($input_file_name + '-' + response.id);
            $li.find('.file-panel .cropper').attr('data-input-id', response.id).attr('data-id', $input_file_name);
            $li.find('.file-panel .remove-picture').attr('data-id', response.id);
        });

        // 文件上传失败，显示上传出错。
        uploader.on('uploadError', function(file) {
            var $li = $('#' + file.id);
            $li.find('.file-state').html('<div class="layui-bg-red">服务器错误</div>');
        });

        // 文件验证不通过
        uploader.on('error', function(type) {
            switch (type) {
                case 'Q_TYPE_DENIED':
                    layer.alert('图片类型不正确，只允许上传后缀名为：' + $ext + '，请重新上传！', { icon: 5 })
                    break;
                case 'F_EXCEED_SIZE':
                    layer.alert('图片不得超过' + ($size / 1024) + 'kb，请重新上传！', { icon: 5 })
                    break;
            }
        });

        // 完成上传完了，成功或者失败，先删除进度条。
        uploader.on('uploadComplete', function(file) {
            setTimeout(function() {
                $('#' + file.id).find('.progress').remove();
            }, 500);

        });

        // 删除图片
        $file_list.delegate('.remove-picture', 'click', function() {
            $(this).closest('.thumbnail').remove();
            if ($multiple) {
                var ids = [];
                $file_list.find('.remove-picture').each(function() {
                    ids.push($(this).data('id'));
                });
                $input_file.val(ids.join(','));
            } else {
                $input_file.val('');
            }
        });

        // 将上传实例存起来
        webuploader.push(uploader);
        // 如果是多图上传，则实例化拖拽
        if ($multiple) {
            $file_list.dragsort({
                dragSelector: ".move-picture",
                dragEnd: function() {
                    var ids = [];
                    $file_list.find('.remove-picture').each(function() {
                        ids.push($(this).data('id'));
                    });
                    $input_file.val(ids.join(','));
                },
                placeHolderTemplate: '<div class="file-item thumbnail" style="border:1px #009688 dashed;"></div>'
            })
        }
    });
});