<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/9
 * Time: 17:19
 */
/**
 * 泰国，越南，印尼的网银支付
 */
use Yaf\Dispatcher;
class BankController extends CommonController{
    /**
     * 首页
     */
    public function indexAction(){
        $bankService = new \service\BankService($this->getRequest());
        $result = $bankService->index();
        if($result['location']){
            //越南银行使用vtc渠道会跳转到charge的url
            Dispatcher::getInstance()->disableView();
            $this->redirect($result['location']);
        }else{
            $this->display("index_".CT,$result);
            Dispatcher::getInstance()->disableView();
        }
    }
    /**
     * 印尼跟越南银行表单提交
     */
    public function submitAction(){
        $bankService = new \service\BankService($this->getRequest());
        $result = $bankService->submit();
        $this->getResponse()->setBody(json_encode($result));
        Dispatcher::getInstance()->disableView();
    }
    /**
     * 印尼跟越南银行响应页面
     */
    public function responseAction(){
        $bankService = new \service\BankService($this->getRequest());
        $data = $bankService->response($this->getRequest());
        $this->display("response_".CT,$data);
        Dispatcher::getInstance()->disableView();
    }
}