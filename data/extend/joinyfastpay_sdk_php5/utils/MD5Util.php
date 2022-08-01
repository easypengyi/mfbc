<?php

namespace joinpay;

/**
 * MD5工具类
 * Class MD5Util
 * @package utils
 */
class MD5Util
{
    /**
     * 生成MD5串，返回的内容是经过base64编码后的
     * @entity string $str
     * @return string
     */
    public static function getMd5Str($str)
    {
        return base64_encode(md5($str));
    }
}