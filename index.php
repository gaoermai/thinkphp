<?php

####################################
# 初始化使用composer安装的类库
####################################
require_once __DIR__.'/Vendor/autoload.php';

####################################
# 初始化ThinkPHP
####################################
define('APP_DEBUG',false);
define('APP_NAME','App');
define('APP_PATH','./App/');
require 'ThinkPHP/ThinkPHP.php';
?>
