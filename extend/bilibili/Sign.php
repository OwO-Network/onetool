<?php

namespace bilibili;

class Sign extends Bilibili
{
    /**
     * @use 登录
     * @param $data
     * @return array
     */
    public static function logins($data = []): array
    {
        # Android 新
        $app_key = base64_decode("NzgzYmJiNzI2NDQ1MWQ4Mg==");
        $app_secret = base64_decode("MjY1MzU4M2M4ODczZGVhMjY4YWI5Mzg2OTE4YjFkNjU=");

        $default = [
            'access_key' => parent::$access_key,
            'actionKey' => 'appkey',
            'appkey' => $app_key,
            'build' => "6510400",
            'channel' => "bili",
            'device' => "phone",
            'mobi_app' => "android",
            'platform' => "android",
            'ts' => time(),
        ];
        $payload = array_merge($data, $default);
        return self::encryption($payload, $app_secret);
    }

    /**
     * @use 通用
     * @param $payload
     * @return array
     */
    public static function common($payload): array
    {
        # Android 旧
        $app_key = base64_decode("MWQ4YjZlN2Q0NTIzMzQzNg==");
        $app_secret = base64_decode("NTYwYzUyY2NkMjg4ZmVkMDQ1ODU5ZWQxOGJmZmQ5NzM");

        $default = [
            'access_key' => parent::$access_key,
            'actionKey' => 'appkey',
            'appkey' => $app_key,
            'build' => "6510400",
            'device' => "phone",
            'mobi_app' => "android",
            'platform' => "android",
            'ts' => time(),
        ];
        $payload = array_merge($payload, $default);
        return self::encryption($payload, $app_secret);
    }


    /**
     * @use 加密
     * @param array $payload
     * @param string $app_secret
     * @return array
     */
    public static function encryption(array $payload, string $app_secret): array
    {
        if (isset($payload['sign'])) {
            unset($payload['sign']);
        }
        ksort($payload);
        $data = http_build_query($payload);
        $payload['sign'] = md5($data . $app_secret);
        return $payload;
    }
}