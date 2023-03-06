<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

////用户登录
//Route::rule('login','login/login');
////用户注册
//Route::rule('reg', 'login/reg');
//网易云
Route::rule('console/netease/[:act]/[:user_id]', 'console/netease');
//网易云Ajax
Route::rule('ajax/netease/[:act]', 'ajax/netease');
//哔哩哔哩
Route::rule('console/bilibili/[:act]/[:mid]', 'console/bilibili');
//哔哩哔哩Ajax
Route::rule('ajax/bilibili/[:act]', 'ajax/bilibili');
//运动助手
Route::rule('console/sport/[:act]/[:uid]', 'console/sport');
//运动助手ajax
Route::rule('ajax/sport/[:act]', 'ajax/sport');;
//爱奇艺
Route::rule('console/iqiyi/[:act]/[:uid]', 'console/iqiyi');
//爱奇艺Ajax
Route::rule('ajax/iqiyi/[:act]', 'ajax/iqiyi');
//用户
Route::rule('console/user/[:act]', 'console/user');
//用户Ajax
Route::rule('ajax/user/[:act]', 'ajax/user');
//商城
Route::rule('console/shop/[:act]', 'console/shop');
//商城Ajax
Route::rule('ajax/shop/[:act]', 'ajax/shop');
//代理Ajax
Route::rule('ajax/agent/[:act]', 'ajax/agent');
//Api运行
Route::rule('api/netease/[:act]', 'api/netease');
