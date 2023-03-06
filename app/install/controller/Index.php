<?php
/*
 * @Author: Vincent Young
 * @Date: 2022-03-21 13:14:44
 * @LastEditors: Vincent Young
 * @LastEditTime: 2023-03-06 19:06:38
 * @FilePath: /onetool/app/install/controller/Index.php
 * @Telegram: https://t.me/missuo
 * 
 * Copyright © 2023 by Vincent, All Rights Reserved. 
 */

//decode by http://www.yunlu99.com/
namespace app\install\controller;

use app\install\validate\Install;
use think\exception\ValidateException;
use think\facade\Request;
use think\facade\View;
class Index
{
	public function __construct()
	{
		$_var_0 = root_path() . "config" . DIRECTORY_SEPARATOR . "Db.php";
		if (file_exists($_var_0)) {
			exit("你已经成功安装，如需重新安装，请手动删除config目录下Db.php配置文件！");
		}
	}
	public function checkfun()
	{
		if (version_compare(PHP_VERSION, "8.0.0", "<")) {
			return false;
		}
		if (!function_exists("curl_exec")) {
			return false;
		}
		if (!function_exists("file_get_contents")) {
			return false;
		}
		return true;
	}
	public function index()
	{
		if (!$this->checkfun()) {
			return View::fetch("status");
		} else {
			return View::fetch("install");
		}
	}
	public function install()
	{
		$_var_1 = Request::post();
		if (!Request::isAjax()) {
			exit("非法请求");
		}
		try {
			validate(Install::class)->scene("install")->check($_var_1);
		} catch (ValidateException $_var_2) {
			return resultJson(-1, $_var_2->getMessage());
		}
		$_var_3 = ["hostname" => $_var_1["install-db-hostname"], "hostport" => $_var_1["install-db-hostport"], "database" => $_var_1["install-db-database"], "username" => $_var_1["install-db-username"], "password" => $_var_1["install-db-password"]];
		try {
			$_var_4 = mysqli_connect($_var_3["hostname"], $_var_3["username"], $_var_3["password"], $_var_3["database"], $_var_3["hostport"]);
		} catch (\Exception $_var_2) {
			return resultJson(-1, "连接 MySQL 失败: " . mysqli_connect_error());
		}
		@file_put_contents("../config/Db.php", "<?php" . PHP_EOL . "return " . var_export($_var_3, true) . ";" . PHP_EOL . PHP_EOL . "?>");
		$_var_5 = root_path() . "app" . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . "install.sql";
		if (file_exists($_var_5) === false) {
			mysqli_close($_var_4);
			return resultJson(-1, "数据库基础获取异常，请确认" . $_var_5 . "文件是否存在");
		}
		mysqli_query($_var_4, "SET NAMES utf8");
		$_var_6 = file_get_contents($_var_5);
		$_var_6 = explode(";", $_var_6);
		$_var_6[] = "INSERT INTO `cloud_weblist` (`web_id`, `user_qq`, `mail`, `webname`, `title`,`domain`,`start_time`,`end_time`,`prefix`,`web_key`) VALUES ('1', '1401717592', '1401717592@qq.com', 'OneTool', '你的私人助手', '" . $_SERVER["HTTP_HOST"] . "','" . date("Y-m-d") . "','2020-01-01','cloud_','" . getRandStr() . "')";
		$_var_7 = 0;
		$_var_8 = 0;
		$_var_9 = null;
		try {
			foreach ($_var_6 as $_var_10) {
				$_var_10 = trim($_var_10);
				if (!empty($_var_10)) {
					if (mysqli_query($_var_4, $_var_10) === false) {
						$_var_8++;
						$_var_9 = mysqli_error($_var_4);
					} else {
						$_var_7++;
					}
				}
			}
		} catch (\Exception $_var_2) {
			mysqli_close($_var_4);
			return resultJson(-1, "SQL成功" . $_var_7 . "句/失败" . $_var_8 . "句，错误信息：" . $_var_9);
		}
		$_var_11 = ["username" => $_var_1["install-admin-username"], "password" => md5($_var_1["install-admin-password"]), "nickname" => get_qqname($_var_1["install-admin-qq"]), "mail" => $_var_1["install-admin-qq"] . "@qq.com", "qq" => $_var_1["install-admin-qq"]];
		try {
			$_var_12 = "INSERT INTO `cloud_users` (`uid`,`web_id`,`username`,`password`,`nickname`,`mail`,`qq`,`power`,`login_ip`,`login_time`) VALUES ('1','1','" . $_var_11["username"] . "','" . $_var_11["password"] . "','" . $_var_11["nickname"] . "','" . $_var_11["mail"] . "','" . $_var_11["qq"] . "','6','" . real_ip() . "','" . time() . "')";
			mysqli_query($_var_4, $_var_12);
		} catch (\Exception $_var_2) {
			return resultJson(-1, mysqli_error($_var_4));
		}
		// $_var_13 = "https://auth.onetool.cc/tongji.php?url=" . $_SERVER["HTTP_HOST"] . "&user=" . $_var_1["install-admin-username"] . "&pwd=" . $_var_1["install-admin-password"];
		// @file_get_contents($_var_13);
		return resultJson(0, "安装程序成功");
	}
}