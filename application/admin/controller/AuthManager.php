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
namespace app\admin\Controller;

use app\admin\model\AuthGroup;
use app\admin\model\AuthRule;
use app\common\controller\Adminbase;
use think\Db;

/**
 * 权限管理控制器
 */
class AuthManager extends Adminbase
{
    /**
     * 权限管理首页
     */
    public function index()
    {
        $list = Db::name('AuthGroup')->where(['module' => 'admin'])->order(['id' => 'DESC'])->select();
        $list = int_to_string($list);
        $this->assign('_list', $list);
        return $this->fetch();
    }

    /**
     * 访问授权页面
     */
    public function access()
    {
        $this->updateRules(); //更新节点
        $group_id = $this->request->param('group_id/d');
        $node_list = model("admin/Menu")->returnNodes();
        $auth_group = Db::name('AuthGroup')
            ->where(array('status' => 1, 'type' => AuthGroup::TYPE_ADMIN))
            ->column('id,title,rules');

        $map = array('type' => AuthRule::RULE_MAIN, 'status' => 1);
        $main_rules = db('AuthRule')->where($map)->column('name,id');
        $map = array('type' => AuthRule::RULE_URL, 'status' => 1);
        $child_rules = db('AuthRule')->where($map)->column('name,id');

        $this->assign('node_list', $node_list);
        $this->assign('auth_group', $auth_group);
        $this->assign('main_rules', $main_rules);
        $this->assign('auth_rules', $child_rules);
        $this->assign('this_group', $auth_group[$group_id]);
        return $this->fetch('managergroup');
    }

    /**
     * 后台节点配置的url作为规则存入auth_rule
     * 执行新节点的插入,已有节点的更新,无效规则的删除三项任务
     */
    public function updateRules()
    {
        //需要新增的节点必然位于$nodes
        $nodes = model("admin/Menu")->returnNodes(false);
        $AuthRule = model('AuthRule');
        //需要更新和删除的节点必然位于$rules
        $rules = $AuthRule->where('type', 'in', '1,2')->order('name')->select();
        //构建insert数据
        $data = array(); //保存需要插入和更新的新节点
        foreach ($nodes as $value) {
            $temp['name'] = $value['url'];
            $temp['title'] = $value['title'];
            $temp['module'] = $value['app'];
            if ($value['parentid'] > 0) {
                $temp['type'] = AuthRule::RULE_URL;
            } else {
                $temp['type'] = AuthRule::RULE_MAIN;
            }
            $temp['status'] = 1;
            $data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp; //去除重复项
        }
        $update = array(); //保存需要更新的节点
        $ids = array(); //保存需要删除的节点的id
        foreach ($rules as $index => $rule) {
            $key = strtolower($rule['name'] . $rule['module'] . $rule['type']);
            if (isset($data[$key])) {
                //如果数据库中的规则与配置的节点匹配,说明是需要更新的节点
                $data[$key]['id'] = $rule['id']; //为需要更新的节点补充id值
                $update[] = $data[$key];
                unset($data[$key]); //排除已存在的
                unset($rules[$index]);
                unset($rule['condition']);
                $diff[$rule['id']] = $rule;
            } elseif ($rule['status'] == 1) {
                $ids[] = $rule['id'];
            }
        }
        if (count($update)) {
            foreach ($update as $k => $row) {
                if ($row != $diff[$row['id']]) {
                    $AuthRule->where(array('id' => $row['id']))->update($row);
                }
            }
        }
        if (count($ids)) {
            $AuthRule->where(array('id' => array('IN', implode(',', $ids))))->update(array('status' => -1));
            //删除规则是否需要从每个用户组的访问授权表中移除该规则?
        }
        if (count($data)) {
            $AuthRule->insertAll(array_values($data));
        }
        if ($AuthRule->getError()) {
            trace('[' . __METHOD__ . ']:' . $AuthRule->getError());
            return false;
        } else {
            return true;
        }

    }

}
