<?php
declare (strict_types=1);

namespace app\api\controller;

use app\api\model\Info;
use app\api\model\TaskLogs;
use app\index\controller\Common;
use app\index\model\Accounts;
use app\index\model\Jobs;
use app\index\model\Tasks;
use app\index\model\Users;
use bilibili\BiliHelper;
use iqiyi\Iqiyi;
use netease\Netease;
use sport\Step;
use think\facade\Request;

class Cron extends Common
{
    public function __construct()
    {
        if (Request::get('key') != config('sys.cronkey')) {
            $res = ['code' => -1001, 'message' => '访问错误或没有权限'];
            exit(json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    }

    public function netease()
    {
        $tasks = Tasks::getTaskList('netease'); // 获取网易云任务列表
        $jobs = Jobs::getUnexecutedList('netease'); // 获取未执行任务列表
        if (count($jobs) == 0) {
            return resultJson(-1002, '没有要执行的任务');
        }
        if ($jobs) { // 在执行时间范围内 且 功能开启状态
            $config = [];
            foreach ($jobs as $key => $value) {
                if ($value['data']) {
                    $config += unserialize($value['data']); // 获取功能配置
                }
            }
            foreach ($jobs as $key => $value) {
                $user = Users::findByUid($value['uid']);
                $user_data = Accounts::where('user_id', '=', $value['user_id'])->find(); // 获取网易云账号信息
                $user_info = unserialize($user_data['data']);
                $do = new Netease($user_data['user_id'], $user_info['csrf'], $user_info['musicu'], $config); // 实例化 Netease
                foreach ($tasks as $k => $v) {
                    if ($value['do'] == $v['execute_name']) {
                        if ($v['vip'] == 1 && strtotime($user['vip_end'] ?? '') < time()) { // VIP过期 关闭任务
                            Users::where('uid', '=', $user['uid'])->update(['vip_start' => NULL, 'vip_end' => NULL]);
                            Jobs::where('type', '=', 'netease')->where('user_id', '=', $value['user_id'])->update(['state' => 0]);
                            $data = [
                                'type' => 'netease',
                                'user_id' => $value['user_id'],
                                'do' => $v['execute_name'],
                                'response' => '会员过期，请开通会员后再试',
                            ];
                            TaskLogs::operateLog($data);
                            break;
                        }
                        $execute = $do->{$v['execute_name']}();
                        if ($execute->cookiezt) {  // 状态失效
                            Accounts::where('user_id', '=', $user_data['user_id'])->update([
                                'state' => 0,
                            ]);
                            Jobs::where('user_id', '=', $user_data['user_id'])->where('type', '=', 'netease')->update([
                                'state' => 0,
                            ]);
                            if (config('sys.mail_invalid') == 1) {
                                $msg = get_mail_tempale(3, "网易云音乐");
                                $sub = config('web.webname') . '- 失效提醒';
                                send_mail($user['mail'], $sub, $msg);
                            }
                        } else {    // 状态未失效 写入运行日志
                            $data = [
                                'type' => 'netease',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => $execute['message'],
                            ];
                            TaskLogs::operateLog($data);
                        }
                        $info = new Info();
                        $info->where('sysid','=','100')->inc('times',1)->update();
                        $info->where('sysid','=','100')->update(['last' => date('Y-m-d H:i:s')]);
                    }
                    Jobs::updateJobInfo($v['execute_name'], $user_data['user_id'], [ // 更新任务执行信息
                        'lastExecute' => date("Y-m-d H:i:s"),
                        'nextExecute' => time() + $v['execute_rate'],
                    ]);
                }
            }
            return resultJson(1, '执行任务成功');
        }
    }

    public function bilibili()
    {
        $tasks = Tasks::getTaskList('bilibili'); // 获取哔哩哔哩任务列表
        $jobs = Jobs::getUnexecutedList('bilibili', [['do', '<>', 'globalroom']]); // 获取未执行任务列表 过滤全局配置任务
        if (count($jobs) == 0) {
            return resultJson(-1002, '没有要执行的任务');
        }
        if ($jobs) { // 在执行时间范围内 且 功能开启状态
            $config = [];
            foreach ($jobs as $key => $value) {
                if ($value['data']) {
                    $config += unserialize($value['data']); // 获取功能配置
                }
            }
            foreach ($jobs as $key => $value) {
                $user = Users::findByUid($value['uid']);
                $user_data = Accounts::where('user_id', '=', $value['user_id'])->find(); // 获取B站账号信息
                $user_info = unserialize($user_data['data']);
                $do = new BiliHelper($user_info['mid'], $user_info['mid_md5'], $user_info['token'], $user_info['csrf'], $user_info['access_key'], $config); // 实例化 BiliHelper
                foreach ($tasks as $k => $v) {
                    if ($value['do'] == $v['execute_name']) {
                        if ($v['vip'] == 1 && strtotime($user['vip_end'] ?? '') < time()) { // VIP过期 关闭任务
                            Users::where('uid', '=', $user['uid'])->update(['vip_start' => NULL, 'vip_end' => NULL]);
                            Jobs::where('type', '=', 'bilibili')->where('user_id', '=', $value['mid'])->update(['state' => 0]);
                            $data = [
                                'type' => 'netease',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => '会员过期，请开通会员后再试',
                            ];
                            TaskLogs::operateLog($data);
                            break;
                        }
                        $execute = $do->{$v['execute_name']}();
                        if ($execute->cookiezt) {   // 状态失效
                            Accounts::where('mid', '=', $user_data['user_id'])->update([
                                'state' => 0,
                            ]);
                            Jobs::where('user_id', '=', $user_data['user_id'])->where('type', '=', 'bilibili')->update([
                                'state' => 0,
                            ]);
                            if (config('sys.mail_invalid') == 1) {
                                $msg = get_mail_tempale(3, "哔哩哔哩");
                                $sub = config('web.webname') . '- 失效提醒';
                                send_mail($user['mail'], $sub, $msg);
                            }
                        } else {
                            $data = [
                                'type' => 'bilibili',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => $execute['message'],
                            ];
                            TaskLogs::operateLog($data);
                        }
                        $info = new Info();
                        $info->where('sysid','=','100')->inc('times',1)->update();
                        $info->where('sysid','=','100')->update(['last' => date('Y-m-d H:i:s')]);
                    }
                    Jobs::updateJobInfo($v['execute_name'], $user_info['mid'], [ // 更新任务执行信息
                        'lastExecute' => date("Y-m-d H:i:s"),
                        'nextExecute' => time() + $v['execute_rate'],
                    ]);
                }
            }
            return resultJson(1, '执行任务成功');
        }
    }

    public function sport()
    {
        $tasks = Tasks::getTaskList('sport'); // 获取步数任务列表
        $jobs = Jobs::getUnexecutedList('sport'); // 获取未执行任务列表
        if (count($jobs) == 0) {
            return resultJson(-1002, '没有要执行的任务');
        }
        if ($jobs) { // 在执行时间范围内 且 功能开启状态
            $config = [];
            foreach ($jobs as $key => $value) {
                if ($value['data']) {
                    $config += unserialize($value['data']); // 获取功能配置
                }
            }
            foreach ($jobs as $key => $value) {
                $user = Users::findByUid($value['uid']);
                $user_data = Accounts::where('user_id', '=', $value['user_id'])->find(); // 获取小米运动账号信息
                $user_info = unserialize($user_data['data']);
                $do = new Step($user_data['user_id'], $user_info['login_token'], $user_info['app_token'], $config); // 实例化 Step
                foreach ($tasks as $k => $v) {
                    if ($value['do'] == $v['execute_name']) {
                        if ($v['vip'] == 1 && strtotime($user['vip_end'] ?? '') < time()) { // VIP过期 关闭任务
                            Users::where('uid', '=', $user['uid'])->update(['vip_start' => NULL, 'vip_end' => NULL]);
                            Jobs::where('type', '=', 'soport')->where('user_id', '=', $value['user_id'])->update(['state' => 0]);
                            $data = [
                                'type' => 'netease',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => '会员过期，请开通会员后再试',
                            ];
                            TaskLogs::operateLog($data);
                            break;
                        }
                        $execute = $do->{$v['execute_name']}();
                        if ($execute->cookiezt) {   // 状态失效
                            Accounts::where('user_id', '=', $user_data['user_id'])->update([
                                'state' => 0,
                            ]);
                            Jobs::where('user_id', '=', $user_data['user_id'])->where('type', '=', 'sport')->update([
                                'state' => 0,
                            ]);
                            if (config('sys.mail_invalid') == 1) {
                                $msg = get_mail_tempale(3, "步数助手");
                                $sub = config('web.webname') . '- 失效提醒';
                                send_mail($user['mail'], $sub, $msg);
                            }
                        } else {    // 状态未失效 写入运行日志
                            $data = [
                                'type' => 'sport',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => $execute['message'],
                            ];
                            TaskLogs::operateLog($data);
                        }
                        $info = new Info();
                        $info->where('sysid','=','100')->inc('times',1)->update();
                        $info->where('sysid','=','100')->update(['last' => date('Y-m-d H:i:s')]);
                    }
                    Jobs::updateJobInfo($v['execute_name'], $user_data['user_id'], [ // 更新任务执行信息
                        'lastExecute' => date("Y-m-d H:i:s"),
                        'nextExecute' => time() + $v['execute_rate'],
                    ]);
                }
            }
            return resultJson(1, '执行任务成功');
        }
    }

    public function iqiyi()
    {
        $tasks = Tasks::getTaskList('iqiyi'); // 获取爱奇艺任务列表
        $jobs = Jobs::getUnexecutedList('iqiyi'); // 获取未执行任务列表
        if (count($jobs) == 0) {
            return resultJson(-1002, '没有要执行的任务');
        }
        if ($jobs) { // 在执行时间范围内 且 功能开启状态
            $config = [];
            foreach ($jobs as $key => $value) {
                if ($value['data']) {
                    $config += unserialize($value['data']); // 获取功能配置
                }
            }
            foreach ($jobs as $key => $value) {
                $user = Users::findByUid($value['uid']);
                $user_data = Accounts::where('user_id', '=', $value['user_id'])->find(); // 获取爱奇艺账号信息
                $user_info = unserialize($user_data['data']);
                $do = new Iqiyi($user_info['P00001'], $user_info['P00003'], $config); // 实例化 Iqiyi
                foreach ($tasks as $k => $v) {
                    if ($value['do'] == $v['execute_name']) {
                        if ($v['vip'] == 1 && strtotime($user['vip_end'] ?? '') < time()) { // VIP过期 关闭任务
                            Users::where('uid', '=', $user['uid'])->update(['vip_start' => NULL, 'vip_end' => NULL]);
                            Jobs::where('type', '=', 'iqiyi')->where('user_id', '=', $user_data['uid'])->update(['state' => 0]);
                            $data = [
                                'type' => 'netease',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => '会员过期，请开通会员后再试',
                            ];
                            TaskLogs::operateLog($data);
                            break;
                        }
                        $execute = $do->{$v['execute_name']}();
                        if ($do->cookiezt) {   // 状态失效
                            Accounts::where('user_id', '=', $user_data['user_id'])->update([
                                'state' => 0,
                            ]);
                            Jobs::where('user_id', '=', $user_data['user_id'])->where('type', '=', 'iqiyi')->update([
                                'state' => 0,
                            ]);
                            if (config('sys.mail_invalid') == 1) {
                                $msg = get_mail_tempale(3, "爱奇艺");
                                $sub = config('web.webname') . '- 失效提醒';
                                send_mail($user['mail'], $sub, $msg);
                            }
                        } else {    // 状态未失效 写入运行日志
                            $data = [
                                'type' => 'iqiyi',
                                'user_id' => $user_data['user_id'],
                                'do' => $v['execute_name'],
                                'response' => $execute['message'],
                            ];
                            TaskLogs::operateLog($data);
                        }
                        $info = new Info();
                        $info->where('sysid','=','100')->inc('times',1)->update();
                        $info->where('sysid','=','100')->update(['last' => date('Y-m-d H:i:s')]);
                    }
                    Jobs::updateJobInfo($v['execute_name'], $user_data['user_id'], [ // 更新任务执行信息
                        'lastExecute' => date("Y-m-d H:i:s"),
                        'nextExecute' => time() + $v['execute_rate'],
                    ]);
                }
            }
            return resultJson(1, '执行任务成功');
        }
    }
}