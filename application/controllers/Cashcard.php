<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/29
 * Time: 12:25
 */
/**
 * 泰国，越南，印尼三个国家的点卡支付逻辑统一走这里
 */
use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use Yaf\Config\Ini;
use Yaf\Registry;
use service\CashcardService;
class CashcardController extends  CommonController{
    /**
     * 首页
     */
    public function indexAction(){
        $cashCardService = new CashcardService($this->getRequest());
        $result = $cashCardService->index();
        if($cashCardService->getUi() == $cashCardService::NON_UI_PARAM){
            Dispatcher::getInstance()->disableView();
            $this->getResponse()->setBody(juu($result));
        }else{
            if(IS_AJAX){
                Dispatcher::getInstance()->disableView();
                $result['lang'] = json_encode($result['lang']);
                $this->getResponse()->setBody(juu($result));
            }else{
                $this->getView()->assign($result);
            }
        }
    }

    /**
     * 点卡充值响应
     */
    public function responseAction(){
        $localCfg = new Ini(CFG."/config.common.ini");
        date_default_timezone_set(empty($localCfg->timezone)?Registry::get("config")->timezone:$localCfg->timezone);
        if (CT == 'th') {
            $price  = (empty($this->getRequest()->getQuery("price"))?0:$this->getRequest()->getQuery("price")) /100;
        }else{
            $price = trim($this->getRequest()->getQuery("price",0));
        }
        $status = trim($this->getRequest()->getQuery("status"));
        if ($status == 200) {
            $status = "success";
        }else{
            $status = "failed";
        }
        $cardNo = trim($this->getRequest()->getQuery("cardNo"));
        $transactionId = trim($this->getRequest()->getQuery("transactionId"));
        $tipStr = urldecode(trim($this->getRequest()->getQuery("description")));
        $this->display("response");
        $response = $this->getResponse();
        $response->appendBody("<script>modifyResult('$status','$price','$transactionId','$tipStr','$cardNo','".date('Y-m-d H:i:s')."');</script>");
        Dispatcher::getInstance()->disableView();
    }
}