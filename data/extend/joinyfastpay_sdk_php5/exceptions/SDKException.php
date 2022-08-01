<?php

namespace joinpay;

/**
 * 自定义异常类
 * Class BizException
 * @package exceptions
 */
class SDKException extends \Exception {
    const PARAM_ERROR = "10000";
    const BIZ_ERROR = "20000";

    private $bizCode;
    private $msg;

    function __construct($bizCode, $msg) {
        parent::__construct();
        $this->bizCode = $bizCode;
        $this->msg = $msg;
    }

    /**
     * @return int
     */
    public function getBizCode()
    {
        return $this->bizCode;
    }

    /**
     * @entity int $bizCode
     */
    public function setBizCode($bizCode)
    {
        $this->bizCode = $bizCode;
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @entity mixed $msg
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }


}