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
    protected $encrypt = null;

    const NON_UI_PARAM = 'none';//无UI的控制参数
    /**
     * CommonService constructor.
     * @param Request_Abstract $request_Abstract
     */
    public function __construct($data)
    {
        //echo \Encypt::encrypt("productId=1&transactionId=231412345&price=5000&ui=none",1);die;
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
        try{
            if(method_exists($this,"validata")){
                $this->validata();
            }
            if(!empty($this->getEncrypt())){
                $org = \Encypt::decrypt($this->getEncrypt(),$this->getProductId());
                if(empty($org)){
                    throw new \ParamsException("encrypt error.",400);
                }
                $orgArr = [];
                foreach(explode("&",$org) as $k => $v){
                    $pos = strpos($v,"=");
                    $key = substr($v,0,$pos);
                    $value = substr($v,$pos+1);
                    $orgArr[$key] = $value;
                }
                foreach($orgArr as $k => $v){
                    $name = "get".ucfirst($k);
                    if($v!=$this->$name()){
                        throw new \ParamsException("encrypt error.",400);
                    }
                }
            }
        }catch (\ParamsException $e){
            $log = ['status'=>$e->getCode(),'description'=>$e->getMessage()];
            $log['request'] = $requestData;
            \Logs::debug("params_error.".date('Ymd'))->addError(get_called_class()."请求参数错误",$log);
            exit(juu(['status'=>$e->getCode(),'description'=>$e->getMessage()]));
        }
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

    /**
     * @return null
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param null $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * @return null
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param null $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return null
     */
    public function getEncrypt()
    {
        return $this->encrypt;
    }

    /**
     * @param null $encrypt
     */
    public function setEncrypt($encrypt)
    {
        $this->encrypt = $encrypt;
    }
}