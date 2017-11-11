<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/16
 * Time: 17:22
 */
use Yaf\Controller_Abstract;
use service\WalletService;
class WalletController extends Controller_Abstract{
    /**
     *
     */
    public function indexAction(){
        $walletService = new WalletService($this->getRequest());
        $walletService->run();
        \Yaf\Dispatcher::getInstance()->disableView();
        $this->getResponse()->setBody(json_encode($walletService->_res,JSON_UNESCAPED_UNICODE));
    }
}