<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/27
 * Time: 18:47
 */
class QrController extends CommonController{
    /**
     * 生成二维码
     */
    public function indexAction(){
        $qrService = new \service\QrService($this->getRequest());
        $qrService->create();
        $this->getResponse()->setBody($qrService->getImg());
        \Yaf\Dispatcher::getInstance()->disableView();
    }
}