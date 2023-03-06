<?php
declare (strict_types=1);

namespace app\index\controller;

use app\index\model\Users;
use think\facade\Session;
use think\facade\View;
use think\response\Redirect;

class Login extends Common
{
    /**
     * login
     * 用户登录
     * @return string|Redirect
     * @author BadCen
     */
    public function login()
    {
        if (Session::has('user')) {
            return redirect((string)url('/index/console/index'));
        } else {
            return View::fetch('/login/login');
        }
    }

    /**
     * reg
     * 用户注册
     * @return string|Redirect
     * @author BadCen
     */
    public function reg()
    {
        if (Session::has('user')) {
            return redirect((string)url('/index/console/index'));
        } else {
            return View::fetch('/login/reg');
        }
    }

    /**
     * find
     * 找回密码
     * @return string
     * @author BadCen
     */
    public function find()
    {
        return View::fetch('/login/find');
    }

    public function reset()
    {
        $token = input('get.token');
        $mail = input('get.mail');
        if (!$token || strlen($token) !== 32 || !$mail) {
            View::assign([
                'msg' => '参数错误',
                'url' => url('index')
            ]);
            return View::fetch('/common/alert');
        } else {
            $user = Users::where('mail', '=', $mail)->find();
            if ($user['sid'] != $token) {
                View::assign([
                    'msg' => '令牌效验失败，请返回重试！',
                    'url' => url('login/find')
                ]);
                return View::fetch('/common/alert');
            } else {
                View::assign([
                    'webTitle' => '设置新密码',
                    'mail' => $mail
                ]);
                return View::fetch('login/reset');
            }
        }
    }

    public function qqLogin()
    {
        //回调地址
        $call_back = get_Domain() . 'index/oauth/qq_callback';
        //快捷登陆API
        $cloud_api = 'https://qqlogin.qqshabi.cn/Oauth/request.api';
        //生成一个随机的state
        $state = md5(uniqid((string)rand(), TRUE));
        //存入session中，用来回调的验证
        cookie('oauth_state', $state);
        //qq登陆，type参数是qqlogin
        $type = 'qqlogin';
        //拼接URL
        $login_url = $cloud_api . '?state=' . $state . '&type=' . $type . '&redirect_uri=' . $call_back;
        return redirect($login_url);
    }


}