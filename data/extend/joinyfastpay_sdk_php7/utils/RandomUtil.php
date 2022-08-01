<?php

namespace joinpay;

/**
 * 随机数生成工具类
 * Class RandomUtil
 * @package utils
 */
class RandomUtil {
    const CHARS =  "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    /**
     * 生成指定长度的随机字符串
     * @entity int $length
     * @return string
     */
    public static function randomStr( $length = 16 ) {
        $str = "";
        $strLen = strlen(static::CHARS) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= static::CHARS[mt_rand(0, $strLen)];
        }
        return $str;
    }
}