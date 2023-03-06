<?php

namespace app\api\model;

use app\index\model\Tasks;
use think\Model;

class TaskLogs extends Model
{
    public static function operateLog($data = [])
    {
        $self = new static();
        $data['do'] = Tasks::where('execute_name', '=', $data['do'])->find()['name'] ?? $data['do'];
        $insert = [
            'type' => $data['type'],
            'user_id' => $data['user_id'],
            'do' => $data['do'],
            'response' => $data['response'],
            'addtime' => date('Y-m-d H:i:s'),
        ];
        if ($self->field('type,user_id,do,response,addtime')->insert($insert)) {
            return true;
        }
        return false;
    }
}