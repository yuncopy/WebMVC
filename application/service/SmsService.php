<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/19
 * Time: 16:15
 */
/**
 * 短代支付服务
 * 命令字：2跟7,2命令字是cp传多来的，这边会拿2命令字去请求charge生成7命令字
 */
namespace service;
use Yaf\Config\Ini;
use Yaf\Registry;
use Yaf\Session;

class SmsService extends CommonService{
    private $ui = null;//是否有UI
    private $telco = null;//运营商
    private $price = null;//商品价格,新的参数
    private $promotionId = 1000;//渠道ID
    private $propsName = 'propsName';//道具名称
    private $keyword = null;//关键字,CP传过来的
    private $msisdn = null;//用戶手機號碼，如果是华为通道的话，需要用户填写手机号
    const SMS_CP_CMD = [2,6];//短代CP传递过来的命令字是2,银行CP传递过来的命令字是6,charge对应关系是2=>7，4=>8
    private $_in_use_huawei_channel_productId = [1,690,716,1094];//印尼使用华为通道的产品ID
    private $_hidden_blue_pay_log_productId = [812,622,518];//需要隐藏bluepay_logo的产品id
    private $_otp_telcos = ['xl','hutchison','indosat'];//otp运营商
    private $_dcb_telcos = [];
    private $_huawei_sms_telcos = ['telkomsel'];//使用华为通道且发短信的运营商
    private $_huawei_vcode_telcos = [];//使用华为通道且发验证码的运营商
    private $bank = false;//是否是银行定向过来的
    public $_res = [];//处理结果
    protected $map = ['pricelist'=>'price'];
    /**
     * 处理逻辑
     * 有ui界面：
     * 1.判断是华为通道还是直连
     * 2.如果是直连，则请求charge接口拿到7命令字，然后生成a标签的href链接
     * 3.如果是 华为通道，则请求charge接口拿到7命令字，然后判断是发短信还是验证码（发短信：用户发送给运营商  验证码：运营商发送给用户）两者都是用户点击按钮触发
     *
     */
    public function deal(){
        if(is_null($this->getUi())||$this->getUi()!=self::NON_UI_PARAM){
            if(!$this->isHwChannel()){
                //直连，如果session中已经有数据，则使用session中的数据
                $hrefSessionName = $this->getTelco()."_".$this->getTransactionId()."_href";
                if(Session::getInstance()->has($hrefSessionName)){
                    $this->_res['href'] = Session::getInstance()->get($hrefSessionName);
                }else{
                    $dConnectRes = $this->dConnect();
                    if(!empty($dConnectRes['shortCode'])&&!empty($dConnectRes['smsContent'])&&$dConnectRes['status']==201){
                        $href = "sms:".$dConnectRes['shortCode'].getOs()."body=".$dConnectRes['smsContent'];
                        $this->_res['href'] = $href;
                        Session::getInstance()->set($hrefSessionName,$href);
                    }else{
                        $this->_res = $dConnectRes;
                        return;
                    }
                }
            }
            $this->_res += [
                'os'=>getOs(),
                'keyword'=>$this->getKeyword(),
                'productId'=>$this->getProductId(),
                'transactionId'=>$this->getTransactionId(),
                'price'=>$this->getPrice(),
                'telcos'=>(new Ini(CFG."/config.common.ini"))->get("telcos")->toArray(),
                'telco'=>$this->getTelco(),
                'promotionId'=>$this->getPromotionId(),
                'propsName'=>$this->getPropsName(),
                'ct'=>CT,
                'currency'=>(new Ini(CFG."/config.common.ini"))->get("currency"),
                'lang'=>(new Ini(CFG."/config.lang.ini"))->toArray(),
                'isShowLogo'=>$this->isShowLogo(),
                'isHwChannel'=>$this->isHwChannel(),
                'bank'=>$this->getBank()
            ];
        }else{
            //无UI
            if($this->isHwChannel()){
                //华为通道
                $this->_res = $this->huawei();
            }else{
                //直连
                $this->_res = $this->dConnect();
            }
        }
    }
    /**
     * 参数验证
     * keyword为空的时候，cp肯定出传productId,price,transactionId
     * keyword不为空的时候，cp传的参数就是keyword跟pricelist,keyword=命令字+商品ID+交易号
     * @return bool
     */
    protected function validata(){
        if(empty($this->getKeyword())){
            if(empty($this->getProductId())){
                throw new \ParamsException("productId is require.",400);
            }
            if(empty($this->getTransactionId())){
                throw new \ParamsException("transactionId is require.",400);
            }
        }else{
            //验证命令字
            $cmd = substr($this->getKeyword(), 0,1);
            if(!in_array($cmd,self::SMS_CP_CMD)){
                throw new \ParamsException("Invalid command word.",400);
            }
            //keyword验证成功之后直接设置好产品ID跟交易号,并判断是否是银行
            $this->setBank($cmd==6?true:false);
            $this->setProductId(substr($this->getKeyword(), 1,4));
            $this->setTransactionId(substr($this->getKeyword(), 5,strlen($this->getKeyword())));
        }
        //验证商品价格是否有传递
        if(empty($this->getPrice())){
            throw new \ParamsException("price is require.",400);
        }
        //依赖ui项
        if($this->getUi() == self::NON_UI_PARAM&&!is_null($this->getUi())){
            if(empty($this->getTelco())){
                throw new \ParamsException("telco is require.",400);
            }
            if($this->isHwChannel()&&empty($this->getMsisdn())){
                throw new \ParamsException("invalid msisdn.",400);
            }
        }
        //运营商
        if(!in_array($this->getTelco(),(new Ini(CFG."/config.common.ini"))->get("telcos")->toArray())&&!empty($this->getTelco())){
            throw new \ParamsException("invalid telco.",400);
        }
        return true;
    }
    /**
     * 直连，type=2是短代，tupe=6是银行定向过来的
     */
    private function dConnect($type=2){
        if($this->getBank() == true){
            $type = 6;
        }
        $smsContent = $this->getSmsContent($type);//商品ID长度小于4位的，左边补零到4位,下单使用2命令字
        $shortCode = $this->getShortCode();//获取短码
        return $this->doSmsCreateOrder($smsContent,$shortCode);//下单
    }

