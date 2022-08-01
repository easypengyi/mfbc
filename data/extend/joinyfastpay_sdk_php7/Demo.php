<?php
/**
 * 测试样例类
 *
 * PHP VERSION = PHP 7.2.20
 */
require "Autoload.php";

use joinpay\Request;
use joinpay\SecretKey;
use joinpay\RequestUtil;
use joinpay\RandomUtil;
use joinpay\AESUtil;

//平台公钥
$platPublicKey = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC615oqLdSc57tZN7yWxxAIuMSoxQ7H4+Rf6lmsU5g43S3aNn8OhDLKsndBH7EUlSGvljiZL/E6/SZ/V++ikNrWrsLb76m1wGd9cz2XuQBYe+qmWFEx+DPYVcV6r2IN2YOH9rdLYUjfeAFC71xgAL1DNM4Kx+SeYAEqoVJSLnGpOQIDAQAB
-----END PUBLIC KEY-----";

//商户私钥
$mchPrivateKey = "-----BEGIN RSA PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANg4HO5kqYaY7x13DURhrYEitwB21sk7Z2QBDCSP3aWDgFARXEUOlRD16TdoQAyK5rq92hZrzpCbYE7hUgwvOoxA+dYh78nKLIMPE2WHjJuPyuGfqzgV0wNEu9sas00kA+36bwq2XtjnRLBmj8SWjzdFFnROAc4BhCz6BOKP044nAgMBAAECgYBrQpLflCIg+jcMd+Wl+Yq31//OhCWS2Bw3GOnsLU438F8z2RjbzRsXudYCvX2geztwggPxQXPMerexCcfI8Zjp1qaqFQ3IEVG/1W9Pz11ciqWGyWFM033fktTd5IdRTUZd/K8oV/WacDOvKHYvTKodHyh325dOJ61pVCR5f5oWkQJBAP4qBajL+S7jeJ8zxgpoxLuFi0zcGVW/OnVV6edPQ+eHFB0Q74Zc3bd6ILr4gDIMWS4od2GnRKOY2JD8dA7iZq8CQQDZx+0rUdbFdhaT4aEG4Arkyl6PiN9LFXwX9L54vt3yYrrlFU73vRwcSScfYxQV0gt9itXPtLDjTgKHhgtt3a4JAkEArMLdk+4J08BU5kon7D1otFpC5JybL/jLAKTEWDE94+uiVVuEpJ0NLED8bHqrkNlp6QEinKM4+cbUNkETlmZ4CwJAQ9RdLizjM8U/6vdPbBDD09aj9RiwU3ZxnBSCbqEkB6Zwh4FHgynHY5f1M3VsgA9XvNZNGdAxd9qINyWs0Z9F4QJAVdaPQ7gOZiDpVhvh5r/+igpgbkaBE+++FmvArakMgJCRggSnIXDALKtCHkRNzsuLIFRICrqJMdZsavOJRclouA==
-----END RSA PRIVATE KEY-----";

$secKey = RandomUtil::randomStr(16);

$data = [];
$data["mch_no_trade"] = "";
$data["mch_order_no"] = "201912020001209";
$data["order_amount"] = "0.10";
$data["mch_req_time"] = date('Y-m-d H:i:s', time());
$data["order_desc"] = "测试sdk-php7";
$data["id_type"] = "1";
$data["callback_url"] = "http://www.baidu.com";
$data["callback_param"] =null;
$data["payer_name"] = AESUtil::encryptECB("您好", $secKey);//加密
$data["id_no"] = AESUtil::encryptECB("", $secKey);//加密
$data["bank_card_no"] = AESUtil::encryptECB("", $secKey);//加密
$data["mobile_no"] = AESUtil::encryptECB("", $secKey);//加密

$request = new Request();
$request->setMethod("fastPay.direct.paySms");
$request->setVersion("1.0");
$request->setMchNo("888100000002985");
$request->setSignType("2");
$request->setRandStr(RandomUtil::randomStr(32));
$request->setData($data);
$request->setSecKey($secKey);//rsa有效

$secretKey = new SecretKey();
$secretKey->setReqSignKey($mchPrivateKey);//签名：使用商户私钥
$secretKey->setRespVerifyKey($platPublicKey);//验签：使用平台公钥
$secretKey->setSecKeyEncryptKey($platPublicKey);//sec_key加密：使用平台公钥
$secretKey->setSecKeyDecryptKey($mchPrivateKey);//sec_key解密：使用商户私钥

$url = "https://api.joinpay.com/fastpay";
try {
    $response = RequestUtil::doRequest($url, $request, $secretKey);
    if ($response->isSuccess()) {//受理成功
        $dataArr = json_decode($response->getData(), true);
        if($dataArr["order_status"] == "P1000"){//订单交易成功
            echo "SUCCESS, Response = ";
            print_r($response);
        }else{
            echo "FAIL OR PROCESSING OR UNKNOWN, Response = ";
            print_r($dataArr);
        }
    }else{
        echo "受理失败, Response = ";
        print_r($response);
    }
} catch (Exception $e) {
    print_r($e);
}

