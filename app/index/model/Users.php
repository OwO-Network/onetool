<?php
declare (strict_types=1);

namespace app\index\model;

use app\index\validate\Users as UsersValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Session;
use think\facade\View;
use think\Model;
use think\response\Json;


class Users extends Model
{
    protected $pk = 'uid';

    /**
     * reg 用户注册
     * @param $data
     * @return Json|void
     * @author BadCen
     */
    public static function reg($data)
    {
        //自动验证
        try {
            validate(UsersValidate::class)->scene('reg')->check($data);
        } catch (ValidateException $e) {
            //验证失败 输出错误信息
            return resultJson(-1, $e->getMessage());
        }
        $self = new static();
        if ($self->where('username', $data['username'])->find()) {
            return resultJson(-1, '用户名已存在');
        } elseif ($self->where('qq', $data['qq'])->find()) {
            return resultJson(-1, '该QQ已存在');
        } else {
            $give = config('sys.reg_give_type');
            switch ($give) {
                case 1:
                    $vip_start = date('Y-m-d H:i:s');
                    $vip_end = date('Y-m-d', strtotime('+' . config('sys.reg_give_value') . 'day'));
                    break;
                case 2:
                    $quota = config('sys.reg_give_value');
                    break;
                case 3:
                    $money = config('sys.reg_give_value');
                    break;
            }
            $ret = $self->create([
                'web_id' => WEB_ID,
                'username' => $data['username'],
                'password' => md5($data['password']),
                'qq' => $data['qq'],
                'mail' => $data['qq'] . '@qq.com',
                'nickname' => get_qqname($data['qq']),
                'money' => $money ?? 0.00,
                'quota' => $quota ?? 0,
                'vip_start' => $vip_start ?? null,
                'vip_end' => $vip_end ?? null,
                'agent' => 0,
                'login_time' => time(),
                'login_ip' => real_ip(),
                'login_city' => get_ip_city(real_ip()),
            ]);
            if ($ret->uid) {
                return resultJson(1, '注册成功');
            } else {
                return resultJson(0, '注册失败');
            }
        }
    }

