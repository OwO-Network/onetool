<?php

namespace app\index\model;

use think\Model;

class TaskLogs extends Model
{
    public static function searchLogs($type, $user_id)
    {
        $self = new static();
        if ($result = $self->where('type', '=', $type)->where('user_id', '=', $user_id)->order('addtime desc')->limit(50)->select()) {
            return $result;
        }
        return false;
    }

    public static function deleteLogs($type, $user_id)
    {
        $self = new static();
        if ($result = $self->where('type', '=', $type)->where('user_id', '=', $user_id)->delete() !== false) {
            return $result;
        }
        return false;
    }
}