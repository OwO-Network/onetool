<?php

namespace app\admin\model;

use think\Model;

class Accounts extends Model
{
    public static function getAccountList()
    {
        $self = new static();
        if ($result = $self->where('zid', '=', WEB_ID)->order('type desc ')->select()) {
            return $result;
        }
        return false;
    }

    public static function delByid($id)
    {
        $self = new static();
        if ($self->where('user_id', '=', $id)->where('zid', '=', WEB_ID)->delete()) {
            return true;
        }
        return false;
    }
}