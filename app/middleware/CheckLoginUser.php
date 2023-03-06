<?php

namespace app\middleware;

use app\index\model\Users;
use think\facade\Session;
use think\facade\View;

class CheckLoginUser
{
    /**
     * @var string[]
     */
    private $authController = [
        'Oauth/qq_callback',
    ];

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response|string
     */
    public function handle($request, \Closure $next)
    {
        // 判断是否登录
        if (empty(Session::get('user')) && !in_array($request->controller() . '/' . $request->action(), $this->authController)) {
            // 未登录 跳转到登录页面
            exit(redirect('/index/login/login')->send());
        } else {
            // 已登录 读取用户信息并存入Session
            $ret = Users::getByUid(Session::get('user.uid'));
            if ($ret) {
                session::set('user', $ret->toArray());
            }
            //检测用户VIP是否过期
            if (strtotime(Session::get('user.vip_end') ?? 0) < time()) {
                Users::where('uid', '=', Session::get('user.uid'))->update(['vip_start' => NULL, 'vip_end' => NULL]);
            }
        }
        if ($request->action() == 'agent' && empty(Session::get('user.agent'))) {
            // 无代理权限
            View::assign(['msg' => '权限不足', 'url' => '/index/console']);
            exit(View::fetch('common/alert'));
        }
        // 继续执行进入到控制器
        return $next($request);
    }
}
