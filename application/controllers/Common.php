<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/17
 * Time: 18:52
 */
class CommonController extends \Yaf\Controller_Abstract{
    /**
     * 加密参数验证
     */
    public function init(){
        $encrypt = $this->getRequest()->getQuery("encrypt");
        $productId = $this->getRequest()->getQuery("productId");
        if(!empty($encrypt)){
            $org = Encypt::decrypt($encrypt,$productId);
            if(empty($org)){
                $this->errorOutput();
            }
            $orgArr = [];
            foreach(explode("&",$org) as $k => $v){
                $pos = strpos($v,"=");
                $key = substr($v,0,$pos);
                $value = substr($v,$pos+1);
                $orgArr[$key] = $value;
            }
            foreach($orgArr as $k => $v){
                if($v!=$this->getRequest()->getQuery($k)){
                    $this->errorOutput();
                }
            }
        }
    }

    /**
     *
     */
    public function errorOutput(){
        $this->getResponse()->setBody(json_encode(['status'=>400,'description'=>'error params.'],JSON_UNESCAPED_UNICODE));
        \Yaf\Dispatcher::getInstance()->disableView();
    }
}