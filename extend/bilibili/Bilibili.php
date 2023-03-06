<?php

namespace bilibili;

use bilibili\Curl;

class Bilibili
{
    public $cookiezt;

    const ACTIVE_TITLE = '';
    const ACTIVE_SWITCH = '';

    protected static $access_key = null;
    protected static $wait_list = array();
    protected static $finish_list = array();
    protected static $all_list = array();
    protected static $banned_rids = array();

    public function __construct($mid = null, $mid_md5 = null, $token = null, $csrf = null, $access_key = null, $config = [])
    {
        $this->mid = $mid;
        $this->mid_md5 = $mid_md5;
        $this->token = $token;
        $this->csrf = $csrf;
        self::$access_key = $access_key;
        $this->config = $config;
        $this->cookie = 'DedeUserID='.$mid.'; DedeUserID__ckMd5='.$mid_md5.'; SESSDATA='.$token.'; bili_jct='.$csrf.'; sid=knhc6kmk;';
    }

    /**
     * getAccessToken
     * @return mixed
     * @author BadCen
     */
    public function getAccessToken($cookie)
    {
        $url = 'https://passport.bilibili.com/login/app/third';
        $payload = [
            'appkey' => '27eb53fc9058f8c3',
            'api' => 'https://www.mcbbs.net/template/mcbbs/image/special_photo_bg.png',
            'sign' => '04224646d1fea004e79606d3b038c84a'
        ];
        $data  = Curl::get('other', $url, $payload, $cookie);
        $arr = json_decode($data['body'],  true);
        $data = self::curl($arr['data']['confirm_uri'], [], $cookie);
        preg_match("/access_key=(.*?)&/", $data['header'], $match);
        return $match[1];
    }

    /*
     * 获取极验参数
    */
    public function geetest()
    {
        $url = 'https://passport.bilibili.com/x/passport-login/captcha?source=main_mini&t=0.887951' . time();
        $ret = Curl::get('other', $url);
        $arr = json_decode($ret['body'], true);
        return ['code' => 1, 'message' => null, 'data' => $arr['data'] ];
    }

    /*
     * 加密登录密码
    */
    public function encrypted_password($password = null): string
    {
        $url = 'https://passport.bilibili.com/x/passport-login/web/key?_=164' . time();
        $payload = [];
        $data = Curl::get('other', $url, $payload);  //获取加密公钥及密码盐值1（web端）
        $arr = json_decode($data['body'], true);
        openssl_public_encrypt($arr['data']['hash'] . $password, $encrypted, $arr['data']['key']);
        return base64_encode($encrypted);
    }

    /*
     * 账号密码登录
     */
    public function login($data = [])
    {
        $url = 'https://passport.bilibili.com/x/passport-login/web/login'; //使用账号密码登录（web端）
        $payload = [
            'username' => $data['username'],
            'password' => self::encrypted_password($data['password']),
            'keep' =>   0,
            'source' => 'main_mini',
            'token'   => $data['key'],
            'go_url' => 'https://www.bilibili.com/',
            'challenge' => $data['geetest_challenge'],
            'validate' => $data['geetest_validate'],
            'seccode' => $data['geetest_seccode'],
        ];
        $headers = [
            'Referer' => 'https://www.bilibili.com/',
        ];
        $raw = Curl::post('other', $url, $payload, '', $headers);
        $arr = json_decode($raw['body'], true);
        if ($arr['data'] && $arr['data']['status'] == 0) {
            preg_match('/DedeUserID=(.*?)\;/', $raw['header'], $mid);
            preg_match('/DedeUserID__ckMd5=(.*?)\;/', $raw['header'], $mid_md5);
            preg_match('/SESSDATA=(.*?)\;/', $raw['header'], $token);
            preg_match('/bili_jct=(.*?)\;/', $raw['header'], $csrf);
            $cookie = 'DedeUserID=' . $mid[1] . '; ' . 'DedeUserID__ckMd5=' . $mid_md5[1] . '; ' . 'SESSDATA=' . $token[1] . '; ' . 'bili_jct=' . $csrf[1] . '; ';
            $access_key = self::getAccessToken($cookie);
            return array('code' => 1, 'message' => '登录成功', 'data' => ['cookie' => $cookie, 'access_key' => $access_key]);
        } elseif ($arr['data'] && $arr['data']['status'] == 2) {
            return array('code' => 2, 'message' => '本次登录环境存在风险, 请尝试使用扫码登录');
        } else {
            return array('code' => 0, 'message' => $arr['message']);
        }
    }

