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
namespace app\admin\model;
use think\Model;
use think\Validate;

/**
 * 管理员模型
 */
class User extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN__';
    protected $pk = 'userid';

	/**
	 * 管理员登录检测
     * @param string $username 用户名
     * @param string $password 密码
	 */
	public function checkLogin($username = '', $password = '')
    {
        $condition['username'] = trim($username);
        $condition['password'] = trim($password);
        $admin_info = $this->where(['username'=>$condition['username']]) ->find();
        if($admin_info)$admin_info =$admin_info ->toArray();
        if(is_array($admin_info)){
            /* 验证用户密码 */
            if($admin_info['password']==sha1($condition['password'].$admin_info['encrypt'])){
                /* 密码正确 自动登录用户 */
                $this->autoLogin($admin_info);
                return true;
            }
        }
        $this->error ='账号密码不正确！';
        return false;
	}

    /**
     * 自动登录用户
     */
    private function autoLogin($user)
    {
         //记录行为
        action_log('user_login', 'member', $user['userid'], $user['userid']);

    	/* 更新登录信息 */
        $data = array(
            'last_login_time' => time(),
            'last_login_ip'   => get_client_ip(1),
        );
        $this->where(array('userid'=>$user['userid']))->update($data);
    	/* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['userid'],
            'username'        => $user['username'],
            'last_login_time' => $user['last_login_time'],
        );
        session('last_login_time',$user['last_login_time']);
        session('last_login_ip',$user['last_login_ip']);

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));//签名
    }

    /**
     * 注销登录状态
     */
    public function logout()
    {
        session('user_auth', null);
        session('user_auth_sign', null);
    }

    /**
     * 创建管理员
     * @param type $data
     * @return boolean
     */
    public function createManager($data)
    {
        if (empty($data)) {
            $this->error = '没有数据！';
            return false;
        }
        //验证器
        $rule = [
            'username'  => 'unique:admin|require|alphaDash|length:3,15',
            'password'  => 'require|length:6,20|confirm',
            'email'     => 'email',
            'roleid'     => 'require'
        ];
        $msg = [
            'username.unique' => '用户名已经存在！',
            'username.require' => '用户名不能为空！',
            'username.alphaDash' => '用户名格式不正确！',
            'username.length' => '用户名长度不正确！',
            'password.require'     => '密码不能为空！',
            'password.length'     => '密码长度不正确！',
            'password.confirm'        => '两次输入的密码不一样！',
            'email.email'        => '邮箱地址有误！',
            'roleid.require'        => '请选择一个权限组！'
        ];
        $validate = new Validate($rule,$msg);
        if (!$validate->check($data)) {
            $this->error = $validate->getError();
            return false;
        }
        $passwordinfo = password($data['password']);//对密码进行处理
        $data['password'] = $passwordinfo['password'];
        $data['encrypt']  = $passwordinfo['encrypt'];
        $id = $this->allowField(true)->save($data);
        if ($id) {
            return $id;
        }
        $this->error = '入库失败！';
        return false;
    }

    /**
     * 编辑管理员
     * @param [type] $data [修改数据]
     * @return boolean
     */
    public function editManager($data)
    {
        if (empty($data) || !isset($data['userid']) || !is_array($data)) {
            $this->error = '没有修改的数据！';
            return false;
        }
        $info = $this->where(array('userid' => $data['userid']))->find();
        if (empty($info)) {
            $this->error = '该管理员不存在！';
            return false;
        }
        //验证器
        $rule = [
            'password'  => 'length:6,20|confirm',
            'roleid'     => 'require'
        ];
        $msg = [
            'password.length'     => '密码长度不正确！',
            'password.confirm'        => '两次输入的密码不一样！',
            'roleid.require'        => '请选择一个权限组！'
        ];
        $validate = new Validate($rule,$msg);
        if (!$validate->check($data)) {
            $this->error = $validate->getError();
            return false;
        }
        //密码为空，表示不修改密码
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
            unset($data['encrypt']);
        }else{
            $passwordinfo = password($data['password']);//对密码进行处理
            $data['encrypt'] = $passwordinfo['encrypt'];
            $data['password'] = $passwordinfo['password'];
        }
        $status = $this->allowField(true)->isUpdate(true)->save($data);
        return $status !== false ? true : false;
    }

    /**
     * 删除管理员
     * @param type $userId
     * @return boolean
     */
    public function deleteManager($userId)
    {
        $userId = (int) $userId;
        if (empty($userId)) {
            $this->error = '请指定需要删除的用户ID！';
            return false;
        }
        if ($userId == config('USER_ADMINISTRATOR')) {
            $this->error = '禁止对超级管理员执行该操作！';
            return false;
        }
        if (false !== $this->where(array('userid' => $userId))->delete()) {
            return true;
        } else {
            $this->error = '删除失败！';
            return false;
        }
    }

    /**
     * 修改密码
     * @param int $uid 用户ID
     * @param string $newPass 新密码
     * @param string $password 旧密码
     * @return boolean
     */
    public function modifypw($uid, $newPass, $password = NULL) {
        //获取会员信息
        $userInfo = $this->getUserInfo((int) $uid, $password);
        if (empty($userInfo)) {
            $this->error = '旧密码不正确或者该用户不存在！';
            return false;
        }
        $passwordinfo = password($newPass);//对密码进行处理
        $data['encrypt'] = $passwordinfo['encrypt'];
        $data['password'] = $passwordinfo['password'];
        $status = $this->allowField(true)->isUpdate(true)->save($data,array('userid' => $userInfo['userid']));
        return $status !== false ? true : false;
    }


    /**
     * 获取用户信息
     * @param type $identifier 用户名或者用户ID
     * @return boolean|array
     */
    public function getUserInfo($identifier, $password = NULL) {
        if (empty($identifier)) {
            return false;
        }
        $map = array();
        //判断是uid还是用户名
        if (is_int($identifier)) {
            $map['userid'] = $identifier;
        } else {
            $map['username'] = $identifier;
        }
        $userInfo = $this->where($map)->find();
        if (empty($userInfo)) {
            return false;
        }
        //密码验证
        if (!empty($password) && password($password,$userInfo['encrypt']) != $userInfo['password']) {
            return false;
        }
        return $userInfo;
    }















}