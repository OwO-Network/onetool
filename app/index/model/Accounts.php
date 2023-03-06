<?php

namespace app\index\model;

use think\facade\Session;
use think\Model;

class Accounts extends Model
{
    public static function add($type = null, $user_id = null, $data = [])
    {
        $self = new static();
        $query = $self->where('type', '=', $type)
            ->where('user_id', '=', $user_id)
            ->where('uid', '=', Session::get('user.uid'));
        if ($query->find()) {
            $adata = serialize($data); // 序列化数组
            $data = [
                'addtime' => date('Y-m-d H:i:s'),
                'state' => 1,
            ];
            $data = array_merge($data, ['data' => $adata]);
            $query->update($data);
            Jobs::updateJob($type, $user_id);
            return resultJson(1, '更新成功');
        } else {
            if (Session::get('user.quota') > Accounts::getMyAccountNum()) {
                $a_data = serialize($data); // 序列化数组
                $data = [
                    'uid' => Session::get('user.uid'),
                    'type' => $type,
                    'user_id' => $user_id,
                    'addtime' => date('Y-m-d H:i:s'),
                    'zid' => WEB_ID
                ];
                $data = array_merge($data, ['data' => $a_data]);
                $self->insert($data);
                Jobs::add($type, $user_id);
                return resultJson(1, '登录成功');
            } else {
                return resultJson(0, '账号配额不足，请先购买挂机配额后再试');
            }

        }
    }

    public static function getMyList($type)
    {
        $self = new static();
        if ($result = $self->where('type', '=', $type)->where('uid', Session::get('user.uid'))->select()) {
            return $result;
        }
        return false;
    }

    public static function findByUserId($user_id)
    {
        $self = new static();
        if ($result = $self->where('user_id', $user_id)->where('uid', Session::get('user.uid'))->find()) {
            return $result;
        }
        return false;
    }

    public static function delByUserId($user_id)
    {
        $self = new static();
        if($result = $self->where('user_id', $user_id)->where('uid', Session::get('user.uid'))->delete()){
            return $result;
        }
        return false;
    }

    public static function getMyAccountNum()
    {
        $self = new static();
        $result = $self->where('uid', '=', Session::get('user.uid'))->select()->count();
        return $result;

    }
}