<?php
namespace app\home\controller;

class Index{
    public function index(){
        return 'Yzncms内容管理系统V1.0.0<br>
[项目介绍]<br>

Yzncms是完全开源的项目，基于ThinkPHP5.09最新版,框架易于功能扩展，代码维护，方便二次开发<br>
帮助开发者简单高效降低二次开发成本，满足专注业务深度开发的需求。<br><br>
[环境要求]<br>

ThinkPHP5.09的运行环境要求PHP5.4以上。（注意：PHP5.4dev版本和PHP6均不支持）<br><br>
[安装教程]<br>

第一步：修改数据库配置 apps/common/conf/database.php<br>
第二步：将根目录的yzncms.sql文件导入数据库即可<br>
第三步：后台入口 http://您的域名/admin 默认账号密码admin  123456<br><br>
[PS]<br>

本系统持续更新 由于时间关系 更新时间较长<br>
本系统从0开始发布  TP新手可以看看如何写一个cms<br>
任何问题都可以提交到码云的issues里<br>';
    }
}
