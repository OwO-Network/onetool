<?php

//decode by http://www.yunlu99.com/
namespace app\index\controller;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Session;
class Common
{
	function __construct()
	{
		$this->CheckWebSite();
		$this->SystemCheck("goooooooooooooooooooooooooooooooooooooood");
		$this->SystemCheck2("gooooooooooooooasdsaooooooood");
		$this->SystemCheck5("wcnmdb");
	}
	public function CheckWebSite()
	{
		if (!Session::get("authcode") || !Cache::get("domain")) {
			$_var_0 = Config::get("authcode")["authcode"];
			if (Session::get("authcode") && Session::get("authcode") != base64_encode($_var_0)) {
				exit("<h3>如果我道歉的话你会好受些吗</h3>");
			}
			// $_var_1 = file_get_contents("https://auth.onetool.cc/check.php?url=" . $_SERVER["HTTP_HOST"] . "&authcode=" . $_var_0);
			// $_var_2 = json_decode($_var_1, true);
			// if ($_var_2["code"] == 1) {
			// 	Cache::set("domain", $_SERVER["HTTP_HOST"]);
			// 	Session::set("authcode", base64_encode($_var_0));
			// } else {
			// 	exit("<h3>" . $_var_2["msg"] . "</h3>");
			// }
		}
	}
	public function CheckAjaxRequest($url)
	{
		$_var_3 = curl_init($url);
		curl_setopt($_var_3, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($_var_3, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($_var_3, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_var_3, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1");
		curl_setopt($_var_3, CURLOPT_TIMEOUT, 30);
		$_var_4 = curl_exec($_var_3);
		curl_close($_var_3);
		return $_var_4;
	}
	public function CheckLoginUser($url)
	{
		$_var_5 = curl_init($url);
		curl_setopt($_var_5, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($_var_5, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($_var_5, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_var_5, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1");
		curl_setopt($_var_5, CURLOPT_TIMEOUT, 30);
		$_var_6 = curl_exec($_var_5);
		curl_close($_var_5);
		return $_var_6;
	}
	public function CheckUserPower($url)
	{
		$_var_7 = curl_init($url);
		curl_setopt($_var_7, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($_var_7, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($_var_7, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_var_7, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1");
		curl_setopt($_var_7, CURLOPT_TIMEOUT, 30);
		$_var_8 = curl_exec($_var_7);
		curl_close($_var_7);
		return $_var_8;
	}
	public function LoadConfigs($url)
	{
		$_var_9 = curl_init($url);
		curl_setopt($_var_9, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($_var_9, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($_var_9, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_var_9, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1");
		curl_setopt($_var_9, CURLOPT_TIMEOUT, 30);
		$_var_10 = curl_exec($_var_9);
		curl_close($_var_9);
		return $_var_10;
	}
	public function SystemCheck($a)
	{
		if ($a != "goooooooooooooooooooooooooooooooooooooood") {
			exit("nmslnmslnmslnmslnmslnmslnmsl");
		}
	}
	public function SystemCheck2($a)
	{
		if ($a != "gooooooooooooooasdsaooooooood") {
			exit;
		}
	}
	public function SystemCheck3($a)
	{
		if ($a != "gooooooooooooooodbsjkabjkooooooooooooooooooooooood") {
			exit;
		}
	}
	public function SystemCheck4($a)
	{
		if ($a != "daslkjlknls") {
			exit;
		}
	}
	public function SystemCheck5($a)
	{
		if ($a != "wcnmdb") {
			exit("nmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmslnmsl");
		}
	}
}