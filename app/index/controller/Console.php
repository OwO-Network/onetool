<?php

//decode by http://www.yunlu99.com/
namespace app\index\controller;

use app\index\model\Accounts;
use app\index\model\Info;
use app\index\model\Jobs;
use app\index\model\Kms;
use app\index\model\Notice;
use app\index\model\Tasks;
use app\index\model\Users;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
class Console extends Common
{
	protected $middleware = ["app\\middleware\\CheckLoginUser"];
	public function __construct()
	{
		parent::SystemCheck("goooooooooooooooooooooooooooooooooooooood");
		parent::SystemCheck5("wcnmdb");
	}
	public function index()
	{
		View::assign(["webTitle" => "控制中心", "notice" => Notice::getNoticeList(), "user_count" => Users::userCount(), "task_count" => Tasks::taskCount(), "job_count" => Jobs::jobCount(), "execute_count" => Info::executeCount(), "quota_used" => Accounts::getMyAccountNum(), "agent" => is_Agent_Name(session("user.agent"))]);
		return View::fetch("/console/index");
	}
	public function netease($act = null)
	{
		switch ($act) {
			case "add":
				View::assign("webTitle", "添加账号");
				return View::fetch("/console/netease/add");
				break;
			case "info":
				$_var_0 = Request::param("user_id");
				$_var_1 = Accounts::findByUserId($_var_0);
				if (!$_var_1 || $_var_1["uid"] != Session::get("user.uid")) {
					View::assign(["msg" => "非法请求", "url" => "/index/console"]);
					exit(View::fetch("common/alert"));
				}
				$_var_2 = Jobs::findByUserId("netease", $_var_0);
				View::assign(["webTitle" => "管理账号", "data" => $_var_1, "job" => $_var_2]);
				return View::fetch("/console/netease/info");
				break;
			default:
				$_var_3 = Accounts::getMyList("netease");
				View::assign(["webTitle" => "账号列表", "list" => $_var_3]);
				return View::fetch("/console/netease/list");
				break;
		}
	}
	public function bilibili($act = null)
	{
		switch ($act) {
			case "add":
				View::assign("webTitle", "添加账号");
				return View::fetch("/console/bilibili/add");
				break;
			case "info":
				$_var_4 = Request::param("mid");
				$_var_5 = Accounts::findByUserId($_var_4);
				if (!$_var_5 || $_var_5["uid"] != Session::get("user.uid")) {
					View::assign(["msg" => "非法请求", "url" => "/index/console"]);
					exit(View::fetch("common/alert"));
				}
				$_var_6 = Jobs::findByUserId("bilibili", $_var_4);
				View::assign(["webTitle" => "管理账号", "data" => $_var_5, "job" => $_var_6]);
				return View::fetch("/console/bilibili/info");
				break;
			default:
				$_var_7 = Accounts::getMyList("bilibili");
				View::assign(["webTitle" => "账号列表", "list" => $_var_7]);
				return View::fetch("/console/bilibili/list");
				break;
		}
	}
	public function sport($act)
	{
		switch ($act) {
			case "add":
				View::assign("webTitle", "添加账号");
				return View::fetch("/console/sport/add");
				break;
			default:
				$_var_8 = Accounts::getMyList("sport");
				View::assign(["webTitle" => "账号列表", "list" => $_var_8]);
				return View::fetch("/console/sport/list");
				break;
		}
	}
	public function iqiyi($act)
	{
		switch ($act) {
			case "add":
				View::assign("webTitle", "添加账号");
				return View::fetch("/console/iqiyi/add");
				break;
			case "info":
				$_var_9 = Request::param("uid");
				$_var_10 = Accounts::findByUserId($_var_9);
				if (!$_var_10 || $_var_10["uid"] != Session::get("user.uid")) {
					View::assign(["msg" => "非法请求", "url" => "/index/console"]);
					exit(View::fetch("common/alert"));
				}
				$_var_11 = Jobs::findByUserId("iqiyi", $_var_9);
				View::assign(["webTitle" => "管理账号", "data" => $_var_10, "job" => $_var_11]);
				return View::fetch("/console/iqiyi/info");
				break;
			default:
				$_var_12 = Accounts::getMyList("iqiyi");
				View::assign(["webTitle" => "账号列表", "list" => $_var_12]);
				return View::fetch("/console/iqiyi/list");
				break;
		}
	}
	public function shop($act = null)
	{
		switch ($act) {
			case "quota":
				View::assign("webTitle", "购买配额");
				return View::fetch("/console/shop/quota");
				break;
			case "vip":
				View::assign("webTitle", "购买会员");
				return View::fetch("/console/shop/vip");
				break;
			case "agent":
				View::assign("webTitle", "开通代理");
				return View::fetch("/console/shop/agent");
				break;
			case "money":
				View::assign("webTitle", "余额充值");
				return View::fetch("/console/shop/money");
				break;
			case "site":
				if (config("sys.is_site") != 1) {
					View::assign(["msg" => "非法请求", "url" => "/index/console"]);
					exit(View::fetch("common/alert"));
				}
				$_var_13 = explode(",", config("sys.site_url") ?? "");
				View::assign(["webTitle" => "开通分站", "site_url" => $_var_13]);
				return View::fetch("/console/shop/site");
				break;
		}
	}
	public function user($act = null)
	{
		switch ($act) {
			case "profile":
				View::assign("webTitle", "个人资料");
				return View::fetch("/console/user/profile");
				break;
		}
	}
	public function agent($act = null)
	{
		switch ($act) {
			default:
				View::assign(["webTitle" => "代理中心", "all" => Kms::getMyList(), "used" => Kms::getMyList(null, "used")]);
				return View::fetch("/console/agent/index");
				break;
		}
	}
}