    /**
     * 华为通道
     * 走华为通道的话，短码是charge那边的接口提供的
     */
    public function huawei(){
        $beforeHash = "productId=".$this->getProductId()
            ."&telco_name=".$this->getTelco()
            ."&price=".$this->getPrice()
            ."&promotionId=".$this->getPromotionId()
            ."&msisdn=".$this->getMsisdn()
            ."&propsName=".$this->getPropsName()
            ."&transactionId=".$this->getTransactionId();
        //发短信还是验证码：channel=2的时候是发短信 否则就是验证码
        if(in_array($this->getTelco(),$this->_huawei_sms_telcos)){
            $beforeHash = $beforeHash."&channel=2";
        }
        $key = \ProductInfoModel::find($this->productId)->producter->md5Suf;
        $hash =md5($beforeHash.$key);
        $host = Registry::get("config")->country->host[CT];
        $url = $host."/charge/paysBySms/initPayment?".$beforeHash."&encrypt=".$hash;
        $output = CommonService::httpClient()->request("GET",$url);
        $output = $output->getBody();
        $lang = new Ini(CFG."/config.lang.ini");
        try {
            $result = json_decode($output,true);
            \Logs::debug("sms")->addInfo("",[
                "telco"=>$this->getTelco(),
                "price"=>$this->getPrice(),
                "transactionId"=>$this->getTransactionId(),
                "productId"=>$this->getProductId(),
                "output"=>$output,
                "url"=>$url,
                'res'=>$result
            ]);
            if($this->getTelco() == "xl"){
                $description = "Untuk mengakhiri transaksi ini, silahkan balas kode verifikasi yang tertera melalui sms :XXXX";
            }elseif($this->getTelco() == "hutchison"){
                $description = "Untuk mengakhiri transaksi ini, silahkan balas kode verifikasi yang tertera melalui sms :BUY XXX";
            }elseif($this->getTelco() == "indosat"){
                $description = "Untuk mengakhiri transaksi ini, silahkan balas kode verifikasi yang tertera melalui sms :BELI xxx.";
            }
            if (empty($result['status'])) {
                return ['status'=>501,'description'=>$lang[501]];
            }else{
                if ($result['status'] == "201") {
                    //如果是发送短信的，则charge会返回shortcode，如果是发送验证码的，charge只返回状态码跟描述
                    if (in_array($this->getTelco(),$this->_huawei_sms_telcos)) {
                        //短信
                        if (empty($result['status']) || empty($result['shortcode'])) {
                           return ['status'=>600,'description'=>$lang['600']];
                        }else{
                           return ['status'=>$result['status'],'shortCode'=>$result['shortcode'],'smsContent'=>$result['content'],'qr'=>''];
                        }
                    }else{
                        //验证码
                        return ['status'=>$result['status'],'description'=>$description];
                    }
                }else{
                    return ['status'=>$result['status'],'description'=>$lang[$result['status']]];
                }
            }
        } catch (\Exception $e) {
            return ['status'=>501,'description'=>$lang[501]];
        }
    }
    /**
     * 获取短码：shortcode
     * 泰、越、印尼短代支付，每种价格的商品都有不同的短码，但是泰国银行支付，只有一种短码
     * 直连的短码是我们这边配置，华为通道的短码是请求huawei()那个方法获取到短码
     * @return string
     */
    private function getShortCode(){
        //泰国银行的短码只有一个
        if($this->getBank() == true){
            $th_bank_sc = [
                'ais'=>4192034,
                'true'=>4192007,
                'dtac'=>4078005
            ];
            return $th_bank_sc[$this->getTelco()];
        }
        //短代的短码是每种价格的产品有一个短码
        $dm = new Ini(CFG."/config.sc.ini");
        $telco = strtolower($this->getTelco());
        if(CT == 'th'){
            if (in_array($this->getProductId(), $dm->get("boyaa")->get("productId")->toArray())) {
                return $dm->get("boyaa")->$telco->chargelist[$this->price];
            }elseif($telco =='true'&&in_array($this->getProductId(), $dm->get($telco)->get("productIdForApp")->toArray())){
                return $dm->get($telco)->price[$this->getPrice()];
            }else{
                return $dm->get($telco)->price[$this->getPrice()];
            }
        }
        if(CT == 'vn'){
            if(!array_key_exists($this->getPrice(), $dm->get($telco)->toArray())){
                return "";
            }else{
                return "9029";
            }
        }
        if(CT == 'in'){
            if(!array_key_exists($this->getPrice(), $dm->get($telco)->toArray())){
                return "";
            }else{
                return '98888';
            }
        }
        if(CT == 'test'){
            return $dm->get($this->getPrice());
        }
    }
    /**
     * 直连短代下单
     */
    private function doSmsCreateOrder($smsContent,$shortCode){
        if(CT == 'th'){
            $closeProductIdForTrue	= (new Ini(CFG."/config.sc.ini"))->get("true")->get("closeproductId")->toArray();
        }
        $lang = new Ini(CFG."/config.lang.ini");
        $host = Registry::get("config")->country->host[CT];
        $beforeHash = "productId=".$this->getProductId()
            ."&telco=".$this->getTelco()
            ."&price=".$this->getPrice()
            ."&shortcode=".$shortCode
            ."&smsMsg=".$smsContent;
        $key = \ProductInfoModel::find($this->productId)->producter->md5Suf;
        if(CT=='th'&&$this->getTelco() == 'true' && in_array($this->productId,$closeProductIdForTrue)){
            return ['status'=>700,'description'=>str_replace("{}", $this->telco, $lang[700])];
        }
        if (empty($shortCode)) {
            return ['status'=>400,'description'=>"price unsupport"];
        }else{
            $hash =md5($beforeHash.$key);
            $url = $host."/charge/order/generateOrder?".$beforeHash."&encrypt=".$hash;
            $output = CommonService::httpClient()->request("GET",$url);
            $output = $output->getBody();
            try {
                $result = json_decode($output,true);
                if (empty($result['status'])) {
                    return ['status'=>501,'description'=>'Network error.'];
                }else{
                    $preFix = urlencode($this->getPreFix());
                    \Logs::debug("sms")->addInfo("",[
                        "telco"=>$this->getTelco(),
                        "price"=>$this->getPrice(),
                        "sms"=>$smsContent,
                        "productId"=>$this->getProductId(),
                        "status"=>$result['status'],
                        "output"=>$output
                    ]);
                    $qrService = new QrService([
                        'pricelist'=>$this->getPrice(),
                        'keyword'=>$smsContent,
                        'telco'=>$this->getTelco()
                    ]);
                    $qrService->create();
                    $qr_url = $qrService->getImg();
                    $preFix = urldecode($preFix);
                    $status = $result['status'];
                    if ($status == "200")  $status = '201';
                    return ['status'=>$status,'shortCode'=>$shortCode,'smsContent'=>$preFix.$result["orderId"],'qr'=>$qr_url];
                }
            } catch (\Exception $e) {
                return ['status'=>501,'description'=>'Network error.'];
            }
        }
    }
    /**
     * 获取关键字
     */
    private function getPreFix(){
        if(CT == 'th'){
            return "";
        }
        if(CT == 'test'){
            return $this->getPrice()."_";
        }
        $keywords = new Ini(CFG."/config.sc.ini");
        if(CT == 'in'){
            $getIsCpPayVat = \ProductInfoModel::join("indonesia_price_cp","product_info.producer_id","=","indonesia_price_cp.producer_id")
                ->where("product_info.product_id",$this->getProductId())
                ->where("indonesia_price_cp.real_telco",$this->getTelco())
                ->get(["product_info.producer_id"]);
            if($getIsCpPayVat){
                return $keywords[$this->getTelco()."_1"][$this->getPrice()];
            }
        }
        return  $keywords[$this->getTelco()][$this->getPrice()];
    }

