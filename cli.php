<?php
// 检测PHP环境

if (version_compare(PHP_VERSION, '5.3.0', '<')) die('require PHP > 5.3.0 !');
//
define('DOC_ROOT', dirname(__FILE__));

//// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', True);
//
define('MODE_NAME', 'cli');  // 采用CLI运行模式运行
//
//// 定义应用目录
define('APP_PATH', dirname(__FILE__).'/Application/');
//
//// 绑定ap模块到当前入口文件
////define('BIND_MODULE', 'V1');
//
//// 引入ThinkPHP入口文件
require 'ThinkPHP/ThinkPHP.php';