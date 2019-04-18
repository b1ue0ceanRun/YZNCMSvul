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
// | 会员首页管理
// +----------------------------------------------------------------------
namespace app\member\controller;

use app\member\model\Member as Member_Model;
use app\member\service\User;
use think\facade\Cookie;

class Index extends MemberBase
{

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->Member_Model = new Member_Model;
    }

    //会员中心首页
    public function index()
    {
        return $this->fetch('/index');

    }

    //登录页面
    public function login()
    {
        $cookie_url = $_REQUEST['forward'] ? $_REQUEST['forward'] : Cookie::get('__forward__');
        Cookie::set("forward", null);
        if (!empty($this->userid)) {
            $this->success("您已经是登陆状态！", $forward ? $forward : url("Index/index"));
        }
        if ($this->request->isPost()) {
            //登录验证
            $username = $this->request->param('username');
            $password = $this->request->param('password');
            $verify = $this->request->param('verify');
            $cookieTime = $this->request->param('cookieTime', 0);
            //验证码
            if (empty($verify) && $this->memberConfig['openverification']) {
                $this->error('验证码错误！');
            }
            if ($this->memberConfig['openverification'] && !captcha_check($verify)) {
                $this->error('验证码错误！');
            }
            $userid = $this->Member_Model->loginLocal($username, $password, $cookieTime ? 86400 * 180 : 86400);
            if ($userid > 0) {
                if (!$cookie_url) {
                    $cookie_url = url('index');
                }
                $this->success('登录成功！', $cookie_url);
            } else {
                //登陆失败
                $this->error('账号或者密码错误！');
            }

        } else {
            $this->assign('forward', $forward);
            return $this->fetch('/login');
        }
    }

    //注册页面
    public function register()
    {
        if (empty($this->memberConfig['allowregister'])) {
            $this->error("系统不允许新会员注册！");
        }
        $forward = $_REQUEST['forward'] ?: cookie("forward");
        cookie("forward", null);
        if ($this->userid) {
            $this->success("您已经是登陆状态，无需注册！", $forward ? $forward : url("index"));
        }
        if ($this->request->isPost()) {
            $post = $data = $this->request->post();
            $result = $this->validate($data, 'member.register');
            if (true !== $result) {
                return $this->error($result);
            }
            $userid = $this->Member_Model->userRegister($data['username'], $data['password'], $data['email']);
            if ($userid > 0) {
                unset($data['username'], $data['password'], $data['email']);
                //==============注册设置处理==============
                //新注册用户积分
                $data['point'] = $this->memberConfig['defualtpoint'] ? $this->memberConfig['defualtpoint'] : 0;
                //新会员注册默认赠送资金
                $data['amount'] = $this->memberConfig['defualtamount'] ? $this->memberConfig['defualtamount'] : 0;
                //新会员注册需要邮件验证
                if ($this->memberConfig['enablemailcheck']) {
                    $data['groupid'] = 7;
                    $data['status'] = 0;
                } else {
                    //新会员注册需要管理员审核
                    if ($this->memberConfig['registerverify']) {
                        $data['status'] = 0;
                    } else {
                        $data['status'] = 1;
                    }
                    //计算用户组
                    $data['groupid'] = $this->Member_Model->get_usergroup_bypoint($data['point']);
                }
                //==============注册设置处理==============

                if (false !== $this->Member_Model->save($data, ['id' => $userid])) {
                    //注册登陆状态
                    $this->Member_Model->loginLocal($post['username'], $post['password']);
                    $this->success('会员注册成功！', url('index'));
                } else {
                    //删除
                    $this->Member_Model->userDelete($userid);
                    $this->error("会员注册失败！");
                }
            } else {
                $this->error($this->Member_Model->getError() ?: '帐号注册失败！');
            }
        } else {
            return $this->fetch('/register');
        }
    }

    /**
     * 个人资料
     */
    public function profile()
    {
        return $this->fetch('/profile');

    }

    //手动退出登录
    public function logout()
    {
        if (User::instance()->logout()) {
            //手动登出时，清空forward
            Cookie::set("forward", null);
            $this->success('注销成功！', url("index/login"));
        }
    }

}
