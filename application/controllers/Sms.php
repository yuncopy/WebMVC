<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/13
 * Time: 18:28
 */
use service\SmsService;
use Yaf\Dispatcher;
class SmsController extends CommonController{
    /**
     * 短代充值
     */
    public function indexAction(){
        $smsService = new SmsService($this->getRequest());
        $smsService->deal();
        if(is_null($smsService->getUi())){
            if(IS_AJAX){
                Dispatcher::getInstance()->disableView();
                $response = $this->getResponse();
                $response->setBody(juu($smsService->_res));
            }else{
                $this->getView()->assign($smsService->_res);
            }
        }elseif($smsService->getUi()==$smsService::NON_UI_PARAM){
            //无UI界面
            Dispatcher::getInstance()->disableView();
            $response = $this->getResponse();
            $response->setBody(juu($smsService->_res));
        }
    }

    /**
     * 华为通道生成短信内容
     */
    public function genSmsContentAction(){
        Dispatcher::getInstance()->disableView();
        $smsService = new SmsService($this->getRequest());
        $res = $smsService->genSmsConten();
        $this->getResponse()->setBody(juu($res));
    }
}