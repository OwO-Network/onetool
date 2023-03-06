<?php

namespace app\index\controller;

use app\index\model\Users;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Oauth extends Common
{
    protected $middleware = [
        'app\middleware\CheckLoginUser'
    ];

    public function qq_callback()
    {
        $token = Request::get('code');
        $state = Request::get('state');
        if (cookie('oauth_state') != $state) {
            View::assign([
                'msg' => 'QQ授权验证失败',
                'url' => url('/index/console')
            ]);
            return View::fetch('/common/alert');
        } else {
            if (!$qqInfo = $this->get_Oauth_Info($token)) {
                View::assign([
                    'msg' => '获取登录QQ信息失败',
                    'url' => url('/index/console')
                ]);
                return View::fetch('/common/alert');
            } elseif (!$userInfo = Users::where('token', '=', $token)->find()) {    //用户未绑定QQ
                cookie('oauth_state', null);
                View::assign([
                    'msg' => 'QQ未绑定用户',
                    'url' => url('/index/index')
                ]);
                return View::fetch('/common/alert');
            } else {
                Users::qqlogin($userInfo);
                cookie('oauth_state', null);
                header('Location:' . url('/index/console'));
            }
        }
    }

    public function get_Oauth_Info($code, $type = 'qqlogin')
    {
        //查询接口，返回登陆用户的信息，开放平台token，openid，昵称等
        $CloudInfo_Api = 'https://qqlogin.qqshabi.cn/Oauth/getOauthInfo.api';
        //访问接口，返回json
        $json = file_get_contents($CloudInfo_Api . '?code=' . $code . '&type=' . $type);
        $oauth_user = json_decode($json, true);
        return $oauth_user;
    }

    public function qq_set_callback()
    {
        $token = Request::get('code');
        $state = Request::get('state');
        if (cookie('oauth_state') != $state) {
            View::assign([
                'msg' => 'QQ授权验证失败',
                'url' => url('/index/console')
            ]);
            return View::fetch('/common/alert');
        } else {
            if (!$qqInfo = $this->get_Oauth_Info($token)) {
                View::assign([
                    'msg' => '获取登录QQ信息失败',
                    'url' => url('/index/console')
                ]);
                return View::fetch('/common/alert');
            } elseif ($userInfo = Users::where('token', '=', $token)->find()) {    //用户已绑定QQ
                cookie('oauth_state', null);
                View::assign([
                    'msg' => '该QQ账号已绑定过或绑定过其他用户',
                    'url' => url('/index/index')
                ]);
                return View::fetch('/common/alert');
            } else {
                $is_set = Users::updateByUid(Session::get('user.uid'), [
                    'token' => $token,
                    'nickname' => $qqInfo['nickname']
                ]);
                if ($is_set) {
                    $userInfo = Users::where('token', '=', $token)->find();
                    Users::qqlogin($userInfo);
                    header('Location:' . url('/index/console'));
                    cookie('oauth_state', null);
                    exit();
                } else {
                    cookie('oauth_state', null);
                    View::assign([
                        'msg' => '更改失败，请重试',
                        'url' => url('/index/console')
                    ]);
                    return View::fetch('/common/alert');
                }
            }
        }
    }
}