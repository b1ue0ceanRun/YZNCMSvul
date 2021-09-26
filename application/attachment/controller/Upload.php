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
// | 附件上传处理类
// +----------------------------------------------------------------------
namespace app\attachment\controller;

use app\attachment\model\Attachment as Attachment_Model;
use app\common\controller\Base;
use app\common\exception\UploadException;
use app\common\library\Upload as UploadLib;

class Upload extends Base
{
    //编辑器初始配置
    private $editorConfig = array(
        /* 上传图片配置项 */
        "imageActionName"         => "uploadimage", /* 执行上传图片的action名称 */
        "imageFieldName"       => "upfile", /* 提交的图片表单名称 */
        "imageMaxSize"     => 2048000, /* 上传大小限制，单位B */
        "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 上传图片格式显示 */
        "imageCompressEnable" => true, /* 是否压缩图片,默认是true */
        "imageCompressBorder" => 1600, /* 图片压缩最长边限制 */
        "imageInsertAlign" => "none", /* 插入的图片浮动方式 */
        "imageUrlPrefix" => "", /* 图片访问路径前缀 */
        'imagePathFormat' => '',
        /* 涂鸦图片上传配置项 */
        "scrawlActionName"        => "uploadscrawl", /* 执行上传涂鸦的action名称 */
        "scrawlFieldName"      => "upfile", /* 提交的图片表单名称 */
        'scrawlPathFormat' => '',
        "scrawlMaxSize"           => 2048000, /* 上传大小限制，单位B */
        'scrawlUrlPrefix'      => '',
        'scrawlInsertAlign'       => 'none',
        /* 截图工具上传 */
        "snapscreenActionName"    => "uploadimage", /* 执行上传截图的action名称 */
        'snapscreenPathFormat' => '',
        'snapscreenUrlPrefix'     => '',
        'snapscreenInsertAlign'   => 'none',
        /* 抓取远程图片配置 */
        'catcherLocalDomain'      => array('127.0.0.1', 'localhost', 'img.baidu.com'),
        "catcherActionName"       => "catchimage", /* 执行抓取远程图片的action名称 */
        'catcherFieldName'     => 'source',
        'catcherPathFormat'       => '',
        'catcherUrlPrefix'        => '',
        'catcherMaxSize'          => 0,
        'catcherAllowFiles'       => array('.png', '.jpg', '.jpeg', '.gif', '.bmp'),
        /* 上传视频配置 */
        "videoActionName"         => "uploadvideo", /* 执行上传视频的action名称 */
        "videoFieldName"       => "upfile", /* 提交的视频表单名称 */
        'videoPathFormat'  => '',
        'videoUrlPrefix'          => '',
        'videoMaxSize'            => 0,
        'videoAllowFiles'         => array(".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"),
        /* 上传文件配置 */
        "fileActionName"          => "uploadfile", /* controller里,执行上传视频的action名称 */
        'fileFieldName'        => 'upfile',
        'filePathFormat'          => '',
        'fileUrlPrefix'           => '',
        'fileMaxSize'             => 0,
        'fileAllowFiles'          => array(".flv", ".swf"),
        /* 列出指定目录下的图片 */
        "imageManagerActionName"  => "listimage", /* 执行图片管理的action名称 */
        'imageManagerListPath' => '',
        'imageManagerListSize'    => 20,
        'imageManagerUrlPrefix'   => '',
        'imageManagerInsertAlign' => 'none',
        'imageManagerAllowFiles'  => array('.png', '.jpg', '.jpeg', '.gif', '.bmp'),
        /* 列出指定目录下的文件 */
        "fileManagerActionName"   => "listfile", /* 执行文件管理的action名称 */
        'fileManagerListPath'  => '',
        'fileManagerUrlPrefix'    => '',
        'fileManagerListSize'     => '',
        'fileManagerAllowFiles'   => array(".flv", ".swf"),
    );

    protected function initialize()
    {
        parent::initialize();
        //图片上传大小和类型
        $this->editorConfig['imageMaxSize'] = $this->editorConfig['catcherMaxSize'] = 0 == config('upload_image_size') ? 1024 * 1024 * 1024 : config('upload_image_size') * 1024;
        if (!empty(config('upload_image_ext'))) {
            $imageallowext = parse_attr(config('upload_image_ext'));
            foreach ($imageallowext as $k => $rs) {
                $imageallowext[$k] = ".{$rs}";
            }
            $this->editorConfig['imageAllowFiles'] = $imageallowext;
        }

        //附件上传大小和类型
        $this->editorConfig['fileMaxSize'] = $this->editorConfig['videoMaxSize'] = 0 == config('upload_file_size') ? 1024 * 1024 * 1024 : config('upload_file_size') * 1024;
        if (!empty(config('upload_file_ext'))) {
            $fileallowext = parse_attr(config('upload_file_ext'));
            foreach ($fileallowext as $k => $rs) {
                $fileallowext[$k] = ".{$rs}";
            }
            $this->editorConfig['fileAllowFiles'] = $fileallowext;
        }
    }

