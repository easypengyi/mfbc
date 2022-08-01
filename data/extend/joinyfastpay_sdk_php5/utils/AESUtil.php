<?php

namespace joinpay;


/**
 * AES加解密工具类
 * Class AESUtil
 * @package utils
 */
class AESUtil {
    const EBC_MODE = "AES-128-ECB";

    /**
     * AES加密，模式为：AES/ECB/PKCK7Padding
     * @param string $data
     * @param string $secKey
     * @return string
     * @throws SDKException
     */
    public static function encryptECB($data, $secKey){
        $encrypted = openssl_encrypt($data, self::EBC_MODE, $secKey, OPENSSL_RAW_DATA);
        if($encrypted === false){
            throw new SDKException(SDKException::BIZ_ERROR, "aes加密失败");
        }
        return base64_encode($encrypted);
    }

    /**
     * AES解密，模式为：AES/ECB/PKCK7Padding
     * @param string $data
     * @param string $secKey
     * @return string
     * @throws SDKException
     */
    public static function decryptECB($data, $secKey){
        $decrypted = openssl_decrypt(base64_decode($data), self::EBC_MODE, $secKey, OPENSSL_RAW_DATA);
        if($decrypted === false){
            throw new SDKException(SDKException::BIZ_ERROR, "aes解密失败");
        }
        return $decrypted;
    }
}