<?php
/**
 * 异步测试样例类
 *
 * PHP VERSION = PHP 5.6
 */
require "Autoload.php";

use joinpay\Response;

$response = new Response();


//异步通知
$notify='{"biz_code":"JS000000","biz_msg":"成功","data":"{\"errCode\":\"CP110016\",\"errMsg\":\"持卡人账户名称与签约行记录不符\",\"jp_order_no\":\"100120200722169474896517382145\",\"mch_order_no\":\"1595404363\",\"order_amount\":0.10,\"order_desc\":\"测试sdk-php7\",\"order_status\":\"P2000\"}","mch_no":"888100000002985","rand_str":"plm991dz9pqn05d5am7g0ayco68ml2wk","sign":"OOI1SFC2AV2wADxH09vNqmxkdS0bMzwq0rpUdPw00jNxIuLsSf3wW/kbxjOuMwYKxkUpDMV5R2xlES1nRpmqXatuMHBWnfXjuxmEiwzzvbgQUJ0AwuD6rtNaBAZfljlwlLMMlksRrsIMlmSvPZUbWCX5tvL/9cR+6W/4eEe8Uqc=","sign_type":"2"}';
//汇聚公钥
$pubKey="-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC615oqLdSc57tZN7yWxxAIuMSoxQ7H4+Rf6lmsU5g43S3aNn8OhDLKsndBH7EUlSGvljiZL/E6/SZ/V++ikNrWrsLb76m1wGd9cz2XuQBYe+qmWFEx+DPYVcV6r2IN2YOH9rdLYUjfeAFC71xgAL1DNM4Kx+SeYAEqoVJSLnGpOQIDAQAB
-----END PUBLIC KEY-----";


$array=json_decode($notify,true);
$response->setBizCode($array['biz_code']);
$response->setBizMsg($array['biz_msg']);
$response->setData($array['data']);
$response->setMchNo($array['mch_no']);
$response->setRandStr($array['rand_str']);
$response->setSign($array['sign']);
$response->setSignType($array['sign_type']);

//异步返回sign
$signParam=$array['sign'];

$signData=\joinpay\SignUtil::getSortedString($response);
print_r($signData);
echo "\n";

$isMatch = \joinpay\RSAUtil::verify($signData, $signParam, $pubKey);
print_r($isMatch);