    /**
     * 华为通道生成短信内容
     * @return array
     */
    public function genSmsConten(){
        $hrefSessionName = $this->getTransactionId()."_href";
        if(\Yaf\Session::getInstance()->has($hrefSessionName)){
            $res = ['status'=>605,'description'=>(new Ini(CFG."/config.lang.ini"))->get("605")];
        }else{
            $res = $this->huawei();
            if($res['status'] == 201&&!empty($res['shortCode'])&&!empty($res['smsContent'])){
                $href = "sms:".$res['shortCode'].getOs()."body=".$res['smsContent'];
                $res['href'] = $href;
                \Yaf\Session::getInstance()->set($hrefSessionName,$href);
            }elseif($res['status'] == 201&&empty($res['shortCode'])&&empty($res['smsContent'])){
                //验证码
                $res['href'] = '';
            }
        }
        return $res;
    }
    /**
     * 获取下单短信内容，keyword是CP传过来的，keyword第一位：2为短代
     * 这里的短信内容是在调用charge接口下单使用的
     *
     */
    private function getSmsContent($type){
        $productIdFix = $this->addBlank();
        $productId = $productIdFix.$this->getProductId();
        return $type.$productId.$this->getTransactionId();
    }
    /**
     * @return string
     */
    private function addBlank(){
        $productIdFix = "";
        for ($i=strlen($this->getProductId()); $i < 4 ; $i++) {
            $productIdFix= "0".$productIdFix;
        }
        return $productIdFix;
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
     * 是否是华为通道，华为通道是需要用户填写手机号的
     * @return bool
     */
    private function isHwChannel(){
        if(in_array($this->getTelco(),['xl','indosat','hutchison'])||($this->getTelco() == 'telkomsel'&&in_array($this->getProductId(),$this->_in_use_huawei_channel_productId))){
            return true;
        }
        return false;
    }



    /**
     * 是否是DCB（DCB：是指拿到用户手机号码之后直接可以扣费）
     * @return bool
     */
    private function isDcb(){
        if(in_array($this->getTelco(),$this->_dcb_telcos)){
            return true;
        }
        return false;
    }

    /**
     * 是否是OTP（OTP：是指运营商给用户发送验证短信通过之后才能扣费）
     * @return bool
     */
    private function isOtp(){
        if(in_array($this->getTelco(),$this->_otp_telcos)){
            return true;
        }
        return false;
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
     * @param null $keyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
    }
    /**
     * @return null
     */
    public function getUi()
    {
        return $this->ui;
    }

    /**
     * @param null $ui
     */
    public function setUi($ui)
    {
        $this->ui = $ui;
    }


    /**
     * @return string
     */
    public function getTelco()
    {
        if(is_null($this->telco)){
            $this->setTelco((new Ini(CFG."/config.common.ini"))->get("telcos")->toArray()[0]);
        }
        return $this->telco;
    }

    /**
     * @param null $telco
     */
    public function setTelco($telco)
    {
        $this->telco = $telco;
    }

    /**
     * @return null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param null $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getPromotionId()
    {
        return $this->promotionId;
    }

    /**
     * @param int $promotionId
     */
    public function setPromotionId($promotionId)
    {
        $this->promotionId = $promotionId;
    }

    /**
     * @return string
     */
    public function getPropsName()
    {
        return $this->propsName;
    }

    /**
     * @param string $propsName
     */
    public function setPropsName($propsName)
    {
        $this->propsName = $propsName;
    }

    /**
     * @return null
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
    /**
     * @return boolean
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param boolean $bank
     */
    public function setBank($bank)
    {
        $this->bank = $bank;
    }

}