    /**
     * qqlogin QQ登录
     * @param $userInfo
     * @return bool|string
     * @author BadCen
     */
    public static function qqlogin($userInfo)
    {
        $self = new static();
        $row = Users::where('uid', '=', $userInfo['uid'])->find();
        if (!$row['state']) {
            View::assign([
                'msg' => '该账号已被封禁',
                'url' => url('index/index')
            ]);
            return View::fetch('/common/alert');
        } else {
            Session::set('user', $row->toArray());
            $update = $self->where('uid', '=', $row['uid'])
                ->update([
                    'login_ip' => real_ip(),
                    'login_city' => get_ip_city(real_ip()),
                    'login_time' => time()
                ]);
            if ($update) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * findPass 找回密码
     * @param $data
     * @return Json|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function findPass($data)
    {
        //自动验证
        try {
            validate(UsersValidate::class)->scene('find')->check($data);
        } catch (ValidateException $e) {
            //验证失败 输出错误信息
            return resultJson(-1, $e->getMessage());
        }
        $self = new static();
        $row = $self->where('mail', $data['mail'])->find();
        if (!$row) {
            return resultJson(-1, '邮箱不存在');
        } else {
            $user_data = $self->where('mail', '=', $data['mail'])->find();
            $token = md5($user_data['uid'] . $user_data['username'] . time() . real_ip());
            Users::updateByUid($user_data['uid'], [
                'sid' => $token
            ]);
            $sign = get_Domain() . 'index/login/reset/?mail=' . $data['mail'] . '&token=' . md5($user_data['uid'] . $user_data['username'] . time() . real_ip()) . '&access=' . get_os();
            $content = get_mail_tempale(2, $user_data, $sign);
            if ($result = Captcha::send_captcha($data['mail'], '找回密码', $content)) {
                return resultJson($result['code'], $result['message']);
            }
        }
    }

    /**
     * updateByUid 更新用户信息
     * @param $uid
     * @param $data
     * @author BadCen
     */
    public static function updateByUid($uid, $data = [])
    {
        $self = new static();
        if ($result = $self->where('uid', '=', $uid)->update($data)) {
            return $result;
        }
        return false;
    }

    /**
     * reset 重置密码
     * @param $data
     * @return Json|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function reset($data)
    {
        //自动验证
        try {
            validate(UsersValidate::class)->scene('reset')->check($data);
        } catch (ValidateException $e) {
            //验证失败 输出错误信息
            return resultJson(-1, $e->getMessage());
        }
        $self = new static();
        $row = $self->where('mail', '=', $data['mail'])->find();
        $new_pass = md5($data['repass']);
        if (Users::where('uid', '=', $row['uid'])->update([
            'password' => $new_pass
        ])) {
            $login = [
                'username' => $row['username'],
                'password' => $data['password']
            ];
            Users::login($login);
            return resultJson(1, '重置密码成功，登录中');
        } else {
            return resultJson(0, '重置密码失败，请重新操作！');
        }
    }

    /**
     * login 用户登录
     * @param $data
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function login($data)
    {
        //自动验证
        try {
            validate(UsersValidate::class)->scene('login')->check($data);
        } catch (ValidateException $e) {
            //验证失败 输出错误信息
            return resultJson(-1, $e->getError());
        }
        $self = new static();
        $row = $self->where('username', $data['username'])->find();
        if (!$row) {
            return resultJson(-1, '用户名不存在');
        } elseif ($row['state'] != 1) {
            return resultJson(-1, '该账号已被封禁');
        } elseif (md5($data['password']) != $row['password']) {
            return resultJson(-1, '密码错误');
        } elseif ($row['web_id'] != WEB_ID) {
            return resultJson(-1, '该账号不属于当前站点');
        } else {
            Session::set('user', $row->toArray());
            $self->where('uid', $row['uid'])
                ->update([
                    'login_ip' => real_ip(),
                    'login_city' => get_ip_city(real_ip()),
                    'login_time' => time()
                ]);
            return resultJson(1, '登录成功');
        }
    }

    /**
     * changePassWord 修改密码
     * @param $uid
     * @param $data
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function changePassWord($uid, $data)
    {
        //自动验证
        try {
            validate(UsersValidate::class)->scene('changePassWord')->check($data);
        } catch (ValidateException $e) {
            //验证失败 输出错误信息
            return resultJson(-1, $e->getError());
        }
        $self = new static();
        $row = $self->where('uid', '=', $uid)->find();
        if (md5($data['outpass']) != $row['password']) {
            return resultJson(-1, '原密码错误');
        } else {
            $newPass = md5($data['repass']);
            if (Users::where('uid', '=', Session::get('user.uid'))->update(['password' => $newPass])) {
                Session::delete('user');
                return resultJson(1, '修改成功，请重新登录');
            } else {
                return resultJson(0, '修改失败');
            }
        }
    }

    /**
     * updateMyInfo 更新用户的SESSION缓存
     * @author BadCen
     */
    public static function updateMyInfo()
    {
        $self = new static();
        $ret = $self->getByUid(Session::get('user.uid'));
        if ($ret) {
            session::set('user', $ret->toArray());
        }
    }

    /**
     * logout 注销登录
     * @return Json
     * @author BadCen
     */
    public static function logout()
    {
        Session::delete('user');
        return resultJson(1, '退出登录成功');
    }

    /**
     * userCount 用户数量
     * @return false|int
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function userCount()
    {
        $self = new static();
        return $self->select()->count();
    }

    /**
     * agentCount 代理数量
     * @return false|int
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function agentCount()
    {
        $self = new static();
        return $self->where('agent', '<>', 0)->select()->count();
    }

    /**
     * findByUid
     * @param $uid
     * @return Users|array|false|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author BadCen
     */
    public static function findByUid($uid)
    {
        $self = new static();
        if ($result = $self->where('uid', $uid)->find()) {
            return $result;
        }
        return false;
    }

    public static function getUserList()
    {
        $self = new static();
        if (WEB_ID == 1) {
            return $self->select();
        } elseif ($result = $self->where('web_id', '=', WEB_ID)->select()) {
            return $result;
        }
        return false;
    }

    public static function delByUid($uid)
    {
        $self = new static();
        if ($result = $self->where('uid', '=', $uid)->delete()) {
            return $result;
        } else {
            return false;
        }
    }
}