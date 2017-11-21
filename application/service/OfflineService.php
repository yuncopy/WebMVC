<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/11
 * Time: 16:30
 */
namespace service;
use Yaf\Config\Ini;
use Yaf\Registry;
use Yaf\Request_Abstract;

class OfflineService extends CommonService{
    private $price = null;//价格，原有版本price参数为pricelist，兼容该参数。
    private $propsName = 'propsName';//道具名称
    private $promotionId = 1000;//渠道id
    private $ui = null;//通过该参数来决定是否使用web ui，该参数只有一个值：none。如果ui=none，则直接返回payment code
    private $payType = null;//支付方式，支持atm,otc（便利店）。如果ui=none,那么该参数必填；
    private $bankType = 'permata';//银行类型。如果payType=atm，那么bankType等于其中之一：permata,mandiri,bca,bni。如果ui=none,该参数选填，默认是permata。
    private $redirect_url = null;//重定向URL，支付结果重定向到该URL，带上transactionId,price,status,description
    private $pre_page = null;//用户点击返回会跳转该url，不携带任何参数
    private $customerId = NOW;//用户ID
    private $msisdn = '08181234567';//手机号
    protected $map = [
        'pricelist'=>'price',
        'provider'=>'payType',
    ];
    private $_all_pay_type = ['atm','otc'];
    private $_all_bank_type = ['permata'=>1,'mandiri'=>1,'bni'=>3,'other'=>1];
    private $_hidden_blue_pay_log_productId = [812,622,518];//需要隐藏bluepay_logo的产品id
    private $paymentCode;
    private $cardNo;

    /**
     * 首页
     * @return array
     */
    public function index(){
        if($this->getUi() == self::NON_UI_PARAM){
            return $this->offline();
        }else{
            return [
                "payType"=>$this->getPayType(),
                "productId"=>$this->getProductId(),
                "promotionId"=>$this->getPromotionId(),
                "transactionId"=>$this->getTransactionId(),
                "price"=>$this->getPrice(),
                "customerId"=>$this->getCustomerId(),
                "propsName"=>$this->getPropsName(),
                "bankType"=>$this->getBankType(),
                "msisdn"=>$this->getMsisdn(),
                'isShowLogo'=>$this->isShowLogo(),
                'offlineObj'=>$this->checkIsTaxInclusive(),
                'prePage'=>$this->getPre_page(),
                'redirectUrl'=>$this->getRedirect_url()
            ];
        }
    }

    /**
     * 数据验证
     * @return bool
     * @throws \ParamsException
     */
    protected function validata(){
        if(!in_array(CT,['in','test'])){
            throw new \ParamsException("error country.",400);
        }
        if(empty($this->getProductId())){
            throw new \ParamsException("parameter error [productId]",400);
        }
        if(empty($this->getPrice())){
            throw new \ParamsException("parameter error [price]",400);
        }
        if($this->getPrice() < 10000 ){
            throw new \ParamsException("price error,is too low.",400);
        }
        if ($this->getPrice() > 100000000){
            throw new \ParamsException("price error,is too high.",400);
        }
        if(empty($this->getTransactionId())){
            throw new \ParamsException("parameter error [transactionId]",400);
        }
        if($this->getUi() == self::NON_UI_PARAM){
            if(empty($this->getPayType())){
                throw new \ParamsException("parameter error [payType]",400);
            }
            //atm必须要填写手机号码，默认给的手机号码是因为
            if($this->getPayType() == 'atm'){
                $taxInclusive = json_decode($this->checkIsTaxInclusive());
                if($taxInclusive->isStatic == 1){
                    if($this->getMsisdn() == '08181234567'){
                        $this->setMsisdn(null);
                        if(empty($this->getMsisdn())&&$taxInclusive->isStatic==1){
                            throw new \ParamsException("parameter error [msisdn]",400);
                        }
                    }
                }
            }
        }
        return true;
    }
    /**
     * 手续费
     * @return string
     */
    private function checkIsTaxInclusive(){
        $cpsIdStr = \SysConfPropertiesModel::where('key','cp.payfee.list')->pluck('value');
        $producerId = \ProductInfoModel::find($this->getProductId(),['producer_id'])->producer_id;
        //直连
        $objChannel = \SysConfPropertiesModel::where('key','offline.atm.product')->pluck('value');
        //二手通道
        $objIsBluePay = \SysConfPropertiesModel::where('key','offline.atm.channel')->pluck('value');
        //静态
        $objFun = \SysConfPropertiesModel::where('key','offline.atm.static.producers')->pluck('value');
        $offlineObj = new class{
            public $atmFee = 4500;//银行侧手续费，应该使用va更恰当
            public $otcFee = 5500;
            public $isStatic = 0;
            public $channel = 1;
        };
        if ($cpsIdStr) {
            $queryChannel = explode(",", $objChannel);
            if (in_array($this->getProductId(), $queryChannel)) {
                $offlineObj -> channel = 1;
            }else{
                $offlineObj -> channel = 0;
            }
            $objFun = explode(",", $objFun);
            if (!empty($objFun) &&in_array($producerId, $objFun)) {
                $offlineObj -> isStatic = 1;
            }
            $cps = explode(",",$cpsIdStr);
            if (in_array($producerId, $cps)) {
                $offlineObj -> atmFee = 0;
                $offlineObj -> otcFee = 0;
            }
            return json_encode($offlineObj);
        }
    }

