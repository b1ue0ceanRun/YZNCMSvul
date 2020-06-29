layui.define(['form', 'yzn', 'table', 'notice'], function(exports) {
    var MOD_NAME = 'yznForm',
        $ = layui.$,
        yzn = layui.yzn,
        table = layui.table,
        form = layui.form,
        notice = layui.notice;

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTable',
    };

    var yznForm = {
        listen: function(preposeCallback, ok, no, ex) {
            // 监听表单提交事件
            yznForm.api.formSubmit(preposeCallback, ok, no, ex);
        },
        api: {
            form: function(url, data, ok, no, ex, refreshTable) {
                if (refreshTable === undefined) {
                    refreshTable = true;
                }
                ok = ok || function(res) {
                    res.msg = res.msg || '';
                    yzn.msg.success(res.msg, function() {
                        yznForm.api.closeCurrentOpen({
                            refreshTable: refreshTable
                        });
                    });
                    return false;
                };
                yzn.request.post({
                    url: url,
                    data: data,
                }, ok, no, ex);
                return false;
            },
            closeCurrentOpen: function(option) {
                option = option || {};
                option.refreshTable = option.refreshTable || false;
                option.refreshFrame = option.refreshFrame || false;
                if (option.refreshTable === true) {
                    option.refreshTable = init.table_render_id;
                }
                var index = parent.layer.getFrameIndex(window.name);
                parent.layer.close(index);
                if (option.refreshTable !== false) {
                    parent.layui.table.reload(option.refreshTable);
                }
                if (option.refreshFrame) {
                    parent.location.reload();
                }
                return false;
            },
            refreshFrame: function() {
                parent.location.reload();
                return false;
            },
            refreshTable: function(tableName) {
                tableName = tableName | 'currentTable';
                table.reload(tableName);
            },
            formSubmit: function(preposeCallback, ok, no, ex) {
                var formList = document.querySelectorAll("[lay-submit]");

                // 表单提交自动处理
                if (formList.length > 0) {
                    $.each(formList, function(i, v) {
                        var filter = $(this).attr('lay-filter'),
                            type = $(this).attr('data-type'),
                            refresh = $(this).attr('data-refresh'),
                            url = $(this).attr('lay-submit');
                        // 表格搜索不做自动提交
                        if (type === 'tableSearch') {
                            return false;
                        }
                        // 判断是否需要刷新表格
                        if (refresh === 'false') {
                            refresh = false;
                        } else {
                            refresh = true;
                        }
                        // 自动添加layui事件过滤器
                        if (filter === undefined || filter === '') {
                            filter = 'save_form_' + (i + 1);
                            $(this).attr('lay-filter', filter)
                        }
                        if (url === undefined || url === '' || url === null) {
                            url = window.location.href;
                        }
                        form.on('submit(' + filter + ')', function(data) {
                            var dataField = data.field;

                            // 富文本数据处理
                            /*var editorList = document.querySelectorAll(".editor");
                            if (editorList.length > 0) {
                                $.each(editorList, function(i, v) {
                                    var name = $(this).attr("name");
                                    dataField[name] = CKEDITOR.instances[name].getData();
                                });
                            }*/

                            if (typeof preposeCallback === 'function') {
                                dataField = preposeCallback(dataField);
                            }
                            yznForm.api.form(url, dataField, ok, no, ex, refresh);
                            return false;
                        });
                    });
                }

            },
        }
    }

    yznForm.listen();

    // 监听动态表格刷新
    $('body').on('click', '[data-table-refresh]', function() {
        var tableId = $(this).attr('data-table-refresh');
        if (tableId === undefined || tableId === '' || tableId == null) {
            tableId = init.table_render_id;
        }
        table.reload(tableId);
    });

    // 监听请求
    $('body').on('click', '[data-request]', function() {
        var title = $(this).attr('data-title'),
            url = $(this).attr('data-request'),
            tableId = $(this).attr('data-table'),
            addons = $(this).attr('data-addons'),
            checkbox = $(this).attr('data-checkbox');

        var postData = {};
        if (checkbox === 'true') {
            tableId = tableId || init.table_render_id;
            var checkStatus = table.checkStatus(tableId),
                data = checkStatus.data;
            if (data.length <= 0) {
                yzn.msg.error('请勾选需要操作的数据');
                return false;
            }
            var ids = [];
            $.each(data, function(i, v) {
                ids.push(v.id);
            });
            postData.id = ids;
        }

        if (addons !== true && addons !== 'true') {
            //url = admin.url(url);
        }
        title = title || '确定进行该操作？';
        tableId = tableId || init.table_render_id;
        yzn.msg.confirm(title, function() {
            yzn.request.post({
                url: url,
                data: postData,
            }, function(res) {
                yzn.msg.success(res.msg, function() {
                    table.reload(tableId);
                });
            })
        });
        return false;
    });

    // 数据表格多删除
    $('body').on('click', '[data-table-delete]', function() {
        var tableId = $(this).attr('data-table-delete'),
            url = $(this).attr('data-url');
        tableId = tableId || init.table_render_id;
        url = url !== undefined ? url : window.location.href;
        var checkStatus = table.checkStatus(tableId),
            data = checkStatus.data;
        if (data.length <= 0) {
            yzn.msg.error('请勾选需要删除的数据');
            return false;
        }
        var ids = [];
        $.each(data, function(i, v) {
            ids.push(v.id);
        });
        yzn.msg.confirm('确定删除？', function() {
            yzn.request.post({
                url: url,
                data: {
                    ids: ids
                },
            }, function(res) {
                yzn.msg.success(res.msg, function() {
                    table.reload(tableId);
                });
            });
        });
        return false;
    });

    // 监听弹出层的打开
    $('body').on('click', '[data-open]', function() {

        var clienWidth = $(this).attr('data-width'),
            clientHeight = $(this).attr('data-height'),
            dataFull = $(this).attr('data-full'),
            checkbox = $(this).attr('data-checkbox'),
            url = $(this).attr('data-open'),
            tableId = $(this).attr('data-table');

        if (checkbox === 'true') {
            tableId = tableId || init.table_render_id;
            var checkStatus = table.checkStatus(tableId),
                data = checkStatus.data;
            if (data.length <= 0) {
                yzn.msg.error('请勾选需要操作的数据');
                return false;
            }
            var ids = [];
            $.each(data, function(i, v) {
                ids.push(v.id);
            });
            if (url.indexOf("?") === -1) {
                url += '?id=' + ids.join(',');
            } else {
                url += '&id=' + ids.join(',');
            }
        }

        if (clienWidth === undefined || clientHeight === undefined) {
            var width = document.body.clientWidth,
                height = document.body.clientHeight;
            if (width >= 800 && height >= 600) {
                clienWidth = '800px';
                clientHeight = '600px';
            } else {
                clienWidth = '100%';
                clientHeight = '100%';
            }
        }
        if (dataFull === 'true') {
            clienWidth = '100%';
            clientHeight = '100%';
        }

        yzn.open(
            $(this).attr('data-title'), url, clienWidth, clientHeight,
        );
    });

    /**
     * iframe弹窗
     * @href 弹窗地址
     * @title 弹窗标题
     * @lay-data {width: '弹窗宽度', height: '弹窗高度', idSync: '是否同步ID', table: '数据表ID(同步ID时必须)', type: '弹窗类型'}
     */
    $(document).on('click', '.layui-iframe', function() {
        var that = $(this),
            query = '';
        var def = { width: '750px', height: '500px', idSync: false, table: 'dataTable', type: 2, url: !that.attr('data-href') ? that.attr('href') : that.attr('data-href'), title: that.attr('title') };
        var opt = new Function('return ' + that.attr('lay-data'))() || {};

        opt.url = opt.url || def.url;
        opt.title = opt.title || def.title;
        opt.width = opt.width || def.width;
        opt.height = opt.height || def.height;
        opt.type = opt.type || def.type;
        opt.table = opt.table || def.table;
        opt.idSync = opt.idSync || def.idSync;

        if (!opt.url) {
            notice.info('请设置data-href参数');
            return false;
        }

        if (opt.idSync) { // ID 同步
            if ($('.checkbox-ids:checked').length <= 0) {
                var checkStatus = table.checkStatus(opt.table);
                if (checkStatus.data.length <= 0) {
                    notice.info('请选择要操作的数据');
                    return false;
                }

                for (var i in checkStatus.data) {
                    query += '&id[]=' + checkStatus.data[i].id;
                }
            } else {
                $('.checkbox-ids:checked').each(function() {
                    query += '&id[]=' + $(this).val();
                })
            }
        }

        if (opt.url.indexOf('?') >= 0) {
            opt.url += '&iframe=yes' + query;
        } else {
            opt.url += '?iframe=yes' + query;
        }

        layer.open({ type: opt.type, title: opt.title, content: opt.url, area: [opt.width, opt.height] });
        return false;
    });

    $(document).on('click', '.layui-tr-del', function() {
        var that = $(this),
            href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href');
        layer.confirm('删除之后无法恢复，您确定要删除吗？', { icon: 3, title: '提示信息' }, function(index) {
            if (!href) {
                notice.info('请设置data-href参数');
                return false;
            }
            $.get(href, function(res) {
                if (res.code == 1) {
                    notice.success(res.msg);
                    that.parents('tr').remove();
                } else {
                    notice.error(res.msg);
                }
            });
            layer.close(index);
        });
        return false;
    });

    /**
     * 列表页批量操作按钮组
     * @attr href 操作地址
     * @attr data-table table容器ID
     * @class confirm 类似系统confirm
     * @attr tips confirm提示内容
     */
    $(document).on('click', '.layui-batch-all', function() {
        var that = $(this),
            query = '',
            code = function(that) {
                var href = that.attr('href') ? that.attr('href') : that.attr('data-href');
                var tableObj = that.attr('data-table') ? that.attr('data-table') : 'dataTable';
                if (!href) {
                    notice.info('请设置data-href参数');
                    return false;
                }

                if ($('.checkbox-ids:checked').length <= 0) {
                    var checkStatus = table.checkStatus(tableObj);
                    if (checkStatus.data.length <= 0) {
                        notice.info('请选择要操作的数据');
                        return false;
                    }
                    for (var i in checkStatus.data) {
                        if (i > 0) {
                            query += '&';
                        }
                        query += 'ids[]=' + checkStatus.data[i].id;
                    }
                } else {
                    if (that.parents('form')[0]) {
                        query = that.parents('form').serialize();
                    } else {
                        query = $('#pageListForm').serialize();
                    }
                }

                yzn.request.post(href, query, function(res) {
                    if (res.code === 1) {
                        table.reload('dataTable');
                    }
                });
            };
        if (that.hasClass('confirm')) {
            var tips = that.attr('tips') ? that.attr('tips') : '您确定要执行此操作吗？';
            layer.confirm(tips, { icon: 3, title: '提示信息' }, function(index) {
                code(that);
                layer.close(index);
            });
        } else {
            code(that);
        }
        return false;
    });

    /**
     * 通用状态设置开关
     * @attr data-href 请求地址
     */
    form.on('switch(switchStatus)', function(data) {
        var that = $(this),
            status = 0;
        if (!that.attr('data-href')) {
            notice.info('请设置data-href参数');
            return false;
        }
        if (this.checked) {
            status = 1;
        }
        $.get(that.attr('data-href'), { value: status }, function(res) {
            if (res.code === 1) {
                notice.success(res.msg);
            } else {
                notice.error(res.msg);
                that.trigger('click');
                form.render('checkbox');
            }
        });
    });

    /**
     * 监听表单提交
     * @attr action 请求地址
     * @attr data-form 表单DOM
     */
    /*form.on('submit(formSubmit)', function(data) {
        var _form = '',
            that = $(this),
            text = that.text(),
            options = { pop: false, refresh: true, jump: false, callback: null };
        if ($(this).attr('data-form')) {
            _form = $(that.attr('data-form'));
        } else {
            _form = that.parents('form');
        }
        if (that.attr('lay-data')) {
            options = new Function('return ' + that.attr('lay-data'))();
        }
        that.prop('disabled', true);
        yzn.request.post(_form.attr('action'), _form.serialize(), function(res) {
            if (res.code == 0) {
                that.prop('disabled', true);
                setTimeout(function() {
                    that.prop('disabled', false);
                }, 3000);
            } else {
                setTimeout(function() {
                    that.prop('disabled', false);
                    if (options.callback) {
                        options.callback(that, res);
                    }
                    if (options.pop == true) {
                        if (options.refresh == true) {
                            parent.location.reload();
                        } else if (options.jump == true && res.url != '') {
                            parent.location.href = res.url;
                        }
                        parent.layui.layer.closeAll();
                    } else if (options.refresh == true) {
                        if (res.url != '') {
                            location.href = res.url;
                        } else {
                            location.reload();
                        }
                    }
                }, 3000);
            }
        }, {
            error: function() {
                that.prop('disabled', true);
                setTimeout(function() {
                    that.prop('disabled', false);
                }, 3000);
            }

        });
        return false;
    });*/

    //ajax get请求
    $(document).on('click', '.ajax-get', function() {
        var that = $(this),
            href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href'),
            refresh = !that.attr('refresh') ? 'true' : that.attr('refresh');
        if (!href) {
            layer.msg('请设置data-href参数');
            return false;
        }
        if ($(this).hasClass('confirm')) {
            layer.confirm('确认要执行该操作吗?', { icon: 3, title: '提示' }, function(index) {
                $.get(href, {}, function(res) {
                    layer.msg(res.msg, {}, function() {
                        if (refresh == 'true' || refresh == 'yes') {
                            if (typeof(res.url) != 'undefined' && res.url != null && res.url != '') {
                                location.href = res.url;
                            } else {
                                location.reload();
                            }
                        }
                    });
                });
            });
        } else {
            $.get(href, {}, function(res) {
                layer.msg(res.msg, {}, function() {
                    if (refresh == 'true') {
                        if (typeof(res.url) != 'undefined' && res.url != null && res.url != '') {
                            location.href = res.url;
                        } else {
                            location.reload();
                        }
                    }
                });
            });
        };
        return false;
    });

    //通用表单post提交
    $('.ajax-post').on('click', function(e) {
        var target, query, _form,
            target_form = $(this).attr('target-form'),
            that = this,
            nead_confirm = false;

        _form = $('.' + target_form);
        if ($(this).attr('hide-data') === 'true') {
            _form = $('.hide-data');
            query = _form.serialize();
        } else if (_form.get(0) == undefined) {
            return false;
        } else if (_form.get(0).nodeName == 'FORM') {
            if ($(this).hasClass('confirm')) {
                if (!confirm('确认要执行该操作吗?')) {
                    return false;
                }
            }
            if ($(this).attr('url') !== undefined) {
                target = $(this).attr('url');
            } else {
                target = _form.attr("action");
            }
            query = _form.serialize();
        } else if (_form.get(0).nodeName == 'INPUT' || _form.get(0).nodeName == 'SELECT' || _form.get(0).nodeName == 'TEXTAREA') {
            _form.each(function(k, v) {
                if (v.type == 'checkbox' && v.checked == true) {
                    nead_confirm = true;
                }
            })
            if (nead_confirm && $(this).hasClass('confirm')) {
                if (!confirm('确认要执行该操作吗?')) {
                    return false;
                }
            }
            query = _form.serialize();
        } else {
            if ($(this).hasClass('confirm')) {
                if (!confirm('确认要执行该操作吗?')) {
                    return false;
                }
            }
            query = _form.find('input,select,textarea').serialize();
        }

        $.post(target, query).success(function(data) {
            if (data.code == 1) {
                parent.layui.layer.closeAll();
                if (data.url) {
                    layer.msg(data.msg + ' 页面即将自动跳转~');
                } else {
                    layer.msg(data.msg);
                }
                setTimeout(function() {
                    if (data.url) {
                        location.href = data.url;
                    } else {
                        location.reload();
                    }
                }, 1500);
            } else {
                layer.msg(data.msg);
                setTimeout(function() {
                    if (data.url) {
                        location.href = data.url;
                    }
                }, 1500);
            }
        });
        return false;
    });

    // 放大图片
    $('body').on('click', '[data-image]', function() {
        var title = $(this).attr('data-image'),
            src = $(this).attr('src'),
            alt = $(this).attr('alt');
        var photos = {
            "title": title,
            "id": Math.random(),
            "data": [{
                "alt": alt,
                "pid": Math.random(),
                "src": src,
                "thumb": src
            }]
        };
        layer.photos({
            photos: photos,
            anim: 5
        });
        return false;
    });

    exports(MOD_NAME, {});
});