<?php

//decode by http://www.yunlu99.com/
namespace app\index\controller;

use app\index\model\Accounts;
use app\index\model\Jobs;
use app\index\model\Kms;
use app\index\model\Pays;
use app\index\model\TaskLogs;
use app\index\model\Tasks;
use app\index\model\Users;
use app\index\validate\Sport;
use bilibili\Bilibili;
use bilibili\BiliHelper;
use iqiyi\Iqiyi;
use netease\Netease;
use netease\Qrcode;
use sport\Step;
use think\exception\ValidateException;
use think\facade\Request;
use think\facade\Session;
class Ajax extends Common
{
	protected $middleware = ["app\\middleware\\CheckLoginUser", "app\\middleware\\CheckAjaxRequest"];
	public function __construct()
	{
		parent::SystemCheck("goooooooooooooooooooooooooooooooooooooood");
		parent::SystemCheck5("wcnmdb");
	}
	public function netease($act = null)
	{
		switch ($act) {
			case "add":
				$_var_0 = Request::post();
				$_var_1 = new Netease();
				if ($_var_0["username"] && $_var_0["password"]) {
					$_var_2 = $_var_1->login($_var_0["username"], md5($_var_0["password"]));
					if ($_var_2["code"] == 200) {
						$_var_0 = array_merge($_var_2["data"]);
						if (Accounts::where("user_id", "=", $_var_0["user_id"])->where("uid", "<>", Session::get("user.uid"))->find()) {
							return resultJson(-1, "系统已存在该账号，无法继续添加");
						} else {
							return Accounts::add("netease", $_var_0["user_id"], $_var_0);
						}
					} else {
						return resultJson(0, $_var_2["message"]);
					}
				} else {
					return resultJson(0, "参数错误");
				}
				break;
			case "getQrimg":
				$_var_1 = new Netease();
				$_var_3 = new Qrcode();
				$_var_4 = $_var_1->get_qr_key();
				$_var_3->png("http://music.163.com/login?codekey=" . $_var_4, false, QR_ECLEVEL_L, 8, 4);
				$_var_5 = base64_encode(ob_get_contents());
				ob_end_clean();
				return resultJson(1, "获取二维码成功", ["key" => $_var_4, "qrimg" => $_var_5]);
				break;
			case "qrLogin":
				$_var_1 = new Netease();
				$_var_0 = Request::post();
				$_var_6 = $_var_1->qrLogin($_var_0["key"]);
				if ($_var_6["code"] == 200) {
					$_var_0 = array_merge($_var_6["data"]);
					if (Accounts::where("user_id", "=", $_var_0["user_id"])->where("uid", "<>", Session::get("user.uid"))->find()) {
						return resultJson(-1, "系统已存在该账号，无法继续添加");
					} else {
						return Accounts::add("netease", $_var_0["user_id"], $_var_0);
					}
				} else {
					return resultJson($_var_6["code"], $_var_6["message"]);
				}
				break;
			case "delete":
				$_var_7 = Request::post("user_id");
				if (Accounts::delByUserId($_var_7) && Jobs::delJob("netease", $_var_7) && TaskLogs::deleteLogs("netease", $_var_7)) {
					return resultJson(1, "删除成功");
				} else {
					return resultJson(0, "删除失败");
				}
				break;
			case "set":
				$act = Request::post("act");
				switch ($act) {
					case "zt":
						$_var_0 = Request::post();
						Jobs::refreshJob("netease", $_var_0["user_id"]);
						if (Tasks::checkTaskPower($_var_0["do"]) && empty(Session::get("user.vip_start"))) {
							return resultJson(-1, "您需要开通VIP会员才可以使用该功能");
						}
						if (Jobs::switchState($_var_0["user_id"], $_var_0["do"])) {
							return resultJson(1, "修改成功");
						} else {
							return resultJson(0, "修改失败");
						}
						break;
					default:
						$_var_0 = Request::post();
						$_var_8 = serialize(json_decode($_var_0["config"], true)) ?? [];
						if (Jobs::where("type", "=", "netease")->where("user_id", "=", $_var_0["user_id"])->where("do", "=", $_var_0["do"])->update(["data" => $_var_8]) !== false) {
							return resultJson(1, "保存成功");
						} else {
							return resultJson(0, "保存失败");
						}
						break;
				}
				break;
			case "logs":
				$_var_7 = Request::post("user_id");
				return TaskLogs::searchLogs("netease", $_var_7);
				break;
			case "reExecute":
				$_var_7 = Request::post("user_id");
				$_var_9 = Accounts::where("user_id", "=", $_var_7)->find();
				if (Session::get("user.uid") == $_var_9["uid"]) {
					$_var_10 = Jobs::where("user_id", "=", $_var_7)->where("uid", "=", Session::get("user.uid"))->where("state", "=", 1);
					if (count($_var_10->select()) == 0) {
						return resultJson(1, "没有需要补挂的任务");
					}
					$_var_10->update(["nextExecute" => time()]);
					return resultJson(1, "申请补挂成功，请稍后查看任务运行情况");
				} else {
					return resultJson(0, "非法操作");
				}
				break;
		}
	}
	public function bilibili($act = null)
	{
		switch ($act) {
			case "geetest_captcha":
				$_var_11 = new BiliHelper();
				$_var_12 = $_var_11->geetest();
				if ($_var_12["code"] == 1) {
					return resultJson(1, "获取成功", $_var_12["data"]);
				}
				break;
			case "login":
				$_var_11 = new Bilibili();
				$_var_13 = Request::post();
				$_var_14 = $_var_11->login($_var_13);
				if ($_var_14["code"] == 1) {
					preg_match("/DedeUserID=(.*?);/", $_var_14["data"]["cookie"], $_var_15);
					preg_match("/DedeUserID__ckMd5=(.*?);/", $_var_14["data"]["cookie"], $_var_16);
					preg_match("/SESSDATA=(.*?);/", $_var_14["data"]["cookie"], $_var_17);
					preg_match("/bili_jct=(.*?);/", $_var_14["data"]["cookie"], $_var_18);
					$_var_11 = new Bilibili($_var_15[1], $_var_16[1], $_var_17[1], $_var_18[1]);
					$_var_19 = $_var_11->login_info();
					$_var_20 = json_decode($_var_19["body"], true);
					$_var_13 = ["nickname" => $_var_20["data"]["uname"], "avatar" => $_var_20["data"]["face"], "mid" => $_var_15[1], "mid_md5" => $_var_16[1], "token" => $_var_17[1], "csrf" => $_var_18[1], "access_key" => $_var_14["data"]["access_key"]];
					if (Accounts::where("user_id", "=", $_var_13["mid"])->where("uid", "<>", Session::get("user.uid"))->find()) {
						return resultJson(-1, "系统已存在该账号，无法继续添加");
					} else {
						return Accounts::add("bilibili", $_var_13["mid"], $_var_13);
					}
				} else {
					return resultJson(0, $_var_14["message"]);
				}
				break;
			case "getQrimg":
				$_var_11 = new Bilibili();
				$_var_21 = new Qrcode();
				$_var_22 = $_var_11->getQrimg();
				$_var_21->png($_var_22["url"], false, QR_ECLEVEL_L, 8, 4);
				$_var_23 = base64_encode(ob_get_contents());
				ob_end_clean();
				return resultJson(1, "获取二维码成功", ["oauthKey" => $_var_22["oauthKey"], "qrimg" => $_var_23]);
				break;
			case "qrLogin":
				$_var_13 = Request::post();
				$_var_11 = new Bilibili();
				$_var_24 = $_var_11->qrLogin($_var_13["oauthKey"]);
				if ($_var_24["code"] == 1) {
					preg_match("/DedeUserID=(.*?);/", $_var_24["data"]["cookie"], $_var_15);
					preg_match("/DedeUserID__ckMd5=(.*?);/", $_var_24["data"]["cookie"], $_var_16);
					preg_match("/SESSDATA=(.*?);/", $_var_24["data"]["cookie"], $_var_17);
					preg_match("/bili_jct=(.*?);/", $_var_24["data"]["cookie"], $_var_18);
					$_var_11 = new Bilibili($_var_15[1], $_var_16[1], $_var_17[1], $_var_18[1]);
					$_var_19 = $_var_11->login_info();
					$_var_20 = json_decode($_var_19["body"], true);
					$_var_13 = ["nickname" => $_var_20["data"]["uname"], "avatar" => $_var_20["data"]["face"], "mid" => $_var_15[1], "mid_md5" => $_var_16[1], "token" => $_var_17[1], "csrf" => $_var_18[1], "access_key" => $_var_24["data"]["access_key"]];
					if (Accounts::where("user_id", "=", $_var_13["mid"])->where("uid", "<>", Session::get("user.uid"))->find()) {
						return resultJson(-1, "系统已存在该账号，无法继续添加");
					} else {
						return Accounts::add("bilibili", $_var_13["mid"], $_var_13);
					}
				} else {
					return resultJson($_var_24["code"], $_var_24["message"]);
				}
				break;
			case "delete":
				$_var_15 = Request::post("mid");
				if (Accounts::delByUserId($_var_15) && Jobs::delJob("bilibili", $_var_15) && TaskLogs::deleteLogs("bilibili", $_var_15)) {
					return resultJson(1, "删除成功");
				} else {
					return resultJson(0, "删除失败");
				}
				break;
			case "set":
				$act = Request::post("act");
				switch ($act) {
					case "zt":
						$_var_13 = Request::post();
						Jobs::refreshJob("netease", $_var_13["user_id"]);
						if (Tasks::checkTaskPower($_var_13["do"]) && empty(Session::get("user.vip_start"))) {
							return resultJson(-1, "您需要开通VIP会员才可以使用该功能");
						}
						if (Jobs::switchState($_var_13["user_id"], $_var_13["do"])) {
							return resultJson(1, "修改成功");
						} else {
							return resultJson(0, "修改失败");
						}
						break;
					default:
						$_var_13 = Request::post();
						$_var_25 = serialize(json_decode($_var_13["config"], true)) ?? [];
						if (Jobs::where("type", "=", "bilibili")->where("user_id", "=", $_var_13["user_id"])->where("do", "=", $_var_13["do"])->update(["data" => $_var_25]) !== false) {
							return resultJson(1, "保存成功");
						} else {
							return resultJson(0, "保存失败");
						}
						break;
				}
				break;
			case "logs":
				$_var_15 = Request::post("user_id");
				return TaskLogs::searchLogs("bilibili", $_var_15);
				break;
			case "reExecute":
				$_var_26 = Request::post("user_id");
				$_var_27 = Accounts::where("user_id", "=", $_var_26)->find();
				if (Session::get("user.uid") == $_var_27["uid"]) {
					$_var_28 = Jobs::where("user_id", "=", $_var_26)->where("uid", "=", Session::get("user.uid"))->where("state", "=", 1);
					if (count($_var_28->select()) == 0) {
						return resultJson(1, "没有需要补挂的任务");
					}
					$_var_28->update(["nextExecute" => time()]);
					return resultJson(1, "申请补挂成功，请稍后查看任务运行情况");
				} else {
					return resultJson(0, "非法操作");
				}
				break;
		}
	}
	public function sport($act = null)
	{
		switch ($act) {
			case "add":
				$_var_29 = new Step();
				$_var_30 = Request::post();
				if (Tasks::checkTaskPower("step") && empty(Session::get("user.vip_start"))) {
					return resultJson(-1, "您需要开通VIP会员才可以使用该功能");
				} else {
					$_var_31 = $_var_29->login($_var_30["username"], $_var_30["password"]);
					$_var_32 = @json_decode($_var_31["body"], true);
					if (@$_var_32["result"] == "ok") {
						$_var_30 = ["user_id" => $_var_32["token_info"]["user_id"], "username" => $_var_30["username"], "password" => $_var_30["password"], "nickname" => $_var_32["thirdparty_info"]["nickname"], "login_token" => $_var_32["token_info"]["login_token"], "app_token" => $_var_32["token_info"]["app_token"]];
						if (Accounts::where("user_id", "=", $_var_30["user_id"])->where("uid", "<>", Session::get("user.uid"))->find()) {
							return resultJson(-1, "系统已存在该账号，无法继续添加");
						} else {
							return Accounts::add("sport", $_var_30["user_id"], $_var_30);
						}
					} else {
						return resultJson(0, "登录失败，请检查账号密码是否正确");
					}
				}
				break;
			case "step":
				$_var_30 = Request::post();
				try {
					validate(Sport::class)->check($_var_30);
				} catch (ValidateException $_var_33) {
					return resultJson(-1, $_var_33->getMessage());
				}
				$_var_34 = Accounts::where("user_id", "=", $_var_30["user_id"])->where("type", "=", "sport")->find();
				$_var_35 = unserialize($_var_34["data"]);
				$_var_36 = ["username" => $_var_35["username"], "password" => $_var_35["password"], "step_start" => (float) $_var_30["step_start"], "step_stop" => (float) $_var_30["step_stop"]];
				$_var_36 = serialize($_var_36);
				if (Jobs::where("type", "=", "sport")->where("user_id", "=", $_var_30["user_id"])->where("uid", "=", Session::get("user.uid"))->update(["data" => $_var_36])) {
					return resultJson(1, "修改成功");
				} else {
					return resultJson(0, "修改失败");
				}
				break;
			case "delete":
				$_var_37 = Request::post("user_id");
				if (Accounts::delByUserId($_var_37) && Jobs::delJob("sport", $_var_37) && TaskLogs::deleteLogs("sport", $_var_37)) {
					return resultJson(1, "删除成功");
				} else {
					return resultJson(0, "删除失败");
				}
				break;
		}
	}
	public function iqiyi($act = null)
	{
		switch ($act) {
			case "getQrimg":
				$_var_38 = new Iqiyi();
				$_var_39 = new Qrcode();
				$_var_40 = $_var_38->getLoginToken();
				$_var_39->png($_var_40["data"]["url"], false, QR_ECLEVEL_L, 8, 4);
				$_var_41 = base64_encode(ob_get_contents());
				ob_end_clean();
				return resultJson(1, "获取二维码成功", ["token" => $_var_40["data"]["token"], "qrimg" => $_var_41]);
				break;
			case "qrLogin":
				$_var_38 = new Iqiyi();
				$_var_42 = Request::post();
				$_var_43 = $_var_38->qrLogin($_var_42["token"]);
				if ($_var_43["code"] == 200) {
					if (Accounts::where("user_id", "=", $_var_43["data"]["uid"])->where("uid", "<>", Session::get("user.uid"))->find()) {
						return resultJson(-1, "系统已存在该账号，无法继续添加");
					} else {
						return Accounts::add("iqiyi", $_var_43["data"]["uid"], $_var_43["data"]);
					}
				} else {
					return resultJson($_var_43["code"], $_var_43["message"]);
				}
				break;
			case "delete":
				$_var_44 = Request::post("user_id");
				if (Accounts::delByUserId($_var_44) && Jobs::delJob("iqiyi", $_var_44) && TaskLogs::deleteLogs("iqiyi", $_var_44)) {
					return resultJson(1, "删除成功");
				} else {
					return resultJson(0, "删除失败");
				}
				break;
			case "set":
				$act = Request::post("act");
				switch ($act) {
					case "zt":
						$_var_42 = Request::post();
						Jobs::refreshJob("iqiyi", $_var_42["user_id"]);
						if (Tasks::checkTaskPower($_var_42["do"]) && empty(Session::get("user.vip_start"))) {
							return resultJson(-1, "您需要开通VIP会员才可以使用该功能");
						}
						if (Jobs::switchState($_var_42["user_id"], $_var_42["do"])) {
							return resultJson(1, "修改成功");
						} else {
							return resultJson(0, "修改失败");
						}
						break;
					default:
						$_var_42 = Request::post();
						$_var_45 = serialize(json_decode($_var_42["config"], true)) ?? [];
						if (Jobs::where("type", "=", "iqiyi")->where("user_id", "=", $_var_42["user_id"])->where("do", "=", $_var_42["do"])->update(["data" => $_var_45]) !== false) {
							return resultJson(1, "保存成功");
						} else {
							return resultJson(0, "保存失败");
						}
						break;
				}
				break;
			case "logs":
				$_var_44 = Request::post("user_id");
				return TaskLogs::searchLogs("iqiyi", $_var_44);
				break;
			case "reExecute":
				$_var_44 = Request::post("user_id");
				$_var_46 = Accounts::where("user_id", "=", $_var_44)->find();
				if (Session::get("user.uid") == $_var_46["uid"]) {
					$_var_47 = Jobs::where("user_id", "=", $_var_44)->where("uid", "=", Session::get("user.uid"))->where("state", "=", 1);
					if (count($_var_47->select()) == 0) {
						return resultJson(1, "没有需要补挂的任务");
					}
					$_var_47->update(["nextExecute" => time()]);
					return resultJson(1, "申请补挂成功，请稍后查看任务运行情况");
				} else {
					return resultJson(0, "非法操作");
				}
		}
	}
	public function user($act = null)
	{
		switch ($act) {
			case "profile":
				$_var_48 = Request::Post();
				if (Users::updateByUid(Session::get("user.uid"), $_var_48)) {
					return resultJson(1, "修改成功");
				} else {
					return resultJson(0, "修改失败，无修改");
				}
				break;
			case "passWord":
				$_var_48 = Request::post();
				$_var_49 = Users::changePassWord(Session::get("user.uid"), $_var_48);
				return $_var_49;
				break;
			case "qqLogin":
				$_var_50 = get_Domain() . "index/oauth/qq_set_callback";
				$_var_51 = "https://qqlogin.qqshabi.cn/Oauth/request.api";
				$_var_52 = md5(uniqid((string) rand(), TRUE));
				cookie("oauth_state", $_var_52);
				$_var_53 = "qqlogin";
				$_var_54 = $_var_51 . "?state=" . $_var_52 . "&type=" . $_var_53 . "&redirect_uri=" . $_var_50;
				return resultJson(1, "获取成功", ["url" => $_var_54]);
				break;
			case "unset_qqLogin":
				if (Users::where("uid", "=", Session::get("user.uid"))->update(["token" => null])) {
					return resultJson(1, "解除绑定成功");
				} else {
					return resultJson(0, "未知错误");
				}
				break;
		}
	}
	public function shop($act = null)
	{
		switch ($act) {
			case "buy":
				$_var_55 = Request::post();
				if ($_var_55["pay_type"] == "ypay" && $_var_55["shop"] == "vip") {
					return Pays::YpayVip($_var_55);
				} elseif ($_var_55["pay_type"] == "ypay" && $_var_55["shop"] == "quota") {
					return Pays::YpayQuota($_var_55);
				} else {
					return Pays::Submit_Pay($_var_55);
				}
				break;
			case "activate":
				$_var_55 = Request::post();
				return Kms::activate($_var_55);
				break;
		}
	}
	public function agent($act = null)
	{
		switch ($act) {
			case "kmList":
				return Kms::getMyList();
				break;
			case "delKm":
				$_var_56 = Request::post("id");
				if (Kms::where("id", "=", $_var_56)->where("uid", "=", Session::get("user.uid"))->delete()) {
					return resultJson(1, "删除成功");
				} else {
					return resultJson(0, "删除失败");
				}
				break;
			case "delUsedKm":
				if (Kms::where("useid", "<>", "0")->where("uid", "=", Session::get("user.uid"))->where("zid", "=", WEB_ID)->delete()) {
					return resultJson(1, "清空成功");
				} else {
					return resultJson(0, "没有可清空的卡密");
				}
				break;
			case "getPrice":
				$_var_57 = Request::post();
				switch ($_var_57["type"]) {
					case "vip":
						$_var_58 = $_var_57["num"] * config("sys.vip_price_" . $_var_57["value"] . "");
						$_var_59 = config("sys.agent_give_z_" . Session::get("user.agent") . "");
						$_var_60 = round($_var_58 * $_var_59 / 10, 2);
						$_var_60 = ["name" => "VIP卡密", "value" => is_Vip_Month($_var_57["value"]) . " 个月", "num" => $_var_57["num"] . " 张", "oprice" => $_var_58 . "元", "zk" => $_var_59 . "折", "price" => $_var_60 . "元"];
						return resultJson(1, "获取成功", $_var_60);
						break;
					case "quota":
						$_var_58 = $_var_57["num"] * config("sys.quota_price_" . $_var_57["value"] . "");
						$_var_59 = config("sys.agent_give_z_" . Session::get("user.agent") . "");
						$_var_60 = round($_var_58 * $_var_59 / 10, 2);
						$_var_60 = ["name" => "配额卡密", "value" => is_Quota_Num($_var_57["value"]) . " 个", "num" => $_var_57["num"] . " 张", "oprice" => $_var_58 . "元", "zk" => $_var_59 . "折", "price" => $_var_60 . "元"];
						return resultJson(1, "获取成功", $_var_60);
						break;
					case "agent":
						$_var_58 = $_var_57["num"] * config("sys.agent_price_" . $_var_57["value"] . "");
						$_var_59 = config("sys.agent_give_z_" . Session::get("user.agent") . "");
						$_var_60 = round($_var_58 * $_var_59 / 10, 2);
						$_var_60 = ["name" => "代理卡密", "value" => is_Agent_Name($_var_57["value"]) . "", "num" => $_var_57["num"] . " 张", "oprice" => $_var_58 . "元", "zk" => $_var_59 . "折", "price" => $_var_60 . "元"];
						return resultJson(1, "获取成功", $_var_60);
						break;
				}
				break;
			case "add":
				$_var_57 = Request::post();
				switch ($_var_57["type"]) {
					case "vip":
					case "quota":
					case "agent":
						try {
							validate(\app\index\validate\Kms::class)->scene("add")->check($_var_57);
						} catch (ValidateException $_var_61) {
							return resultJson(-1, $_var_61->getMessage());
						}
						return Kms::agent_add($_var_57);
						break;
				}
				break;
		}
	}
	public function clearCache()
	{
		if (opcache_reset()) {
			return resultJson(1, "清理缓存成功");
		}
	}
}