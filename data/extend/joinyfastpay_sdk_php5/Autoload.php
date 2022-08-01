<?php
/**
 * 自动加载，把需要使用的相关类自动加载到php环境中
 */

spl_autoload_register('autoload');

function autoload(){
    require "entity/Request.php";
    require "entity/Response.php";
    require "entity/SecretKey.php";
    require "entity/Callback.php";
    require "exceptions/SDKException.php";
    require "utils/AESUtil.php";
    require "utils/HttpUtil.php";
    require "utils/MD5Util.php";
    require "utils/RandomUtil.php";
    require "utils/RequestUtil.php";
    require "utils/RSAUtil.php";
    require "utils/SignUtil.php";
    require "utils/ObjectUtil.php";
}