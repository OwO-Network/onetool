<?php

namespace app\middleware;

use think\facade\Db;
use think\facade\Config;
use think\facade\Session;

class LoadConfigs
{

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     */
    public function handle($request, \Closure $next)
    {
        //判断是否安装
        if (!file_exists('../config/Db.php')){
            header("Location:/install");
            exit;
        }
        $acc_domain = $_SERVER['HTTP_HOST'];
        $web_domain_list = Db::name('weblist')
            ->where('domain', '=', $acc_domain)
            ->whereOr('domain2', '=', $acc_domain)
            ->find();
        if ($web_domain_list) {
            define('WEB_ID', $web_domain_list['web_id']);
            define('PREFIX', $web_domain_list['prefix']);
            define('WEB_KEY', md5($web_domain_list['web_key']));
            define('RUN_KEY', md5(config::get('database.connections.mysql')['username'] . md5(config('database.connections.mysql')['password'])));
            $res = Db::table($web_domain_list['prefix'] . 'configs')->select();
            $config = array();
            $config2 = array();
            foreach ($res as $k => $v) {
                $config = array_merge($config, array($res[$k]['k'] => $res[$k]['v']));
            }
            foreach ($web_domain_list as $key => $val) {
                $config2 = array_merge($config2, array($key => $val));
            }
            config::set($config, 'sys');
            config::set($config2, 'web');
        } else {
            exit('此站点未开通');
        }
        //判断站点是否过期
        if (WEB_ID != 1 && strtotime($web_domain_list['end_time']) < time()) {
            exit('该站点已过期');
        }
        if (WEB_ID != 1 && $web_domain_list['status'] != 1) {
            exit('该站点已被封禁');
        }
        // 继续执行进入到控制器
        return $next($request);
    }
}