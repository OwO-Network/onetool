<?php

namespace bilibili;

error_reporting(0);

class BiliHelper extends Bilibili
{
    /*
     * 初始化
     */
    public function __construct($mid = null, $mid_md5 = null, $token = null, $csrf = null, $access_key = null, $config = [])
    {
        parent::__construct($mid, $mid_md5, $token, $csrf, $access_key, $config);
    }

    /*
     * 无实际功能 配置作用
     */
    public function globalroom()
    {
        return array('code' => 1);
    }

    /*
     * 获取极验参数
     */
    public function geetest()
    {
        return parent::geetest();
    }

    /*
     * 账密登录
     */
    public function login($data = [])
    {
        return parent::login($data);
    }

    /*
     * 获取个人信息
     */
    public function login_info()
    {
        return parent::login_info();
    }

    /*
     * 观看视频
     */
    public function watchAid()
    {
        return parent::watchAid();
    }

    /*
     * 分享视频
     */
    public function shareAid()
    {
        return parent::shareAid();
    }

    /*
     * 投币视频
     */
    public function coinAdd()
    {
        $res = parent::coinAdd();
        return $res;
    }

    /*
     * 漫画签到、分享
     */
    public function manga()
    {
        $res[] = parent::manga_sign();
        $res[] = parent::manga_share();
        $res = $res[0]['message'] . ' ' . $res[1]['message'];
        return array('code' => 1, 'message' => $res);
    }

    /*
     * 双端领取日常/周常礼包
     */
    public function dailybag()
    {
        $res[] = parent::dailyBagAPP();
        $res[] = parent::dailyBagPC();
        $res = $res[0]['message'] . ' ' . $res[1]['message'];
        return array('code' => 1, 'message' => $res);
    }

    /*
     * 双端心跳 (姥爷直播经验)
     */
    public function doubleheart()
    {
        $res[] = parent::webHeart();
        $res[] = parent::appHeart();
        $res = $res[0]['message'] . ' ' . $res[1]['message'];
        return array('code' => 1, 'message' => $res);
    }

    /*
     * 友爱社签到
     */
    public function groupsignIn()
    {
        $groups = parent::getGroupList();
        if ($groups['code'] == 0) {
            return $groups;
        }
        foreach ($groups as $group) {
            $res = parent::signInGroup($group);
        }
        return array('code' => 1, 'message' => $res['message']);
    }

    /*
     * 日常心跳每日礼包礼物
     */
    public function giftheart()
    {
        return parent::gift_heart();
    }

    /*
     * 直播每日任务(签到、观看)
     */
    public function dailytask()
    {
        $data = parent::check_daily();
        if (isset($data['data']['sign_info'])) {
            return parent::sign_info($data['data']['sign_info']);
        } else {
            return array('code' => 0, 'message' => '每日任务检查失败!' . $data['message']);
        }
    }

    /*
     * 银瓜子兑换硬币
     */
    public function Silver2Coin()
    {
        $res[] = parent::pcSilver2coin();
        $res[] = parent::appSilver2coin();
        $res = $res[0]['message'] . ' ' . $res[1]['message'];
        return array('code' => 1, 'message' => $res);
    }


}