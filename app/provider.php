<?php
use app\ExceptionHandle;
use app\Request;
error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT ^ E_WARNING);

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
];
