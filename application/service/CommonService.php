<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/29
 * Time: 15:33
 */
namespace service;
use GuzzleHttp\Client;
use LancerHe\Crypt\TripleDES;
use Yaf\Registry;
use Yaf\Request_Abstract;

class CommonService {
    protected $productId = null;//商品ID
    protected $transactionId = null;//交易号

    const NON_UI_PARAM = 'none';//无UI的控制参数
    /**
     * CommonService constructor.
     * @param Request_Abstract $request_Abstract
     */
    public function __construct($data)
    {
        if($data instanceof Request_Abstract ){
            $requestData = $data->getQuery();
        }else{
            $requestData = $data;
        }
        if(property_exists(get_called_class(),'map')){
            $map = $this->map;
        }else{
            $map = [];
        }
        array_walk($requestData,function($item,$key,$map) use($requestData){
            if(array_key_exists($key,$map)&&!empty($map[$key])){
                $act  = "set". ucfirst($map[$key]);
                $this->$act($requestData[$key]);
            }elseif(property_exists($this,$key)&&!empty($key)){
                $act = "set".ucfirst($key);
                $this->$act(empty($item)?null:$item);
            }
        },$map);
    }
    /**
     * http客户端
     * @return Client
     */
    public static function httpClient(){
        if(Registry::has('httpclient')){
            return Registry::get('httpclient');
        }
        Registry::set('httpclient',new Client());
        return Registry::get('httpclient');
    }
    /**
     * 加密解密
     * @return TripleDES
     */
    public static function crypt(){
        if(Registry::has('crypt')){
            return Registry::get('crypt');
        }
        Registry::set('crypt',new TripleDES());
        return Registry::get('crypt');
    }
    /**
     * 反射
     * @param $class
     * @return \ReflectionClass
     */
    public static function reflection($class){
        $name = $class."_reflection";
        if(Registry::has($name)){
            return Registry::get($name);
        }
        Registry::set($name,new \ReflectionClass($class));
        return Registry::get($name);
    }
}