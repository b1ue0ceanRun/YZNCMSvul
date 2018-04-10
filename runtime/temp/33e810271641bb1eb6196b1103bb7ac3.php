<?php /*a:1:{s:49:"E:\yzncms\application\admin\view\login\index.html";i:1523359317;}*/ ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>登录</title>
    <link rel="stylesheet" type="text/css" href="/static/layui/css/layui.css" />
    <link rel="stylesheet" type="text/css" href="/static/admin/css/style.css" />
</head>

<body>
    <div class="login-main">
        <header class="layui-elip">YZNCMS管理系统&nbsp;<span class="version">v1.0.0</span></header>
        <form class="layui-form" action="">
            <div class="layui-form-item">
                <div class="layui-input-inline">
                    <input type="text" name="title" lay-verify="title" autocomplete="off" placeholder="账号" class="layui-input">
                </div>
                <div class="layui-input-inline">
                    <input type="text" name="title" lay-verify="title" autocomplete="off" placeholder="密码" class="layui-input">
                </div>
                <div class="layui-input-inline verify-box">
                    <input type="text" name="verify" required="" lay-verify="required|verify" placeholder="验证码" autocomplete="off" class="layui-input layui-form-danger">
                    <img id="verify" src="http://web.vip-admin.com/captcha.html" alt="验证码" onclick="this.src = this.src+'?'" class="captcha">
                </div>
                <div class="layui-input-inline login-btn">
                    <button type="button" class="layui-btn" lay-submit="" lay-filter="sub">登录</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>