    public function upload($dir = '', $from = '', $module = '')
    {
        if (!function_exists("finfo_open")) {
            return json([
                'code'    => 0,
                'info'    => '检测到环境未开启php_fileinfo拓展',
                'state'   => '检测到环境未开启php_fileinfo拓展', //兼容百度
                'message' => '检测到环境未开启php_fileinfo拓展', //兼容editormd
            ]);
        }
        if ($dir == '') {
            return json([
                'code'    => 0,
                'info'    => '没有指定上传目录',
                'state'   => '没有指定上传目录', //兼容百度
                'message' => '没有指定上传目录', //兼容editormd
            ]);
        }
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!config('chunking')) {
                return json([
                    'code' => 0,
                    'info' => '未开启分片上传功能',
                ]);
            }
            $id      = $this->request->post("id/s", '', 'strtolower');
            $chunkid = $chunkid . $id;
            //分片
            $action     = $this->request->post("action");
            $chunkindex = $this->request->post("chunk/d", 0);
            $chunkcount = $this->request->post("chunks/d", 1);
            $filename   = $this->request->post("filename");
            $method     = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload     = new UploadLib();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename, $dir, $from, $module);
                } catch (UploadException $e) {
                    return json([
                        'code'    => 0,
                        'info'    => $e->getMessage(),
                        'state'   => $e->getMessage(), //兼容百度
                        'message' => $e->getMessage(), //兼容editormd
                    ]);
                }
                return $attachment;
            } else {
                //上传分片文件
                $file = $this->request->file('file');
                try {
                    $upload = new UploadLib($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    return json([
                        'code'    => 0,
                        'info'    => $e->getMessage(),
                        'state'   => $e->getMessage(), //兼容百度
                        'message' => $e->getMessage(), //兼容editormd
                    ]);
                }
                return json(['code' => 1]);
            }
        }
        // 获取附件数据
        switch ($from) {
            case 'editormd':
                $file_input_name = 'editormd-image-file';
                break;
            case 'ueditor':
                $file_input_name = 'upfile';
                break;
            default:
                $file_input_name = 'file';
        }
        $attachment = null;
        //默认普通上传文件
        $file = $this->request->file($file_input_name);
        if ($from == 'ueditor') {
            return $this->ueditor($file);
        }
        try {
            $upload     = new UploadLib($file);
            $attachment = $upload->upload($dir);
        } catch (UploadException $e) {
            return json([
                'code'    => 0,
                'info'    => $e->getMessage(),
                'state'   => $e->getMessage(), //兼容百度
                'message' => $e->getMessage(), //兼容editormd
            ]);
        }
        return $attachment;
    }

    private function ueditor($file)
    {
        $action = $this->request->get('action');
        switch ($action) {
            /* 获取配置信息 */
            case 'config':
                $result = $this->editorConfig;
                break;
            /* 上传图片 */
            case 'uploadimage':
                $upload = new UploadLib($file);
                return $upload->upload('images', 'ueditor');
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $upload = new UploadLib($file);
                return $upload->upload('images', 'ueditor_scrawl');
                break;
            /* 上传视频 */
            case 'uploadvideo':
                $upload = new UploadLib($file);
                return $upload->upload('videos', 'ueditor');
                break;
            /* 上传附件 */
            case 'uploadfile':
                $upload = new UploadLib($file);
                return $upload->upload('files', 'ueditor');
                break;
            /* 列出图片 */
            case 'listimage':
                return $this->showFileList('listimage');
                break;

            /* 列出附件 */
            case 'listfile':
                return $this->showFileList('listfile');
                break;
            default:
                $result = array(
                    'state' => '请求地址出错',
                );
                break;
        }
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                return htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                return json(['state' => 'callback参数不合法']);
            }
        } else {
            return json($result);
        }
    }

    /**
     * @param string $type 类型
     * @param $config
     * @return \think\response\Json
     */
    protected function showFileList($type = '')
    {
        /* 获取参数 */
        $size      = input('get.size/d', 0);
        $start     = input('get.start/d', 0);
        $allowExit = input('get.exit', '');
        if ($size == 0) {
            $size = 20;
        }
        /* 判断类型 */
        switch ($type) {
            /* 列出附件 */
            case 'listfile':
                $allowExit = '' == $allowExit ? config('upload_file_ext') : $allowExit;
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowExit = '' == $allowExit ? config('upload_image_ext') : $allowExit;
        }

        /* 获取附件列表 */
        $filelist = Attachment_Model::order('id desc')->where('ext', 'in', $allowExit)->where('status', 1)->limit($start, $size)->column('id,path,create_time,name,size');
        if (empty($filelist)) {
            return json(array(
                "state" => "没有找到附件",
                "list"  => [],
                "start" => $start,
                "total" => 0
            ));
        }
        $uploadUrl = config('public_url');
        $list      = [];
        $i         = 0;
        foreach ($filelist as $value) {
            $list[$i]['id']    = $value['id'];
            $list[$i]['url']   = $value['path'];
            $list[$i]['name']  = $value['name'];
            $list[$i]['size']  = format_bytes($value['size']);
            $list[$i]['mtime'] = $value['create_time'];
            $i++;
        }
        /* 返回数据 */
        $result = array(
            "state" => "SUCCESS",
            "list"  => $list,
            "start" => $start,
            "total" => Attachment_Model::where('ext', 'in', $allowExit)->count(),
        );
        return json($result);
    }
}
