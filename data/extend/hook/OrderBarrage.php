<?php
// +----------------------------------------------------------------------
// | 订单下单，进行弹幕操作（写入redis队列，存入订单弹幕虚拟表）
// +----------------------------------------------------------------------
// | Copyright (c) 微商来
// +----------------------------------------------------------------------
// | Author: sgw
// +----------------------------------------------------------------------

namespace data\extend\hook;

use addons\orderbarrage\server\OrderBarrage as OrderBarrageServer;
use data\model\UserModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderModel;

class OrderBarrage
{
    const ORDER_BARRAGE_VIRTUAL_QUEUE = 'order_barrage_virtual_queue';//虚拟
    const ORDER_BARRAGE_TRUE_QUEUE = 'order_barrage_true_queue';//真实
    const ORDER_BARRAGE_CONFIG = 'order_barrage_config';//配置redis缓存
    const ORDER_BARRAGE_CONFIG_RULE_STATE = 'order_barrage_config_rule_state';//rule是否改变状态
    
    protected $virtual_queue;
    protected $true_queue;
    protected $config;
    protected $rule_state;
    protected $remove_time = 600;//过滤10分钟内的队列数据
    
    public function __construct()
    {
    }

//    /**
//     * 下单写入订单弹幕队列
//     * @param $params
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function postOrderBarrageTrueQueue($params = null)
//    {
//        $this->virtual_queue = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_VIRTUAL_QUEUE;//eg 2_0_order_barrage_virtual_queue
//        $this->true_queue = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_TRUE_QUEUE;//eg 2_0_order_barrage_true_queue
//        $this->config = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_CONFIG;//eg 2_0_order_barrage_config
//
//        //先判断订单弹幕是否开启
//        $barrage = new OrderBarrageServer();
//        $config = $barrage->getOrderBarrageConfigOfRedis();
//
//        if (!$config || $config['state'] == 0) {
//            return;
//        }
//
//        //查询订单信息
//        $orderModel = new VslOrderModel();
//        $order = $orderModel::get(1);
//        $order_info = $order->order_goods()->select();
//        $goods_name = $order_info[0]['goods_name'];//暂时取多个商品中第一个
//
//        //查询用户信息
//        $user = new UserModel();
//        $user_condition = [
//            'website_id' => $params['website_id'],
//            'instance_id' => $params['shop_id'],
//            'uid' => $params['uid']
//        ];
//        $user_info = $user->getInfo($user_condition, 'nick_name, user_name, user_tel, user_headimg');
//        $user_name = $user_info['nick_name'] ? : ($user_info['user_name'] ?: $user_info['user_tel']);//用户名
//        $user_headimg = $user_info['user_headimg'];
//
//        $order_barrage_arr = [
//            'website_id' => $params['website_id'],
//            'shop_id' => $params['shop_id'],
//            'user_name' => $user_name,
//            'header' => $user_headimg,
//            'goods_name' => $goods_name,
//            'state' => 2,
//            'place_order_time' => time()
//        ];
//        $redis = connectRedis();
//        $redis->lpush($this->true_queue, serialize($order_barrage_arr));//左压入真实数据队列，右出
//
///*        $type = $config['type'];//订单弹幕类型 1真实数据 2 虚拟数据 3真实+虚拟
//        if ($type == 1) {
//            //写入队列
//            $redis->lpush($this->true_queue, serialize($order_barrage_arr));//左压入真实数据队列，右出
//            //存入数据库
//            $barrage->postOrderBarrageVirtual($order_barrage_arr);
//        } else if($type == 3){//获取(虚拟 + 真实)数据
//            $redis->lpush($this->true_virtual_queue, serialize($order_barrage_arr));//左压入真实数据队列，右出
//            $redis->rpush($this->virtual_queue, serialize($order_barrage_arr));//右压入虚拟数据队列,右出（真实数据先出队列）
//            $barrage->postOrderBarrageVirtual($order_barrage_arr);
//        }*/
//    }
    
