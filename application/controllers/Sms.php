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
        if($smsService->_res){
            $this->getResponse()->setBody(json_encode($smsService->_res,JSON_UNESCAPED_UNICODE ));
        }else{
            $smsService->deal();
            if(is_null($smsService->getUi())){
                if(IS_AJAX){
                    Dispatcher::getInstance()->disableView();
                    $response = $this->getResponse();
                    $response->setBody(json_encode($smsService->_res,JSON_UNESCAPED_UNICODE ));
                }else{
                    $this->getView()->assign($smsService->_res);
                }
            }elseif($smsService->getUi()==$smsService::NON_UI_PARAM){
                //无UI界面
                Dispatcher::getInstance()->disableView();
                $response = $this->getResponse();
                $response->setBody(json_encode($smsService->_res,JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * 华为通道生成短信内容
     */
    public function genSmsContentAction(){
        Dispatcher::getInstance()->disableView();
        $smsService = new SmsService($this->getRequest());
        if($smsService->_res){
            $this->getResponse()->setBody(json_encode($smsService->_res,JSON_UNESCAPED_UNICODE));
        }else{
            $res = $smsService->huawei();
            if($res['status'] == 201&&!empty($res['shortCode'])&&!empty($res['smsContent'])){
                //发短信
                $hrefSessionName = $smsService->getTransactionId()."_href";
                if(\Yaf\Session::getInstance()->has($hrefSessionName)){
                    $res['href'] = \Yaf\Session::getInstance()->get($hrefSessionName);
                }else{
                    $href = "sms:".$res['shortCode'].getOs()."body=".$res['smsContent'];
                    $res['href'] = $href;
                    \Yaf\Session::getInstance()->set($hrefSessionName,$href);
                }
            }elseif($res['status'] == 201&&empty($res['shortCode'])&&empty($res['smsContent'])){
                //验证码
                $res['href'] = '';
            }
            $this->getResponse()->setBody(json_encode($res,JSON_UNESCAPED_UNICODE));
        }
    }
}