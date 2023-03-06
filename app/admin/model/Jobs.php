<?php

namespace app\admin\model;

use think\Model;

class Jobs extends Model
{
    public function turnOffTask($user_id, $do)
    {
        return ($this->where('user_id', '=', $user_id)
                ->where('do', '=', $do)
                ->update(['state' => 0]) !== false);
    }

    public static function delByid($id)
    {
        $self = new static();
        $self->where('user_id', '=', $id)->delete();
        return true;
    }
}