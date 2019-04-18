﻿layui.define(['layer', 'laytpl', 'form', 'element', 'upload', 'util'], function(exports) {
    var $ = layui.jquery,
        layer = layui.layer,
        laytpl = layui.laytpl,
        form = layui.form,
        element = layui.element,
        upload = layui.upload,
        util = layui.util,
        device = layui.device(),
        DISABLED = 'layui-btn-disabled';

    //阻止IE7以下访问
    if (device.ie && device.ie < 10) {
        layer.alert('如果您非得使用 IE 浏览器访问，那么请使用 IE10+');
    }


    layui.focusInsert = function(obj, str) {
        var result, val = obj.value;
        obj.focus();
        if (document.selection) { //ie
            result = document.selection.createRange();
            document.selection.empty();
            result.text = str;
        } else {
            result = [val.substring(0, obj.selectionStart), str, val.substr(obj.selectionEnd)];
            obj.focus();
            obj.value = result.join('');
        }
    };



    var fly = {}

    //表单提交
    form.on('submit(*)', function(data) {
        var action = $(data.form).attr('action'),
            button = $(data.elem);
        fly.json(action, data.field, function(res) {
            var end = function() {
                if (res.action) {
                    location.href = res.action;
                } else {
                    fly.form[action || button.attr('key')](data.field, data.form);
                }
            };
            if (res.status == 0) {
                button.attr('alert') ? layer.alert(res.msg, {
                    icon: 1,
                    time: 10 * 1000,
                    end: end
                }) : end();
            };
        });
        return false;
    });

    //加载特定模块
    if (layui.cache.page && layui.cache.page !== 'index') {
        var extend = {};
        extend[layui.cache.page] = layui.cache.page;
        layui.extend(extend);
        layui.use(layui.cache.page);
    }

    //手机设备的简单适配
    var treeMobile = $('.site-tree-mobile'),
        shadeMobile = $('.site-mobile-shade')

    treeMobile.on('click', function() {
        $('body').addClass('site-mobile');
    });

    shadeMobile.on('click', function() {
        $('body').removeClass('site-mobile');
    });

    //固定Bar
    util.fixbar({
        bar1: '&#xe642;',
        bgcolor: '#009688',
        click: function(type) {
            if (type === 'bar1') {
                layer.msg('打开 index.js，开启发表新帖的路径');
                //location.href = 'jie/add.html';
            }
        }
    });

    exports('fly', fly);

});