    public function getQrimg()
    {
        $url = 'https://passport.bilibili.com/qrcode/getLoginUrl';
        $payload = [];
        $raw = Curl::get('other', $url, $payload);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['code'] == 0) {
            return array('code' => 1, 'message' => '获取成功', 'url' => $de_raw['data']['url'], 'oauthKey' => $de_raw['data']['oauthKey']);
        } else {
            return array('code' => 0, 'message' => '获取失败');
        }
    }

    public function qrLogin($key)
    {
        $url = 'http://passport.bilibili.com/qrcode/getLoginInfo';
        $payload = [
            'oauthKey' => $key
        ];
        $raw = Curl::post('other', $url, $payload);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['status'] == true) {
            preg_match('/DedeUserID=(.*?)\;/', $raw['header'], $mid);
            preg_match('/DedeUserID__ckMd5=(.*?)\;/', $raw['header'], $mid_md5);
            preg_match('/SESSDATA=(.*?)\;/', $raw['header'], $token);
            preg_match('/bili_jct=(.*?)\;/', $raw['header'], $csrf);
            $cookie = 'DedeUserID=' . $mid[1] . '; ' . 'DedeUserID__ckMd5=' . $mid_md5[1] . '; ' . 'SESSDATA=' . $token[1] . '; ' . 'bili_jct=' . $csrf[1] . '; ';
            $access_key = self::getAccessToken($cookie);
            return array('code' => 1, 'message' => '登录成功', 'data' => ['cookie' => $cookie, 'access_key' => $access_key]);
        } else {
            if ($de_raw['data'] == -4) {
                return array('code' => -1, 'message' => '请使用哔哩哔哩APP扫描二维码');
            } elseif ($de_raw['data'] == -5) {
                return array('code' => -2, 'message' => '请在哔哩哔哩APP确认登录');
            } elseif ($de_raw['data'] == -2) {
                return array('code' => -3, 'message' => '登录超时请重新获取二维码');
            } elseif ($de_raw['data'] == -1) {
                return array('code' => -4, 'message' => '密钥错误');
            } else {
                return array('code' => -1000, 'message' => '未知错误');
            }
        }
    }

    /*
     * 获取登录信息
     */
    public function login_info()
    {
        $url = 'https://api.bilibili.com/x/web-interface/nav';
        return Curl::get('pc', $url, [], $this->cookie);
    }

    /**
     * watchAid
     * @return array|void
     * @author BadCen
     */
    public function watchAid()
    {
        $url = "https://api.bilibili.com/x/report/click/h5";
        $av_info = self::parseAid();
        $payload = [
            'aid' => $av_info['aid'],
            'cid' => $av_info['cid'],
            'part' => 1,
            'did' => $this->mid_md5,
            'ftime' => time(),
            'jsonp' => "jsonp",
            'lv' => "",
            'mid' => $this->mid,
            'csrf' => $this->csrf,
            'stime' => time()
        ];
        $headers = [
            'Host' => "api.bilibili.com",
            'Origin' => "https://www.bilibili.com",
            'Referer' => "https://www.bilibili.com/video/av{$av_info['aid']}",
            'User-Agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.81 Safari/537.36",
        ];
        $raw = Curl::post('pc', $url, $payload, $this->cookie, $headers);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['code'] == 0) {
            $url = "https://api.bilibili.com/x/report/web/heartbeat";
            $payload = [
                "aid" => $av_info['aid'],
                "cid" => $av_info['cid'],
                "mid" => $this->mid,
                "csrf" => $this->csrf,
                "jsonp" => "jsonp",
                "played_time" => "0",
                "realtime" => $av_info['duration'],
                "pause" => false,
                "dt" => "7",
                "play_type" => "1",
                'start_ts' => time()
            ];
            $raw = Curl::post('pc', $url, $payload, $this->cookie, $headers);
            $de_raw = json_decode($raw['body'], true);

            if ($de_raw['code'] == 0) {
                sleep(5);
                $payload['played_time'] = $av_info['duration'] - 1;
                $payload['play_type'] = 0;
                $payload['start_ts'] = time();
                $raw = Curl::post('pc', $url, $payload, $this->cookie, $headers);
                $de_raw = json_decode($raw['body'], true);
                if ($de_raw['code'] == 0) {
                    return array('code' => 1, 'message' => '主站任务：av' . $av_info['aid'] . '观看成功');
                }
            }
        }
    }

    /**
     * @use 分享视频
     * @return array|bool
     */
    public function shareAid()
    {
        # aid = 稿件av号
        $url = "https://api.bilibili.com/x/web-interface/share/add";
        $av_info = self::parseAid();
        $payload = [
            'aid' => $av_info['aid'],
            'jsonp' => "jsonp",
            'csrf' => $this->csrf,
        ];
        $headers = [
            'Host' => "api.bilibili.com",
            'Origin' => "https://www.bilibili.com",
            'Referer' => "https://www.bilibili.com/video/av{$av_info['aid']}",
            'User-Agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.81 Safari/537.36",
        ];
        $raw = Curl::post('pc', $url, $payload, $this->cookie, $headers);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['code'] == 0) {
            return array('code' => 1, 'message' => '主站任务：av' . $av_info['aid'] . '分享成功');
        } else {
            return array('code' => 0, 'message' => '主站任务：av' . $av_info['aid'] . '分享失败');
        }
    }

    /**
     * @use 视频投币
     * @return array|bool
     */
    public function coinAdd()
    {
        $res = array();
        // 预计数量 失败默认0  避免损失
        $estimate_num = $this->config['add_coin_num'] ?? 0;
        // 库存数量
        $stock_num = self::getCoin();
        // 实际数量 处理硬币库存少于预计数量
        $actual_num = intval($estimate_num > $stock_num ? $stock_num : $estimate_num) - self::coinLog();

        // 上限
        if ($actual_num <= 0) {
            return array('code' => 0, 'message' => '今日投币上限已满');
        }
        // 稿件列表 random(随机热门)/fixed(关注列表)
        $add_coin_mode = $this->config['add_coin_mode'] ?? 'random';
        if ($add_coin_mode == 'random') {
            // 随机热门稿件榜单
            $aids = self::getTopRCmdAids($actual_num);
        } else {
            // 固定获取关注UP稿件榜单, 不足会随机补全
            $aids = self::getFollowUpAids($actual_num);
        }
        // 投币
        foreach ($aids as $aid) {
            $res[] = self::reward($aid);
        }
        return array('code' => 1, 'message' => "主站任务：每日投币，当前硬币库存 $stock_num 预计投币 $estimate_num 实际投币 $actual_num");
    }

    /**
     * @use 首页推荐
     * @param int $num
     * @param int $ps
     * @return array
     */
    private function getTopRCmdAids(int $num, int $ps = 30): array
    {
        // 动画1 国创168 音乐3 舞蹈129 游戏4 知识36 科技188 汽车223 生活160 美食211 动物圈127 鬼畜119 时尚155 资讯202 娱乐5 影视181
        $rids = [1, 168, 3, 129, 4, 36, 188, 223, 160, 211, 127, 119, 155, 202, 5, 181];
        $aids = [];
        $url = 'https://api.bilibili.com/x/web-interface/dynamic/region';
        $payload = [
            'ps' => $ps,
            'rid' => $rids[array_rand($rids)],
        ];
        $raw = Curl::get('other', $url, $payload, $this->cookie);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['code'] == 0) {
            if ($num == 1) {
                $temps = [array_rand($de_raw['data']['archives'], $num)];
            } else {
                $temps = array_rand($de_raw['data']['archives'], $num);
            }
            foreach ($temps as $temp) {
                array_push($aids, $de_raw['data']['archives'][$temp]['aid']);
            }
            return $aids;
        }
        return self::getDayRankingAids($num);
    }

    /**
     * @use 获取榜单稿件列表
     * @param int $num
     * @return array
     */
    private function getDayRankingAids(int $num): array
    {
        // day: 日榜1 三榜3 周榜7 月榜30
        $aids = [];
        $rand_nums = [];
        $url = "https://api.bilibili.com/x/web-interface/ranking";
        $payload = [
            'rid' => 0,
            'day' => 1,
            'type' => 1,
            'arc_type' => 0
        ];
        $raw = Curl::get('other', $url, $payload, $this->cookie);
        $de_raw = json_decode($raw['body'], true);
        for ($i = 0; $i < $num; $i++) {
            while (true) {
                $rand_num = mt_rand(1, 99);
                if (in_array($rand_num, $rand_nums)) {
                    continue;
                } else {
                    array_push($rand_nums, $rand_num);
                    break;
                }
            }
            $aid = $de_raw['data']['list'][$rand_nums[$i]]['aid'];
            array_push($aids, $aid);
        }

        return $aids;
    }

    /**
     * @use 获取关注UP稿件列表
     * @param int $num
     * @return array
     */
    private function getFollowUpAids(int $num)
    {
        $aids = [];
        $url = 'https://api.vc.bilibili.com/dynamic_svr/v1/dynamic_svr/dynamic_new';
        $payload = [
            'uid' => $this->mid,
            'type_list' => '8,512,4097,4098,4099,4100,4101'
        ];
        $headers = [
            'origin' => 'https://t.bilibili.com',
            'referer' => 'https://t.bilibili.com/pages/nav/index_new'
        ];
        $raw = Curl::get('pc', $url, $payload, $this->cookie, $headers);
        $de_raw = json_decode($raw['body'], true);
        foreach ($de_raw['data']['cards'] as $index => $card) {
            if ($index >= $num) {
                break;
            }
            array_push($aids, $card['desc']['rid']);
        }
        // 此处补全缺失
        if (count($aids) < $num) {
            $aids = array_merge($aids, self::getTopRCmdAids($num - count($aids)));
        }
        return $aids;
    }

    /**
     * @use 投币
     * @param $aid
     * @return array
     */
    private function reward($aid): array
    {
        $url = "https://api.bilibili.com/x/web-interface/coin/add";
        $payload = [
            "aid" => $aid,
            "multiply" => "1",
            "csrf" => $this->csrf,
        ];
        $headers = [
            'Host' => "api.bilibili.com",
            'Origin' => "https://www.bilibili.com",
            'Referer' => "https://www.bilibili.com/video/av$aid",
            'User-Agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.81 Safari/537.36",
        ];
        // {"code":34005,"message":"超过投币上限啦~","ttl":1,"data":{"like":false}}
        // {"code":0,"message":"0","ttl":1,"data":{"like":false}}
        // CODE -> 137001 MSG -> 账号封禁中，无法完成操作
        // CODE -> -650 MSG -> 用户等级太低
        $raw = Curl::post('app', $url, $payload, $this->cookie, $headers);
        $de_raw = json_decode($raw['body'], true);
        if ($de_raw['code'] == 0) {
            return array('code' => 1, 'message' => '主站任务：av' . $aid . '投币成功');
        } else {
            return array('code' => 0, 'message' => '主站任务：av' . $aid . '投币失败');
        }
    }

    /**
     * @use 投币日志
     * @return int
     */
    protected function coinLog(): int
    {
        $url = "https://api.bilibili.com/x/member/web/coin/log";
        $payload = [];
        $raw = Curl::get('pc', $url, $payload, $this->cookie);
        $de_raw = json_decode($raw['body'], true);

        $logs = $de_raw['data']['list'] ?? [];
        $coins = 0;
        foreach ($logs as $log) {
            $log_ux = strtotime($log['time']);
            $log_date = date('Y-m-d', $log_ux);
            $now_date = date('Y-m-d');
            if ($log_date != $now_date) {
                break;
            }
            if (str_contains($log['reason'], "打赏")) {
                switch ($log['delta']) {
                    case -1:
                        $coins += 1;
                        break;
                    case -2:
                        $coins += 2;
                        break;
                    default:
                        break;
                }
            }
        }
        return $coins;
    }

    /**
     * @use 获取硬币数量
     * @return int
     */
    private function getCoin()
    {
        $url = 'https://account.bilibili.com/site/getCoin';
        $payload = [];
        $headers = [
            'referer' => 'https://account.bilibili.com/account/coin',
        ];
        $raw = Curl::get('pc', $url, $payload, $this->cookie, $headers);
        $de_raw = json_decode($raw['body'], true);
        // {"code":0,"status":true,"data":{"money":1707.9}}
        if ($de_raw['code'] == 0 && isset($de_raw['data']['money'])) {
            return floor($de_raw['data']['money']);
        }
        return 0;
    }

    /**
     * @use 解析AID到CID
     * @return array
     */
    private function parseAid()
    {
        while (true) {
            $aid = self::getRandomAid();
            $url = "https://api.bilibili.com/x/web-interface/view?aid=" . $aid;
            $payload = [
                'aid' => $aid
            ];
            $raw = Curl::get('other', $url, $payload, $this->cookie);
            $de_raw = json_decode($raw['body'], true);
            if ($de_raw['code'] != 0) {
                continue;
            } else {
                if (!array_key_exists('cid', $de_raw['data'])) {
                    continue;
                }
            }
            $cid = $de_raw['data']['cid'];
            $duration = $de_raw['data']['duration'];
            return [
                'aid' => $aid,
                'cid' => $cid,
                'duration' => $duration
            ];
        }
    }

    /**
     * @use 获取随机AID
     * @return string
     */
    private function getRandomAid()
    {
        do {
            $url = "https://api.bilibili.com/x/web-interface/newlist";
            $payload = [
                'pn' => mt_rand(1, 1000),
                'ps' => 1,
            ];
            $raw = Curl::get('other', $url, $payload);
            $de_raw = json_decode($raw['body'], true);
            // echo "getRandomAid " . count($de_raw['data']['archives']) . PHP_EOL;
            // $aid = array_rand($de_raw['data']['archives'])['aid'];
        } while (count((array)$de_raw['data']['archives']) == 0);
        $aid = $de_raw['data']['archives'][0]['aid'];
        return (string)$aid;
    }

    /**
     * @use 漫画签到
     * @return array
     */
    public function manga_sign()
    {
        sleep(1);
        $url = 'https://manga.bilibili.com/twirp/activity.v1.Activity/ClockIn';
        $payload = [
            'access_key' => self::$access_key,
            'ts' => time()
        ];
        $raw = Curl::post('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);
        # {"code":0,"msg":"","data":{}}
        # {"code":"invalid_argument","msg":"clockin clockin is duplicate","meta":{"argument":"clockin"}}
        if (!$de_raw['code']) {
            return array('code' => 1, 'message' => '漫画签到: 成功');
        } else {
            return array('code' => 0, 'message' => '漫画签到: 失败或者重复操作');
        }
    }


    /**
     * @use 漫画分享
     * @return array
     */
    public function manga_share()
    {
        sleep(1);
        $payload = [];
        $url = "https://manga.bilibili.com/twirp/activity.v1.Activity/ShareComic";
        $raw = Curl::post('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);
        # {"code":0,"msg":"","data":{"point":5}}
        # {"code":1,"msg":"","data":{"point":0}}
        if (!$de_raw['code']) {
            return array('code' => 1, 'message' => '漫画分享: 成功');
        } else {
            return array('code' => 0, 'message' => '漫画分享: 失败或者重复操作');
        }
    }

    /**
     * @use 领取每日包裹PC
     */
    public function dailyBagPC()
    {
        sleep(1);
        $url = 'https://api.live.bilibili.com/gift/v2/live/receive_daily_bag';
        $payload = [];
        $data = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $data = json_decode($data['body'], true);
        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '[PC] 日常/周常礼包领取失败' . $data['message']);
        } else {
            return array('code' => 1, 'message' => '[PC] 日常/周常礼包领取成功');
        }
    }

    /**
     * @use 领取每日包裹APP
     */
    public function dailyBagAPP()
    {
        sleep(1);
        $url = 'https://api.live.bilibili.com/AppBag/sendDaily';
        $payload = [];
        $data = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $data = json_decode($data['body'], true);
        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '[APP] 日常/周常礼包领取失败' . $data['message']);
        } else {
            return array('code' => 1, 'message' => '[APP] 日常/周常礼包领取成功');
        }
    }

    /**
     * @use Web 心跳
     */
    public function webHeart()
    {
        $url = 'https://api.live.bilibili.com/User/userOnlineHeart';
        $payload = [
            'csrf' => $this->csrf,
            'csrf_token' => $this->csrf,
            'room_id' => $this->config['global_room'] ?? 1, // 直播间ID，全局房间，用于礼物赠送、心跳等等 需用户配置
            '_' => time() * 1000,
        ];
        $headers = [
            'Referer' => 'https://live.bilibili.com/' . $payload['room_id'],
        ];
        $data = Curl::post('app', $url, $payload, $this->cookie, $headers);
        $data = json_decode($data['body'], true);

        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '[PC] 发送在线心跳失败' . $data['message']);
        } else {
            return array('code' => 1, 'message' => '[PC] 发送在线心跳成功');
        }
    }

    /**
     * @use 手机端心跳
     */
    public function appHeart()
    {
        $url = 'https://api.live.bilibili.com/mobile/userOnlineHeart';
        $payload = [
            'room_id' => $this->config['global_room'] ?? 1,
        ];
        $data = Curl::post('app', $url, Sign::common($payload), $this->cookie);
        $data = json_decode($data['body'], true);
        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '[APP] 发送在线心跳失败' . $data['message']);
        } else {
            return array('code' => 1, 'message' => '[APP] 发送在线心跳成功');
        }
    }

    /**
     * @use 获取友爱社列表
     * @return array
     */
    public function getGroupList(): array
    {
        $url = 'https://api.vc.bilibili.com/link_group/v1/member/my_groups';
        $payload = [];
        $raw = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);

        if (empty($de_raw['data']['list'])) {
            return array('code' => 0, 'message' => '你没有需要签到的应援团!');
        }
        return $de_raw['data']['list'];
    }

    /**
     * @use 签到
     * @param array $groupInfo
     * @return array
     */
    public function signInGroup(array $groupInfo): array
    {
        $url = 'https://api.vc.bilibili.com/link_setting/v1/link_setting/sign_in';
        $payload = [
            'group_id' => $groupInfo['group_id'],
            'owner_id' => $groupInfo['owner_uid'],
        ];
        $raw = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);

        if ($de_raw['code'] != '0') {
            // Todo 任务失败原因
            // {"code": 710001, "msg": "应援失败>_<", "message": "应援失败>_<", "ttl": "1", "data": {"add_num": 0, "status": 0}}
            if ($de_raw['code'] == '710001') {
                return array('code' => 0, 'message' => '在应援团' . $groupInfo['group_name'] . '中签到失败, 亲密度已达上限');
            } else {
//                print_r($de_raw);
                return array('code' => 0, 'message' => '在应援团' . $groupInfo['group_name'] . '中签到失败, 原因待查');
            }
        }
        if ($de_raw['data']['status'] == '0') {
            return array('code' => 1, 'message' => '在应援团' . $groupInfo['group_name'] . '中签到成功, 增加' . $de_raw['data']['add_num'] . '点亲密度');
        } else {
            return array('code' => 0, 'message' => '在应援团' . $groupInfo['group_name'] . '中不要重复签到');
        }
    }

    /**
     * @use 礼物心跳
     * @return array
     */
    public function gift_heart(): array // 直播间ID，全局房间，用于礼物赠送、心跳等等 需用户配置
    {
        $url = 'https://api.live.bilibili.com/gift/v2/live/heart_gift_receive';
        $payload = [
            'roomid' => $this->config['global_room'] ?? 1,
        ];
        $raw = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);

        // {"code":400,"msg":"访问被拒绝","message":"访问被拒绝","data":[]}
        if (isset($de_raw['msg']) && $de_raw['code'] == 400 && $de_raw['msg'] == '访问被拒绝') {
            //暂停任务
            return array('code' => 0, 'message' => '风控?');
        }

        if ($de_raw['code'] == -403) {
            echo($de_raw['msg']);
            $payload = [
                'ruid' => 17561885,
            ];
            $url = 'https://api.live.bilibili.com/eventRoom/index';
            Curl::get('app', $url, Sign::common($payload), $this->cookie);
            return array('code' => 1, 'message' => '领取心跳礼物成功?');
        }

        if ($de_raw['code'] != 0) {
            return array('code' => -1, 'message' => $de_raw['msg']);
        }

        if ($de_raw['data']['heart_status'] == 0) {
            return array('code' => 1, 'message' => '没有礼物可以领了呢!');
        }

        if (isset($de_raw['data']['gift_list'])) {
            foreach ($de_raw['data']['gift_list'] as $vo) {
                return array('code' => 0, 'message' => "{$de_raw['msg']}，礼物 {$vo['gift_name']} ({$vo['day_num']}/{$vo['day_limit']})");
            }
        }
        return array('code' => 0, 'message' => '失败或者重复操作');
    }

    /**
     * @use 检查每日任务
     * @return array
     */
    public function check_daily(): array
    {
        $url = 'https://api.live.bilibili.com/i/api/taskInfo';
        $payload = [];
        $data = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $data = json_decode($data['body'], true);
        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '每日任务检查失败!' . $data['message']);
        }
        return $data;
    }

    /**
     * @use 每日签到
     * @param $info
     */
    public function sign_info($info)
    {
        if ($info['status'] == 1) {
            return array('code' => 0, 'message' => '该任务已完成');
        }
        $url = 'https://api.live.bilibili.com/sign/doSign';
        $payload = [];
        $data = Curl::get('app', $url, Sign::common($payload), $this->cookie);
        $data = json_decode($data['body'], true);
        // 您被封禁了,无法进行操作
        // {"code":1011040,"message":"今日已签到过,无法重复签到","ttl":1,"data":null}
        // {"code":0,"message":"0","ttl":1,"data":{"text":"3000点用户经验,2根辣条","specialText":"再签到3天可以获得666银瓜子","allDays":31,"hadSignDays":2,"isBonusDay":0}}
        // {"code":0,"message":"0","ttl":1,"data":{"text":"3000点用户经验,2根辣条,50根辣条","specialText":"","allDays":31,"hadSignDays":20,"isBonusDay":1}}
        if (isset($data['code']) && $data['code']) {
            return array('code' => 0, 'message' => '签到失败:' . $data['message']);
        } else {
            return array('code' => 1, 'message' => '签到成功:' . $data['data']['text']);
            // 推送签到信息 $data['data']['text']
        }
    }

    /**
     * @use 双端任务  疑似废弃
     * @param $info
     */
    private static function double_watch_info($info)
    {
        Log::info('检查任务「双端观看直播」...');

        if ($info['status'] == 2) {
            Log::notice('已经领取奖励');
            return;
        }
        if ($info['mobile_watch'] != 1 || $info['web_watch'] != 1) {
            Log::notice('任务未完成，请等待');
            return;
        }
        $url = 'https://api.live.bilibili.com/activity/v1/task/receive_award';
        $payload = [
            'task_id' => 'double_watch_task',
        ];
        $data = Curl::post('app', $url, Sign::common($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning("「双端观看直播」任务奖励领取失败，{$data['message']}!");
        } else {
            Log::info('奖励领取成功!');
            foreach ($info['awards'] as $vo) {
                Log::notice(sprintf("获得 %s × %d", $vo['name'], $vo['num']));
            }
        }
    }

    /**
     * @use app兑换
     * @return array
     */
    public function appSilver2coin(): array
    {
        usleep(0.5 * 1000000);
        $url = 'https://api.live.bilibili.com/AppExchange/silver2coin';
        $payload = [];
        $raw = Curl::post('app', $url, Sign::common($payload), $this->cookie);
        $de_raw = json_decode($raw['body'], true);
        switch ($de_raw['code']) {
            case 0:
                return array('code' => 1, 'message' => '[APP] 银瓜子兑换硬币:' . $de_raw['message']);
                break;
            case 403:
                return array('code' => 0, 'message' => '[APP] 银瓜子兑换硬币:' . $de_raw['message']);
                break;
            default:
                return array('code' => 0, 'message' => '[APP] 银瓜子兑换硬币失败:' . $de_raw['code'] . $de_raw['message']);
                break;
        }

    }

    /**
     * @use pc兑换
     * @return array
     */
    public function pcSilver2coin(): array
    {
        usleep(0.5 * 1000000);
        $payload = [
            'csrf_token' => $this->csrf,
            'csrf' => $this->csrf,
            'visit_id' => ''
        ];
        // $url = "https://api.live.bilibili.com/exchange/silver2coin";
        // $url = "https://api.live.bilibili.com/pay/v1/Exchange/silver2coin";
        $url = "https://api.live.bilibili.com/xlive/revenue/v1/wallet/silver2coin";
        $raw = Curl::post('pc', $url, $payload, $this->cookie);
        $de_raw = json_decode($raw['body'], true);

        switch ($de_raw['code']) {
            case 0:
                return array('code' => 1, 'message' => '[PC] 银瓜子兑换硬币:' . $de_raw['message']);
                break;
            case 403:
                return array('code' => 0, 'message' => '[PC] 银瓜子兑换硬币:' . $de_raw['message']);
                break;
            default:
                return array('code' => 0, 'message' => '[PC] 银瓜子兑换硬币失败:' . $de_raw['code'] . $de_raw['message']);
                break;
        }
    }

    protected function curl($url, $data = null, $cookie = null, $httpheader = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $httpheader[] = "Accept: application/json";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: keep-alive";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        if ($data) {
            if (is_array($data)) $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        if($cookie){
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $ret = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($ret, 0, $headerSize);
        $body = substr($ret, $headerSize);
        $ret = array();
        $ret['header'] = $header;
        $ret['body'] = $body;
        curl_close($curl);
        return $ret;
    }

}