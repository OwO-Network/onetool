<?php
declare (strict_types=1);

namespace app\index\controller;

use app\index\model\Users;
use app\index\model\Weblist;
use think\facade\View;

class Index extends Common
{
    public function _empty()
    {
        return View::fetch('index/index');
    }

    public function index()
    {
        View::assign([
            'timeCount' => Weblist::start_Time(),
            'userCount' => Users::userCount(),
        ]);
        return View::fetch('index/index');
    }


}