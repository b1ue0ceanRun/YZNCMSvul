<?php
// +----------------------------------------------------------------------
// | Yzncms [ 御宅男工作室 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://yzncms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 御宅男 <530765310@qq.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | CMS路由
// +----------------------------------------------------------------------
Route::rule('cms/index', 'cms/index/index');
Route::rule('cms/lists/:catid', 'cms/index/lists')->pattern(['catid' => '\d+']);
Route::rule('cms/shows/:catid/:id', 'cms/index/shows')->pattern(['catid' => '\d+', 'id' => '\d+']);
Route::rule('cms/search', 'cms/index/search');
//如果想要直接绑定域名 不加cms后缀 直接注释上面代码 使用以下代码
//Route::rule('index', 'cms/index/index');
//Route::rule('lists/:catid', 'cms/index/lists');
//Route::rule('shows/:catid/:id', 'cms/index/shows');

//最后重要提示
//务必【更新栏目缓存】和【一键清理缓存】
