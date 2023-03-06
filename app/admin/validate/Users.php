<?php
/*
 * @Author: Vincent Young
 * @Date: 2022-03-08 19:32:08
 * @LastEditors: Vincent Young
 * @LastEditTime: 2023-03-06 19:47:25
 * @FilePath: /onetool/app/admin/validate/Users.php
 * @Telegram: https://t.me/missuo
 * 
 * Copyright © 2023 by Vincent, All Rights Reserved. 
 */

namespace app\admin\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule = [
        'password' => 'require|min:6|max:30',
    ];

    protected $message = [
        'password.require' => '密码不能为空',
        'password.min' => '请输入不低于6位的用户密码',
        'password.max' => '请输入6-30位的用户密码',
        // 'password.alphaNum' => '登录密码只能是字母和数字！',
    ];

    protected $scene = [
        'edit' => ['password'],
    ];
}