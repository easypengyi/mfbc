<?php

namespace joinpay;

/**
 * 密钥类
 * Class SecretKey
 * @package entity
 */
class SecretKey {
    /**
     * 对请求参数进行签名的私钥
     */
    private $reqSignKey = "";
    /**
     * 对响应参数进行验签的公钥
     */
    private $respVerifyKey = "";
    /**
     * 对sec_key进行加密的公钥
     */
    private $secKeyEncryptKey = "";
    /**
     * 对sec_key进行解密的私钥
     */
    private $secKeyDecryptKey = "";

    /**
     * @return mixed
     */
    public function getReqSignKey()
    {
        return $this->reqSignKey;
    }

    /**
     * @param mixed $reqSignKey
     */
    public function setReqSignKey($reqSignKey)
    {
        $this->reqSignKey = $reqSignKey;
    }

    /**
     * @return mixed
     */
    public function getRespVerifyKey()
    {
        return $this->respVerifyKey;
    }

    /**
     * @param mixed $respVerifyKey
     */
    public function setRespVerifyKey($respVerifyKey)
    {
        $this->respVerifyKey = $respVerifyKey;
    }

    /**
     * @return mixed
     */
    public function getSecKeyEncryptKey()
    {
        return $this->secKeyEncryptKey;
    }

    /**
     * @param mixed $secKeyEncryptKey
     */
    public function setSecKeyEncryptKey($secKeyEncryptKey)
    {
        $this->secKeyEncryptKey = $secKeyEncryptKey;
    }

    /**
     * @return mixed
     */
    public function getSecKeyDecryptKey()
    {
        return $this->secKeyDecryptKey;
    }

    /**
     * @param mixed $secKeyDecryptKey
     */
    public function setSecKeyDecryptKey($secKeyDecryptKey)
    {
        $this->secKeyDecryptKey = $secKeyDecryptKey;
    }

}