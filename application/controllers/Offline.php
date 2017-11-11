<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/11
 * Time: 16:28
 */
/**
 * 印尼VA&OTC
 */
use Yaf\Controller_Abstract;
use Yaf\Dispatcher;
use service\CommonService;
class OfflineController extends Controller_Abstract{
    /**
     * VA&OTC
     */
    public function indexAction(){
        $offlineService = new \service\OfflineService($this->getRequest());
        $result = $offlineService->index();
        if($offlineService->getUi() == $offlineService::NON_UI_PARAM){
            Dispatcher::getInstance()->disableView();
            $this->getResponse()->setBody(json_encode($result,JSON_UNESCAPED_UNICODE));
        }else{
            $this->getView()->assign($result);
        }
    }

    /**
     * VA&OTC
     */
    public function offlineAction(){
        $offlineService = new \service\OfflineService($this->getRequest());
        $result = $offlineService->offline();
        $respons = $this->getResponse();
        $respons->setBody(json_encode($result,JSON_UNESCAPED_UNICODE));
        Dispatcher::getInstance()->disableView();
    }

    /**
     * ATM
     */
    public function atmAction(){
        $this->getView()->assign([
            'isDirect'=>$this->getRequest()->getQuery("isDirect",0),
            'fee'=>$this->getRequest()->getQuery("atmFee",0),
            'price'=>$this->getRequest()->getQuery("price",0)+$this->getRequest()->getQuery("atmFee"),
            'productId'=>$this->getRequest()->getQuery("atmFee",0),
            'prePage'=>$this->getRequest()->getQuery("pre_page",''),
            'redirect_url'=>$this->getRequest()->getQuery("redirect_url",''),
        ]);
    }

    /**
     * OTC
     */
    public function otcAction(){
        $this->getView()->assign([
            'fee'=>$this->getRequest()->getQuery("otcFee",0),
            'productId'=>$this->getRequest()->getQuery("otcFee",0),
            'price'=>$this->getRequest()->getQuery("price",0)+$this->getRequest()->getQuery("otcFee",0),
            'prePage'=>$this->getRequest()->getQuery("pre_page",''),
            'redirect_url'=>$this->getRequest()->getQuery("redirect_url",''),
        ]);
    }
}