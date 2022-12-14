<?php
namespace data\extend\weixin;

use addons\miniprogram\model\WeixinAuthModel;
use data\extend\weixin\WxPayException as WxPayException;
use data\extend\weixin\WxPayConfig as WxPayConfig;
use data\extend\weixin\WxPayData\WxPayReport;
use data\extend\weixin\WxPayData\WxPayResults;
use data\model\VslMemberRechargeModel;
use data\model\VslOrderModel;
use data\model\WebSiteModel;
use data\service\Config;
use think\Log;

/**
 *
 * 接口访问类，包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 *
 * @author widyhu
 *        
 */
class WxPayApi
{

    function __construct()
    {}

    /**
     *
     * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayUnifiedOrder $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function unifiedOrder($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        } else 
            if (! $inputObj->IsBodySet()) {
                throw new WxPayException("缺少统一支付接口必填参数body！");
            } else 
                if (! $inputObj->IsTotal_feeSet()) {
                    throw new WxPayException("缺少统一支付接口必填参数total_fee！");
                } else 
                    if (! $inputObj->IsTrade_typeSet()) {
                        throw new WxPayException("缺少统一支付接口必填参数trade_type！");
                    }
        
        // 关联参数
        if ($inputObj->GetTrade_type() == "JSAPI" && ! $inputObj->IsOpenidSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        if ($inputObj->GetTrade_type() == "NATIVE" && ! $inputObj->IsProduct_idSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }
        $WxPayConfig = new WxPayConfig();
        
        $inputObj->SetAppid($WxPayConfig->appid); // 公众账号ID
        $inputObj->SetMch_id($WxPayConfig->MCHID); // 商户号
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetNonce_str(self::getNonceStr());
        $inputObj->SetSign();
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        $result1 = json_encode($result);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        return $result;
    }
    public static function unifiedOrderApp($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        } else
            if (! $inputObj->IsBodySet()) {
                throw new WxPayException("缺少统一支付接口必填参数body！");
            } else
                if (! $inputObj->IsTotal_feeSet()) {
                    throw new WxPayException("缺少统一支付接口必填参数total_fee！");
                } else
                    if (! $inputObj->IsTrade_typeSet()) {
                        throw new WxPayException("缺少统一支付接口必填参数trade_type！");
                    }
            $config = new Config();
            $order = new VslOrderModel();
            $order_from = $order->getInfo(['out_trade_no|out_trade_no_presell'=>$inputObj->GetOut_trade_no()],'website_id');
            $order_recharge = new VslMemberRechargeModel();
            $recharge_info = $order_recharge->getInfo(['out_trade_no'=>$inputObj->GetOut_trade_no()],'website_id');
            if(empty($order_from)){
                $website_id = $recharge_info['website_id'];
            }else{
                $website_id = $order_from['website_id'];
            }
            $wchat_config = $config->getConfig(0, 'WPAYAPP', $website_id);
            
            $appid =  $wchat_config['value']['appid'];
            $MCHID =  $wchat_config['value']['mch_id'];
        $inputObj->SetAppid($appid); // 公众账号ID
        $inputObj->SetMch_id($MCHID); // 商户号
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetNonce_str(self::getNonceStr());
        $inputObj->SetSignApp();
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        $result1 = json_encode($result);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        return $result;
    }
    public static function unifiedOrderMir($inputObj, $timeOut = 6, $website_id = 0)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        } else
            if (! $inputObj->IsBodySet()) {
                throw new WxPayException("缺少统一支付接口必填参数body！");
            } else
                if (! $inputObj->IsTotal_feeSet()) {
                    throw new WxPayException("缺少统一支付接口必填参数total_fee！");
                } else
                    if (! $inputObj->IsTrade_typeSet()) {
                        throw new WxPayException("缺少统一支付接口必填参数trade_type！");
                    }

        // 关联参数
        if ($inputObj->GetTrade_type() == "JSAPI" && ! $inputObj->IsOpenidSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        $config = new Config();
        $order = new VslOrderModel();
        $order_from = $order->getInfo(['out_trade_no|out_trade_no_presell'=>$inputObj->GetOut_trade_no()],'website_id');
        $order_recharge = new VslMemberRechargeModel();
        $recharge_info = $order_recharge->getInfo(['out_trade_no'=>$inputObj->GetOut_trade_no()],'website_id');
        if(empty($website_id)){
            if(empty($order_from)){
                $website_id = $recharge_info['website_id'];
            }else{
                $website_id = $order_from['website_id'];
            }
        }

        $wchat_config = $config->getConfig(0, 'MPPAY', $website_id);
        $mchid=  $wchat_config['value']['mchid'];
        $auth = new WeixinAuthModel();
        $appid =  $auth->getInfo(['website_id'=>$website_id],'authorizer_appid')['authorizer_appid'];
        

        $inputObj->SetAppid($appid); // 小程序ID
        $inputObj->SetMch_id($mchid); // 商户号
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        $inputObj->SetSignMp($website_id);
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
//        $result1 = json_encode($result);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        return $result;
    }
    /**
     *
     * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayUnifiedOrder $inputObj
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function unifiedOrders($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        } else
            if (! $inputObj->IsBodySet()) {
                throw new WxPayException("缺少统一支付接口必填参数body！");
            } else
                if (! $inputObj->IsTotal_feeSet()) {
                    throw new WxPayException("缺少统一支付接口必填参数total_fee！");
                } else
                    if (! $inputObj->IsTrade_typeSet()) {
                        throw new WxPayException("缺少统一支付接口必填参数trade_type！");
                    }

        // 关联参数
        if ($inputObj->GetTrade_type() == "JSAPI" && ! $inputObj->IsOpenidSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        if ($inputObj->GetTrade_type() == "NATIVE" && ! $inputObj->IsProduct_idSet()) {
            throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }
        $config = new Config();
        $WxPayConfig = $config->getConfigMaster(0, 'WPAY', 0, 1);
        
        $inputObj->SetAppid($WxPayConfig['appid']); // 公众账号ID
        $inputObj->SetMch_id($WxPayConfig['mch_id']); // 商户号
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        $inputObj->SetSigns();
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        $result1 = json_encode($result);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        return $result;
    }
    /**
     *
     * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayOrderQuery $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function orderQuery($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet() && ! $inputObj->IsTransaction_idSet()) {
            throw new WxPayException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
        $WxPayConfig = new WxPayConfig();
        $inputObj->SetAppid($WxPayConfig->appid); // 公众账号ID
        $inputObj->SetMch_id($WxPayConfig->MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     *
     * 关闭订单，WxPayCloseOrder中out_trade_no必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayCloseOrder $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function closeOrder($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/closeorder";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            throw new WxPayException("订单查询接口中，out_trade_no必填！");
        }
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     *
     * 申请退款，WxPayRefund中out_trade_no、transaction_id至少填一个且
     * out_refund_no、total_fee、refund_fee、op_user_id为必填参数
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayRefund $inputObj            
     * @param int $timeOut            
     * @param int $website_id
     * @param int $is_mp [是否是小程序微信配置]
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function refund($inputObj, $timeOut = 6,$website_id = 0, $is_mp = 0)
    {
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet()) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "退款申请接口中，缺少必填参数out_trade_no！"
            );
        } elseif (! $inputObj->IsOut_refund_noSet()) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "退款申请接口中，缺少必填参数out_refund_no！"
            );
        } elseif (! $inputObj->IsTotal_feeSet()) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "退款申请接口中，缺少必填参数total_fee！"
            );
        } elseif (! $inputObj->IsRefund_feeSet()) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "退款申请接口中，缺少必填参数refund_fee！"
            );
        }
        $WxPayConfig = new WxPayConfig($website_id, $is_mp);
        $inputObj->SetAppid($WxPayConfig->appid); // 公众账号ID
        $inputObj->SetMch_id($WxPayConfig->MCHID); // 商户号
                                                   // $inputObj->SetAppid(WxPayConfig::APPID);//公众账号ID
                                                   // $inputObj->SetMch_id(WxPayConfig::MCHID);//商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        $inputObj->SetOp_user_id($WxPayConfig->MCHID);
        if ($is_mp == 1) {
            $inputObj->SetSignMp($website_id); // 签名 - 小程序微信
        } else {
            $inputObj->SetSign($website_id); // 签名 - 微信
        }
        $xml = $inputObj->ToXml();
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        try {
            $response = self::postXmlCurl($xml, $url, true, $timeOut,$website_id);
            if($response == "微信数字证书未找到"){
                 return array(
                'return_code' => "FAIL",
                'return_msg' => "微信数字证书未找到"
            );
            }
            if($response == "数字证书路径报错"){
                return array(
                    'return_code' => "FAIL",
                    'return_msg' => "微信数字证书不是绝对路径"
                );
            }
            $result = WxPayResults::Init($response);
            self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
            return $result;
        } catch (\Exception $e) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => $e->getMessage()
            );
        }
    }

    public static function withdraw($openid,$trade_no,$name,$money,$text,$str,$ip,$timeOut,$website_id)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        // 检测必填参数
        if (!$openid) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "提现申请接口中，缺少必填参数用户openid"
            );
        } elseif (!$trade_no) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "提现申请接口中，缺少必填参数提现交易号"
            );
        } elseif (!$money) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "提现申请接口中，缺少必填参数提现金额！"
            );;
        } elseif (!$text) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => "提现申请接口中，缺少必填参数提现描述信息！"
            );
        }
        $money *= 100;
        $WxPayConfig = new WxPayConfig($website_id);
        
        $pars = array();
        $pars['mch_appid'] = $WxPayConfig->appid;
        $pars['mchid'] = $WxPayConfig->MCHID;
        $pars['nonce_str'] = $str;
        $pars['partner_trade_no'] = $trade_no;
        $pars['openid'] = $openid;
        $pars['check_name'] = 'NO_CHECK';
        $pars['amount'] = $money;
        $pars['desc'] = $text;
        $pars['spbill_create_ip'] = $ip;
       
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= $k . '=' . $v . '&';
        }

        $string1 .= 'key=' . $WxPayConfig->key;
        $sign = strtoupper(md5($string1));
        $xml = "<xml>
                    <mch_appid>{$WxPayConfig->appid}</mch_appid>
                    <mchid>{$WxPayConfig->MCHID}</mchid>
                    <nonce_str>{$str}</nonce_str>
                    <partner_trade_no>{$trade_no}</partner_trade_no>
                    <openid>{$openid}</openid>
                    <check_name>NO_CHECK</check_name>
                    <re_user_name>{$name}</re_user_name>
                    <amount>{$money}</amount>
                    <desc>{$text}</desc>
                    <spbill_create_ip>{$ip}</spbill_create_ip>
                    <sign>{$sign}</sign>
                </xml>";
                
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        try {
            $response = self::postXmlCurl($xml, $url, true, $timeOut,$website_id);
            if($response == "微信数字证书未找到"){
                return array(
                    'return_code' => "FAIL",
                    'return_msg' => "微信数字证书未找到"
                );
            }
            if($response == "数字证书路径报错"){
                return array(
                    'return_code' => "FAIL",
                    'return_msg' => "微信数字证书路径报错"
                );
            }
            $result = WxPayResults::Init($response);
            Log::write("微信提现，result：" . json_encode($result));
            self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
            return $result;
        } catch (\Exception $e) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => $e->getMessage()
            );
        }
    }
    /**
     *
     * 查询退款
     * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，
     * 用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
     * WxPayRefundQuery中out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayRefundQuery $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function refundQuery($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/refundquery";
        // 检测必填参数
        if (! $inputObj->IsOut_refund_noSet() && ! $inputObj->IsOut_trade_noSet() && ! $inputObj->IsTransaction_idSet() && ! $inputObj->IsRefund_idSet()) {
            throw new WxPayException("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
        }
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     * 下载对账单，WxPayDownloadBill中bill_date为必填参数
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayDownloadBill $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function downloadBill($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/downloadbill";
        // 检测必填参数
        if (! $inputObj->IsBill_dateSet()) {
            throw new WxPayException("对账单接口中，缺少必填参数bill_date！");
        }
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        if (substr($response, 0, 5) == "<xml>") {
            return "";
        }
        return $response;
    }

    /**
     * 提交被扫支付API
     * 收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，
     * 由商户收银台或者商户后台调用该接口发起支付。
     * WxPayWxPayMicroPay中body、out_trade_no、total_fee、auth_code参数必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayWxPayMicroPay $inputObj            
     * @param int $timeOut            
     */
    public static function micropay($inputObj, $timeOut = 10)
    {
        $url = "https://api.mch.weixin.qq.com/pay/micropay";
        // 检测必填参数
        if (! $inputObj->IsBodySet()) {
            throw new WxPayException("提交被扫支付API接口中，缺少必填参数body！");
        } else 
            if (! $inputObj->IsOut_trade_noSet()) {
                throw new WxPayException("提交被扫支付API接口中，缺少必填参数out_trade_no！");
            } else 
                if (! $inputObj->IsTotal_feeSet()) {
                    throw new WxPayException("提交被扫支付API接口中，缺少必填参数total_fee！");
                } else 
                    if (! $inputObj->IsAuth_codeSet()) {
                        throw new WxPayException("提交被扫支付API接口中，缺少必填参数auth_code！");
                    }
        
        $inputObj->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     *
     * 撤销订单API接口，WxPayReverse中参数out_trade_no和transaction_id必须填写一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayReverse $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     */
    public static function reverse($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/secapi/pay/reverse";
        // 检测必填参数
        if (! $inputObj->IsOut_trade_noSet() && ! $inputObj->IsTransaction_idSet()) {
            throw new WxPayException("撤销订单API接口中，参数out_trade_no和transaction_id必须填写一个！");
        }
        
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, true, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     *
     * 测速上报，该方法内部封装在report中，使用时请注意异常流程
     * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayReport $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function report($inputObj, $timeOut = 1)
    {
        $url = "https://api.mch.weixin.qq.com/payitil/report";
        // 检测必填参数
        if (! $inputObj->IsInterface_urlSet()) {
            throw new WxPayException("接口URL，缺少必填参数interface_url！");
        }
        if (! $inputObj->IsReturn_codeSet()) {
            throw new WxPayException("返回状态码，缺少必填参数return_code！");
        }
        if (! $inputObj->IsResult_codeSet()) {
            throw new WxPayException("业务结果，缺少必填参数result_code！");
        }
        if (! $inputObj->IsUser_ipSet()) {
            throw new WxPayException("访问接口IP，缺少必填参数user_ip！");
        }
        if (! $inputObj->IsExecute_time_Set()) {
            throw new WxPayException("接口耗时，缺少必填参数execute_time_！");
        }
        
        $WxPayConfig = new WxPayConfig();
        $inputObj->SetAppid($WxPayConfig->appid); // 公众账号ID
        $inputObj->SetMch_id($WxPayConfig->MCHID); // 商户号
        $inputObj->SetUser_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        $inputObj->SetTime(date("YmdHis")); // 商户上报时间
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        return $response;
    }

    /**
     *
     * 生成二维码规则,模式一生成支付二维码
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayBizPayUrl $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function bizpayurl($inputObj, $timeOut = 6)
    {
        if (! $inputObj->IsProduct_idSet()) {
            throw new WxPayException("生成二维码，缺少必填参数product_id！");
        }
        
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetTime_stamp(time()); // 时间戳
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        
        return $inputObj->GetValues();
    }

    /**
     *
     * 转换短链接
     * 该接口主要用于扫码原生支付模式一中的二维码链接转成短链接(weixin://wxpay/s/XXXXXX)，
     * 减小二维码数据量，提升扫描速度和精确度。
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     *
     * @param WxPayShortUrl $inputObj            
     * @param int $timeOut            
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public static function shorturl($inputObj, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/tools/shorturl";
        // 检测必填参数
        if (! $inputObj->IsLong_urlSet()) {
            throw new WxPayException("需要转换的URL，签名用原串，传输需URL encode！");
        }
        $inputObj->SetAppid(WxPayConfig::APPID); // 公众账号ID
        $inputObj->SetMch_id(WxPayConfig::MCHID); // 商户号
        $inputObj->SetNonce_str(self::getNonceStr()); // 随机字符串
        
        $inputObj->SetSign(); // 签名
        $xml = $inputObj->ToXml();
        
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
        
        return $result;
    }

    /**
     *
     * 支付结果通用通知
     *
     * @param function $callback
     *            直接回调函数使用方法: notify(you_function);
     *            回调类成员函数方法:notify(array($this, you_function));
     *            $callback 原型为：function function_name($data){}
     */
    public static function notify($callback, &$msg)
    {
        // 获取通知的数据
        $xml = file_get_contents('php://input');
        // 如果返回成功则验证签名
        try {
            $result = WxPayResults::Init($xml);
        } catch (WxPayException $e) {
            $msg = $e->errorMessage();
            return false;
        }
        
        return call_user_func($callback, $result);
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * 
     * @param int $length 随机内容长度
     * @param string $chars 随机内容来源
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        $chars = empty($chars) ? 'abcdefghijklmnopqrstuvwxyz0123456789' : $chars;
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 直接输出xml
     *
     * @param string $xml            
     */
    public static function replyNotify($xml)
    {
        echo $xml;
    }

    /**
     * 发红包
     * @param string $act_name 活动名称
     * @param float $total_amount 付款金额，单位分
     * @param int $total_num 红包发放总人数
     * @param int $website_id
     * @param string $remark 备注信息
     * @param string $wishing 红包祝福语
     * @param string $re_openid 接受红包的用户openid
     * @param string $scene_id 发放红包使用场景，红包金额大于200或者小于1元时必传:
     * PRODUCT_1:商品促销
     * PRODUCT_2:抽奖
     * PRODUCT_3:虚拟物品兑奖
     * PRODUCT_4:企业内部福利
     * PRODUCT_5:渠道分润
     * PRODUCT_6:保险回馈
     * PRODUCT_7:彩票派奖
     * PRODUCT_8:税务刮奖
     * @param string $risk_info 例: posttime%3d123123412%26clientversion%3d234134%26mobile%3d122344545%26deviceid%3dIOS
     * posttime:用户操作的时间戳
     * mobile:业务系统账号的手机号，国家代码-手机号。不需要+号
     * deviceid :mac 地址或者设备唯一标识
     * clientversion :用户操作的客户端版本
     * 把值为非空的信息用key=value进行拼接，再进行urlencode
     * urlencode(posttime=xx& mobile =xx&deviceid=xx)
     *
     * @return array|mixed
     * @throws \think\Exception\DbException
     */
    public static function sendRedPack($act_name, $total_amount, $total_num, $website_id, $remark, $wishing, $re_openid, $scene_id = '', $risk_info = '')
    {
        $website_model = new WebSiteModel();
        $website_info = $website_model::get($website_id);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $total_amount *= 100;
        $WxPayConfig = new WxPayConfig($website_id);
        $pars = [];
        $pars['act_name'] = $act_name;
        $pars['client_ip'] = get_ip();
        $pars['mch_billno'] = $WxPayConfig->MCHID . date('Ymd') . self::getNonceStr(10,'0123456789');
        $pars['mch_id'] = $WxPayConfig->MCHID;
        $pars['nonce_str'] = self::getNonceStr(32);
        $pars['remark'] = $remark;
        $pars['re_openid'] = $re_openid;
        $pars['risk_info'] = $risk_info;
        $pars['send_name'] = $website_info['mall_name'];
        $pars['total_amount'] = $total_amount;
        $pars['total_num'] = $total_num;
        $pars['wishing'] = $wishing;
        $pars['wxappid'] = $WxPayConfig->appid;
        if ($total_amount > 20000 || $total_amount < 100) {
            // 大于200元或者小于1元 需要场景id
            $pars['scene_id'] = $scene_id;
        }

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            if (!empty($v)){
                $string1 .= $k . '=' . $v . '&';
            }
        }

        $string1 .= 'key=' . $WxPayConfig->key;
        $sign = strtoupper(md5($string1));
        $xml = "<xml>
                <sign><![CDATA[{$sign}]]></sign>
                <mch_billno><![CDATA[{$pars['mch_billno']}]]></mch_billno>
                <mch_id><![CDATA[{$WxPayConfig->MCHID}]]></mch_id>
                <wxappid><![CDATA[{$WxPayConfig->appid}]]></wxappid>
                <send_name><![CDATA[{$pars['send_name']}]]></send_name>
                <re_openid><![CDATA[{$re_openid}]]></re_openid>
                <total_amount><![CDATA[{$total_amount}]]></total_amount>
                <total_num><![CDATA[1]]></total_num>
                <wishing><![CDATA[{$wishing}]]></wishing>
                <client_ip><![CDATA[{$pars['client_ip']}]]></client_ip>
                <act_name><![CDATA[{$act_name}]]></act_name>
                <remark><![CDATA[{$remark}]]></remark>
                <scene_id><![CDATA[{$scene_id}]]></scene_id>
                <nonce_str><![CDATA[{$pars['nonce_str']}]]></nonce_str>
                <risk_info></risk_info>
                </xml>";
        $startTimeStamp = self::getMillisecond(); // 请求开始时间
        try {
            $response = self::postXmlCurl($xml, $url, true, '', $website_id);
            if ($response == "微信数字证书未找到") {
                return array(
                    'return_code' => "FAIL",
                    'return_msg' => "微信数字证书未找到"
                );
            }
            if ($response == "数字证书路径报错") {
                return array(
                    'return_code' => "FAIL",
                    'return_msg' => "微信数字证书路径报错"
                );
            }
            $result = WxPayResults::Init($response);
            Log::write("微信红包，result：" . json_encode($result));
            self::reportCostTime($url, $startTimeStamp, $result); // 上报请求花费时间
            return $result;
        } catch (\Exception $e) {
            return array(
                'return_code' => "FAIL",
                'return_msg' => $e->getMessage()
            );
        }
    }

    /**
     *
     * 上报数据， 上报的时候将屏蔽所有异常流程
     *
     * @param string $usrl            
     * @param int $startTimeStamp            
     * @param array $data            
     */
    private static function reportCostTime($url, $startTimeStamp, $data)
    {
        // 如果不需要上报数据
        if (WxPayConfig::REPORT_LEVENL == 0) {
            return;
        }
        // 如果仅失败上报
        if (WxPayConfig::REPORT_LEVENL == 1 && array_key_exists("return_code", $data) && $data["return_code"] == "SUCCESS" && array_key_exists("result_code", $data) && $data["result_code"] == "SUCCESS") {
            return;
        }
        // 上报逻辑
        $endTimeStamp = self::getMillisecond();
        $objInput = new WxPayReport();
        $objInput->SetInterface_url($url);
        $objInput->SetExecute_time_($endTimeStamp - $startTimeStamp);
        // 返回状态码
        if (array_key_exists("return_code", $data)) {
            $objInput->SetReturn_code($data["return_code"]);
        }
        // 返回信息
        if (array_key_exists("return_msg", $data)) {
            $objInput->SetReturn_msg($data["return_msg"]);
        }
        // 业务结果
        if (array_key_exists("result_code", $data)) {
            $objInput->SetResult_code($data["result_code"]);
        }
        // 错误代码
        if (array_key_exists("err_code", $data)) {
            $objInput->SetErr_code($data["err_code"]);
        }
        // 错误代码描述
        if (array_key_exists("err_code_des", $data)) {
            $objInput->SetErr_code_des($data["err_code_des"]);
        }
        // 商户订单号
        if (array_key_exists("out_trade_no", $data)) {
            $objInput->SetOut_trade_no($data["out_trade_no"]);
        }
        // 设备号
        if (array_key_exists("device_info", $data)) {
            $objInput->SetDevice_info($data["device_info"]);
        }
        $objInput->SetUser_ip($_SERVER['REMOTE_ADDR']); // 终端ip
        try {
            self::report($objInput);
        } catch (WxPayException $e) {
            // 不做任何处理
        }
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml
     *            需要post的xml数据
     * @param string $url
     *            url
     * @param bool $useCert
     *            是否需要证书，默认不需要
     * @param int $second
     *            url执行超时时间，默认30s
     * @throws WxPayException
     */
    private static function postXmlCurl($xml, $url, $useCert = false, $second = 30,$website_id=0)
    {
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        // 如果有配置代理这里就设置代理
        if (WxPayConfig::CURL_PROXY_HOST != "0.0.0.0" && WxPayConfig::CURL_PROXY_PORT != 0) {
            curl_setopt($ch, CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
            curl_setopt($ch, CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        // curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 严格校验2
                                                         // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
        if ($useCert == true) {
            // 设置证书
            // 使用证书：cert 与 key 分别属于两个.pem文件
            if(file_exists($_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/wap/apiclient_cert.pem') && file_exists($_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/wap/apiclient_key.pem')){
				curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
				curl_setopt($ch, CURLOPT_SSLCERT, $_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/wap/apiclient_cert.pem');
				curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
				curl_setopt($ch, CURLOPT_SSLKEY, $_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/wap/apiclient_key.pem');
			}else{
				curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
				curl_setopt($ch, CURLOPT_SSLCERT, $_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/mp/apiclient_cert.pem');
				curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
				curl_setopt($ch, CURLOPT_SSLKEY, $_SERVER['DOCUMENT_ROOT'].'/upload/'.$website_id.'/0/weixin/mp/apiclient_key.pem');
			}
        }
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        // 运行curl
        // 返回结果
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            if ($error == 58) {
                return "微信数字证书未找到";
//                 throw new WxPayException("微信数字证书未找到");
            } elseif ($error == 52) {
                return "数字证书路径报错";
         //       throw new WxPayException("curl出错，错误码:$error");
            }
        }
    }
    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond()
    {
        // 获取毫秒的时间戳
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode(".", $time);
        $time = $time2[0];
        return $time;
    }
}

