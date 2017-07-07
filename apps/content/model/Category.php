<?php
// +----------------------------------------------------------------------
// | Yzncms [ 御宅男工作室 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://yzncms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 御宅男 <530765310@qq.com>
// +----------------------------------------------------------------------
namespace app\content\model;
use \think\Model;
use \think\Db;
use \think\Cache;
/**
 * 栏目模型
 */
class Category extends Model
{

  /**
   * 删除栏目
   */
  public function deleteCatid($catid) {
      if (!$catid) {
            return false;
      }
      $where = array();
      //$where['catid'] = 包含自身和下级分类
      if (is_array($catid)) {

      }else{
            $where['catid'] = $catid;
            $catInfo = Db::name('Category')->where($where)->find();
            //是否存在子栏目
            if ($catInfo['child'] && $catInfo['type'] == 0) {
                $arrchildid = explode(",", $catInfo['arrchildid']);
                unset($arrchildid[0]);
                $catid = array_merge($arrchildid, array($catid));
                $where['catid'] = array("IN", $catid);
            }

      }

      if (is_array($catid)) {
            $modeid = array();
            foreach ($catid as $cid) {
                //获取模型ID组
                $catinfo = getCategory($cid);
                if ($catinfo['modelid'] && $catinfo['type'] == 0) {
                    $modeid[$catinfo['modelid']] = $catinfo['modelid'];
                }
                foreach ($modeid as $mid) {
                    $tbname = ucwords(getModel($mid, 'tablename'));
                    if (!$tbname) {
                        return false;
                    }
                    if ($tbname && db($tbname)->where(array("catid" => array("IN", $catid)))->count()) {
                        return false;
                    }
                }
            }
            /*foreach ($modeid as $mid) {
                $tbname = ucwords(getModel($mid, 'tablename'));
                if (!$tbname) {
                    return false;
                }
                if ($tbname && M($tbname)->where(array("catid" => array("IN", $catid)))->count()) {
                    return false;
                }
            }*/
      }else{





      }












  }






    //刷新栏目索引缓存
    public function category_cache()
    {
        $data = Db::name('category')->order("listorder ASC")->select();
        $CategoryIds = array();
        foreach ($data as $r) {
            $CategoryIds[$r['catid']] = array(
                'catid' => $r['catid'],
                'parentid' => $r['parentid'],
            );
        }
        Cache::set("Category", $CategoryIds);
        return $CategoryIds;
    }


}