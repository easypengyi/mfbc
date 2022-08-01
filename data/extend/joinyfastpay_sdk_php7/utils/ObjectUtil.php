<?php

namespace joinpay;


class ObjectUtil
{
    /**
     * 把json格式的字符串参数值赋值到对象属性中去
     * @param object $obj
     * @param string $paramJson
     * @param int $jsonDept
     */
    public static function fillProperties(object &$obj, string $paramJson, $jsonDept = 2){
        $arr = json_decode($paramJson, true, $jsonDept);

        if(!$arr || !is_array($arr)) {
            return;
        }

        $isMagicSetExist = method_exists($obj, "__set");//魔术方法是否存在
        foreach($arr as $key => $value) {
            if(! property_exists($obj, $key)){
                continue;
            }

            if($isMagicSetExist){
                $obj->$key = $value;
            }else{
                $method = "set" . ucfirst($key);

                if(! method_exists($obj, $method)){
                    $method = "set";
                    $fArr = explode("_", $key);
                    foreach($fArr as $idx => $fName){
                        $method .= ucfirst($fName);
                    }
                }
                $obj->$method($value);
            }
        }
    }
}