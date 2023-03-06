<?php

namespace app\install\validate;

use think\Validate;

class Install extends Validate
{
    protected $rule = [
        'install-db-hostname|数据库地址' => 'require',
        'install-db-hostport|数据库端口' => 'require',
        'install-db-database|数据库名称' => 'require',
        'install-db-username|数据库用户名' => 'require',
        'install-db-password|数据库密码' => 'require',
        'install-admin-qq|联系QQ' => 'require|number|min:5|max:10',
        'install-admin-username|用户名' => 'require|min:5|max:25',
        'install-admin-password|密码' => 'require|min:6|alphaNum|max:18',
        'install-admin-password-confirm|确认密码' => 'require|confirm:install-admin-password',
    ];

    protected $scene = [
        'install' => ['install-db-hostname', 'install-db-hostport', 'install-db-database', 'install-db-username', 'install-db-password', 'install-admin-qq', 'install-admin-username', 'install-admin-password', 'install-admin-password-confirm'],
    ];
}