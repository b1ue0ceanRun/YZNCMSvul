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
// | 栏目模型
// +----------------------------------------------------------------------
namespace app\cms\model;

use think\facade\Cache;
use \think\Db;
use \think\Model;

/**
 * 模型
 */
class Category extends Model
{
    //新增栏目
    public function addCategory($data, $fields)
    {
        if (empty($data)) {
            $this->error = '添加栏目数据不能为空！';
            return false;
        }
        //序列化setting数据
        $data['setting'] = serialize($data['setting']);
        //栏目拼音
        /*$catname = iconv('utf-8', 'gbk', $data['catname']);
        $letters = gbk_to_pinyin($catname);
        $data['letter'] = strtolower(implode('', $letters));*/
        $catid = self::create($data, $fields);
        if ($catid) {
            cache('Category', null);
            return $catid;
        } else {
            $this->error = '栏目添加失败！';
            return false;

        }
    }

    //编辑栏目
    public function editCategory($data, $fields)
    {
        if (empty($data)) {
            $this->error = '编辑栏目数据不能为空！';
            return false;
        }
        $catid = $data['id'];
        //栏目类型
        $data['type'] = (int) $data['type'];

        //查询该栏目是否存在
        $info = self::where(array('id' => $catid))->find();
        if (empty($info)) {
            $this->error = '该栏目不存在！';
            return false;
        }

        //栏目拼音
        /*$catname = iconv('utf-8', 'gbk', $data['catname']);
        $letters = gbk_to_pinyin($catname);
        $data['letter'] = strtolower(implode('', $letters));*/

        //序列化setting数据
        $data['setting'] = serialize($data['setting']);

        //更新数据
        if (self::update($data, [], $fields) !== false) {
            //更新栏目缓存
            cache('Category', null);
            getCategory($catid, '', true);
            return true;
        } else {
            $this->error = '栏目编辑失败！';
            return false;
        }
    }

    /**
     * 删除栏目
     */
    public function deleteCatid($catid)
    {
        if (!$catid) {
            return false;
        }
        $where = array();
        $catInfo = self::get($catid);
        //是否存在子栏目
        if ($catInfo['child'] && $catInfo['type'] == 2) {
            $arrchildid = explode(",", $catInfo['arrchildid']);
            unset($arrchildid[0]);
            $catid = array_merge($arrchildid, array($catid));
        }
        $where = ['id' => $catid];
        //检查是否存在数据，存在数据不执行删除
        if (is_array($catid)) {
            $modeid = array();
            foreach ($catid as $cid) {
                $catinfo = getCategory($cid);
                if ($catinfo['modelid'] && $catinfo['type'] == 2) {
                    $modeid[$catinfo['modelid']] = $catinfo['modelid'];
                }
                foreach ($modeid as $mid) {
                    $tbname = ucwords(getModel($mid, 'tablename'));
                    if ($tbname && Db::name($tbname)->where(['id' => $catid])->find()) {
                        return false;
                    }
                }
            }
        } else {
            $catinfo = getCategory($catid);
            $tbname = ucwords(getModel($catInfo['modelid'], 'tablename'));
            //含资料无法删除
            if ($tbname && $catinfo['type'] == 2 && Db::name($tbname)->where(["id" => $catid])->find()) {
                return false;
            }
        }
        $status = self::where($where)->delete();
        //更新缓存
        cache('Category', null);
        if (false !== $status) {
            //TD
            return true;
        } else {
            return false;
        }
    }

    //刷新栏目索引缓存
    public function category_cache()
    {
        $data = self::order("listorder ASC")->select();
        $CategoryIds = array();
        foreach ($data as $r) {
            $CategoryIds[$r['id']] = array(
                'id' => $r['id'],
                'parentid' => $r['parentid'],
            );
        }
        cache("Category", $CategoryIds);
        return $CategoryIds;
    }

}
