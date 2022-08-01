<?php

namespace joinpay;

/**
 * 使用RSA进行加密、解密、签名、验签
 * Class RSAUtil
 * @package utils
 */
class RSAUtil
{
    /**
     * 使用公钥加密
     * @param string $data
     * @param string $pubKey
     * @return string
     * @throws SDKException
     */
    public static function encrypt(string $data, string $pubKey){
        $pubKey = openssl_get_publickey($pubKey);
        if($pubKey === false){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa解密公钥无效");
        }

        $crypted = '';
        $isSuccess = openssl_public_encrypt($data, $crypted, $pubKey);
        openssl_free_key($pubKey);
        if($isSuccess == false){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa加密失败");
        }
        return base64_encode($crypted);
    }

    /**
     * 使用私钥解密
     * @param string $data
     * @param string $priKey
     * @return string
     * @throws SDKException
     */
    public static function decrypt(string $data, string $priKey){
        $priKey = openssl_get_privatekey($priKey);
        if($priKey === false){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa解密私钥无效");
        }

        $decrypted = '';
        $isSuccess = openssl_private_decrypt(base64_decode($data), $decrypted, $priKey);
        openssl_free_key($priKey);
        if(! $isSuccess){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa解密失败");
        }
        return $decrypted;
    }

    /**
     * 使用私钥进行签名
     * @param string $data
     * @param string $priKey
     * @return string
     * @throws SDKException
     */
    public static function sign(string $data, string $priKey){
        $priKey = openssl_get_privatekey($priKey);
        if($priKey === false){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa签名私钥无效");
        }

        $binary_signature = '';
        $isSuccess = openssl_sign($data, $binary_signature, $priKey, OPENSSL_ALGO_MD5);
        openssl_free_key($priKey);
        if(! $isSuccess){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa签名失败");
        }
        return base64_encode($binary_signature);
    }

    /**
     * 使用公钥进行验签
     * @param string $signData
     * @param string $signParam
     * @param string $pubKey
     * @return bool
     * @throws SDKException
     */
    public static function verify(string $signData, string $signParam, string $pubKey){
        $pubKey = openssl_get_publickey($pubKey);
        if($pubKey === false){
            throw new SDKException(SDKException::BIZ_ERROR, "rsa验签公钥无效");
        }

        $signParam = base64_decode($signParam);
        $isMatch = openssl_verify($signData, $signParam, $pubKey, OPENSSL_ALGO_MD5) === 1;
        openssl_free_key($pubKey);
        return $isMatch;
    }
}