    /**
     * @return array|mixed
     */
    public function offline(){
        $host = Registry::get("config")->country->host[CT];
        $path = $host.'/charge/payByOffline/doPayByOffline?';
        $str = 'provider='.$this->getPayType();
        $str .= '&msisdn='.$this->getMsisdn();
        $str .= '&productId='.$this->getProductId();
        $str .= '&propsName='.$this->getPropsName();
        $str .= '&promotionId='.$this->getPromotionId();
        $str .= '&customerId='.$this->getCustomerId();
        $str .= '&transactionid='.$this->getTransactionId();
        $str .= '&price='.$this->getPrice();
        $str .= '&bankType='.$this->_all_bank_type[$this->getBankType()];
        $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
        $hash =md5($str.$key);
        $encrypt = '&encrypt='.$hash;
        $url = $path.$str.$encrypt;
        $resJosn = $this->httpClient()->request("GET",$url)->getBody();
        $result = json_decode($resJosn,true);
        \Logs::debug("offline")->addInfo("",["request"=>(array) $this,"result"=>$result]);
        if ($result["status"] == "201"||$result["status"] == "200") {
            return $result;
        }
        $langCfg = new Ini(CFG."/config.lang.ini");
        $result = ['status'=>$result['status'],'description'=>$langCfg->get($result['status'])];
        return $result;
    }

    /**
     * @return array
     */
    public function test(){
        return [
            'productId'=>$this->getProductId(),
            'price'=>$this->getPrice(),
            'transactionId'=>$this->getTransactionId(),
            'payType'=>$this->getPayType(),
            'paymentCode'=>$this->getPaymentCode(),
        ];
    }

    /**
     * 测试
     */
    public function testOffline(){
        if(empty($this->getPaymentCode())){
            return ['status'=>400,'description'=>"paymentCode 不能为空"];
        }
        $data['productId'] = $this->getProductId();
        $data['provider'] = $this->getPayType();
        $data['paymentCode'] = $this->getPaymentCode();
        $data['price'] = $this->getPrice();
        $data['cardNo'] = $this->getCardNo();
        $path = 'http://120.76.101.146:8160/charge/test/testOfflineOrderPay?';
        $str = http_build_query($data);
        $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
        $hash =md5($str.$key);
        $encrypt = '&encrypt='.$hash;
        $url = $path.$str.$encrypt;
        \Logs::debug("testOfflinePay")->addInfo("银行offline测试",['url'=>$url]);
        $resJosn = $this->httpClient()->request("GET",$url)->getBody();
        $result = json_decode($resJosn,true);
        if ($result["status"] == "200") {
            return $result;
        }
        if(isset($result['error'])){
            return ['status'=>$result['error']['code'],'description'=>(new Ini(CFG."/config.lang.ini"))->get($result['error']['code'])];
        }
        return ['status'=>$result['status'],'description'=>(new Ini(CFG."/config.lang.ini"))->get($result['status'])];
    }
    /**
     * 判断是否显示bluepay_logo
     * @return bool
     */
    private function isShowLogo(){
        if(in_array($this->getProductId(),$this->_hidden_blue_pay_log_productId)){
            return false;
        }
        return true;
    }
    /**
     * @return mixed
     */
    public function getPre_page()
    {
        return $this->pre_page;
    }

    /**
     * @param mixed $pre_page
     */
    public function setPre_page($pre_page)
    {
        $this->pre_page = $pre_page;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getPropsName()
    {
        return $this->propsName;
    }

    /**
     * @param mixed $propsName
     */
    public function setPropsName($propsName)
    {
        $this->propsName = $propsName;
    }

    /**
     * @return mixed
     */
    public function getPromotionId()
    {
        return $this->promotionId;
    }

    /**
     * @param mixed $promotionId
     */
    public function setPromotionId($promotionId)
    {
        $this->promotionId = $promotionId;
    }

    /**
     * @return mixed
     */
    public function getUi()
    {
        return $this->ui;
    }

    /**
     * @param mixed $ui
     */
    public function setUi($ui)
    {
        $this->ui = $ui;
    }

    /**
     * @return mixed
     */
    public function getPayType()
    {
        return $this->payType;
    }

    /**
     * @param mixed $payType
     */
    public function setPayType($payType)
    {
        $this->payType = $payType;
    }

    /**
     * @return mixed
     */
    public function getBankType()
    {
        return $this->bankType;
    }

    /**
     * @param mixed $bankType
     */
    public function setBankType($bankType)
    {
        $this->bankType = $bankType;
    }

    /**
     * @return mixed
     */
    public function getRedirect_url()
    {
        return $this->redirect_url;
    }

    /**
     * @param mixed $redirect_url
     */
    public function setRedirect_url($redirect_url)
    {
        $this->redirect_url = $redirect_url;
    }
    /**
     * @return null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param null $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return null
     */
    public function getMsisdn()
    {
        return $this->msisdn;
    }

    /**
     * @param null $msisdn
     */
    public function setMsisdn($msisdn)
    {
        $this->msisdn = $msisdn;
    }
    /**
     * @return mixed
     */
    public function getPaymentCode()
    {
        return $this->paymentCode;
    }

    /**
     * @param mixed $paymentCode
     */
    public function setPaymentCode($paymentCode)
    {
        $this->paymentCode = $paymentCode;
    }
    /**
     * @return mixed
     */
    public function getCardNo()
    {
        return $this->cardNo;
    }

    /**
     * @param mixed $cardNo
     */
    public function setCardNo($cardNo)
    {
        $this->cardNo = $cardNo;
    }

}