    /**
     * 下单写入订单弹幕队列
     * @param null $params
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  postOrderBarrageTrueQueue($params = null)
    {
        if (!$params['website_id'] || !$params['uid']|| !$params['order_id']){return;}
		if(!getAddons('orderbarrage',$params['website_id'])){
            return;
        }
        $redis = connectRedis();
        if(!config('is_high_powered')){
            try{
                $this->virtual_queue = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_VIRTUAL_QUEUE;//eg 2_0_order_barrage_virtual_queue
                $this->true_queue = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_TRUE_QUEUE;//eg 2_0_order_barrage_true_queue
                $this->config = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_CONFIG;//eg 2_0_order_barrage_config
                $this->rule_state = $params['website_id'] .'_'.$params['shop_id'] .'_'.self::ORDER_BARRAGE_CONFIG_RULE_STATE;
                //先判断订单弹幕是否开启
                $barrage = new OrderBarrageServer($params['website_id'],$params['shop_id']);
                $config = $barrage->getCurrentOrderBarrageRuleOfRedis();
                # 没有配置、关闭、或者弹幕类型为虚拟则不写入真实队列
                if (!$config || $config['state'] == 0) {return;}
                if (!$config['rule']){return;}

                //真实下单，写入真实队列（先查询出真实队列，过滤前10分钟的订单数据
                $trueData = [];
                $redisRes = $redis->get($this->true_queue);
                if ($redisRes){
                    $trueData = unserialize($redisRes);
                    if ($trueData){
                        //筛选数据
                        $trueData = $this->removeData($trueData);
                    }
                }


                //查询用户信息
                $user = new UserModel();
                $user_condition = [
                    'website_id' => $params['website_id'],
                    'uid' => $params['uid']
                ];
                $user_info = $user->getInfo($user_condition, 'nick_name, user_name, user_tel, user_headimg');
                $user_name = $user_info['nick_name'] ? : ($user_info['user_name'] ?: $user_info['user_tel']);//用户名
                $user_headimg = $user_info['user_headimg'];

                //查询订单信息
                $orderGoodsModel = new VslOrderGoodsModel();
                $orderGoods = $orderGoodsModel->getInfo(['order_id'=> $params['order_id']],'goods_name');
                $goods_name = $orderGoods['goods_name'] ?: '商品';//TODO 暂时取多个商品中第一个

                $order_barrage_arr = [
                    'website_id' => $params['website_id'],
                    'shop_id' => $params['shop_id'],
                    'user_name' => $user_name ?: '佚名',
                    'header' => $user_headimg,
                    'goods_name' => $goods_name,
                    'state' => 2,
                    'place_order_time' => time()-3,
                    'uid' => $params['uid']
                ];
//            $redisRes = $redis->get($this->true_queue);
//            $trueData = unserialize($redisRes);
//            $trueData = $trueData ?: [];
                $trueData = is_array($trueData) ?$trueData : [];
                array_push($trueData, $order_barrage_arr);
                $redis->set($this->true_queue, serialize($trueData));
                //写入虚拟数据表
                $barrage->postOrderBarrageVirtual($order_barrage_arr) ;
//
//            # 清除真实队列
//            $redis = connectRedis();
//            if ($config['type'] == 2){
//                $redis->set($this->true_queue, null);
//                return;
//            }
//            //查询订单信息
//            $orderModel = new VslOrderModel();
//            $order = $orderModel::get(1);
//            $order_info = $order->order_goods()->select();
//            $goods_name = $order_info[0]['goods_name'];//暂时取多个商品中第一个
//
//            //查询用户信息
//            $user = new UserModel();
//            $user_condition = [
//                'website_id' => $params['website_id'],
//                'instance_id' => $params['shop_id'],
//                'uid' => $params['uid']
//            ];
//            $user_info = $user->getInfo($user_condition, 'nick_name, user_name, user_tel, user_headimg');
//            $user_name = $user_info['nick_name'] ? : ($user_info['user_name'] ?: $user_info['user_tel']);//用户名
//            $user_headimg = $user_info['user_headimg'];
//
//            $order_barrage_arr = [
//                'website_id' => $params['website_id'],
//                'shop_id' => $params['shop_id'],
//                'user_name' => $user_name,
//                'header' => $user_headimg,
//                'goods_name' => $goods_name,
//                'state' => 2,
//                'place_order_time' => time(),
//                'uid' => $params['uid']
//            ];
//
//            $redisRes = $redis->get($this->true_queue);
//            $trueData = unserialize($redisRes);
//            $trueData = $trueData ?: [];
//            array_push($trueData, $order_barrage_arr);
//            $redis->set($this->true_queue, serialize($trueData));
//            //写入虚拟数据表
//            $barrage->postOrderBarrageVirtual($order_barrage_arr) ;
            } catch (\Exception $e){
                debugLog($e->getMessage(),'弹幕下单错误');
            }
        }else{
            $barrage = new OrderBarrageServer();
            $current_res = $barrage->getCurrentTimeOfOrderBarrageRule($params['website_id']);
            $start_key = $current_res['rule']['start_date'];
            $end_key = $current_res['rule']['end_date'];
            $redis_real_key = 'real_'.$current_res['rule']['rule_id'].'_'.$start_key.'_'.$end_key;//real_123_1618367940_1618390800
            //查询用户信息
            $user = new UserModel();
            $user_condition = [
                'website_id' => $params['website_id'],
                'uid' => $params['uid']
            ];
            $user_info = $user->getInfo($user_condition, 'nick_name, user_name, user_tel, user_headimg');
            $user_name = $user_info['nick_name'] ? : ($user_info['user_name'] ?: $user_info['user_tel']);//用户名
            $user_headimg = $user_info['user_headimg'];

            //查询订单信息
            $orderGoodsModel = new VslOrderGoodsModel();
            $orderGoods = $orderGoodsModel->getInfo(['order_id'=> $params['order_id']],'goods_name');
            $goods_name = $orderGoods['goods_name'] ?: '商品';//TODO 暂时取多个商品中第一个
            $order_barrage_arr = [
                'website_id' => $params['website_id'],
                'shop_id' => $params['shop_id'],
                'user_name' => $user_name ?: '佚名',
                'header' => $user_headimg,
                'goods_name' => $goods_name,
                'state' => 2,
                'place_order_time' => time()-3,
                'uid' => $params['uid']
            ];
            //写入虚拟数据表
            $barrage->postOrderBarrageVirtual($order_barrage_arr);
            $redis->lpush($redis_real_key, json_encode($order_barrage_arr));//入队
        }
    }

    /**
     * 过滤数据
     * @param array $redis_data
     * @return array
     */
    public function removeData (array $redis_data)
    {
        $new_redis_data = [];
        foreach ($redis_data as $key => $val){
            if(time() - $this->remove_time < $val['place_order_time']){
                $new_redis_data[] = $val;
            }
        }
        return $new_redis_data;
    }
}
