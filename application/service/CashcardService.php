<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/29
 * Time: 14:40
 */
namespace service;
use Yaf\Config\Ini;
use Yaf\Registry;
class CashcardService extends CommonService{
    private $propsName = 'propsName';//道具名
    private $promotionId = 1000;//渠道ID
    private $customerId = NOW;//用户ID
    private $ui = null;//ui
    private $provider = null;//提供商
    private $cardNo = null;//卡号
    private $serialNo = null;//卡密
    private $redirect_url = null;
    private $_need_serialNo_providers = ['viettel','mobifone','vinaphone','vcoin','megacard'];//需要卡号跟卡密的提供商
    private $_split_card_no_providers = ['dtac'];//卡号分两个input的提供商
    private $_only_need_card_no_providers = ['bluecoins','truemoney','12call','mogplay','game-on'];//仅仅需要卡号的提供商，不包含分隔卡号的
    private $display_none_logo_productId = [812,622,518];//不显示bluepay_logo的商品ID
    protected $map = [
        'cardno'=>'cardNo',
        'serialno'=>'serialNo'
    ];
    /**
     * 参数验证
     * @return bool
     */
    protected function validata(){
        if(empty($this->getProductId())){
            throw new \ParamsException("productId is require.",400);
        }
        if(empty($this->getTransactionId())){
            throw new \ParamsException("transactionId is require.",400);
        }
        if(!is_null($this->getUi())&&$this->getUi() != self::NON_UI_PARAM){
            throw new \ParamsException("ui param error.",400);
        }
        if(!empty($this->getProvider())){
            $this->validataProvider();
        }
        if($this->getUi() == self::NON_UI_PARAM){
            if(empty($this->getProvider())){
                throw new \ParamsException("provider param error.",400);
            }
            $this->validataProvider();
            if(empty($this->getCardNo())){
                throw new \ParamsException("cardno is require.",400);
            }
            if(!empty($this->getRedirect_url())){
                throw new \ParamsException("this mode not support redirect_url param.",400);
            }
        }
        return true;
    }

    /**
     * 验证提供商
     * @throws \ParamsException
     */
    private function validataProvider(){
        $flag = false;
        if(CT == 'test'){
            $for = [];
            foreach((new Ini(CFG."/config.common.ini"))->get("operators")->get("cashcard")->toArray() as $country => $providers){
                foreach($providers as $provider){
                    $for[] = $provider;
                }
            }
        }else{
            $for = (new Ini(CFG."/config.common.ini"))->get("operators")->get("cashcard")->toArray();
        }
        foreach($for as $provider){
            if(strcasecmp($this->getProvider(),$provider)==0){
                $flag = true;
            }
        }
        if($flag == false){
            throw new \ParamsException("provider param error.",400);
        }
    }

    /**
     * @return array
     */
    public function index(){
        if($this->getUi() == self::NON_UI_PARAM){
            if (strtolower($this->getProvider()) == "mogplay" ) {
                $this->setProvider("indomog");
            }
            if (strtolower($this->getProvider()) == "game-on") {
                $this->setProvider("lytocard");
            }
            $host = Registry::get("config")->country->host[CT];
            $beforeHash = "productId=".$this->getProductId()
                ."&provider=".$this->getProvider()
                ."&transactionId=".$this->getTransactionId()
                ."&cardNo=".$this->getCardNo()
                ."&serialNo=".$this->getSerialNo()
                ."&msisdn=&customerId=".$this->getCustomerId()
                ."&propsName=".$this->getPropsName()
                ."&promotionId=".$this->getPromotionId();
            $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
            $hash =md5($beforeHash.$key);
            $url = $host."/charge/service/cashcard?".$beforeHash."&encrypt=".$hash;
            $lang = new Ini(CFG."/config.lang.ini");;
            try {
                $output = CommonService::httpClient()->request("GET",$url)->getBody();
                $result = json_decode($output,true);
                \Logs::debug("cashcard")->addInfo("",(array) $this);
                if ($result["status"]!=200) {
                    return ['status'=>509,'description'=>$lang[509]];
                }else{
                    return ['status'=>$result["status"],'description'=>'Success.','price'=>$result["price"]];
                }
            } catch (\ParamsException $e) {
                //错误码
                return ['status'=>405,'description'=>$lang[405]];
            }
        }else{
            $op = new \Yaf\Config\Ini(CFG."/config.common.ini");
            $lang = new \Yaf\Config\Ini(CFG."/config.lang.ini");
            return [
                'op'=>$op->get("operators")->get("cashcard")->toArray(),
                'ct'=>CT,
                'lang'=>$lang->toArray(),
                'redirectUrl'=>$this->getRedirect_url(),
                'cardNo'=>$this->getCardNo(),
                'serialNo'=>$this->getSerialNo(),
                'productId'=>$this->getProductId(),
                'promotionId'=>$this->getPromotionId(),
                'provider'=>$this->getProvider(),
                'propsName'=>$this->getPropsName(),
                'customerId'=>$this->getCustomerId(),
                'transactionId'=>$this->getTransactionId(),
                'isShowLogo'=>$this->isShowLogo(),
                'input'=>$this->getInput(),
                'thGuideBlueCoins'=>$this->getThGuideBlueCoins()
            ];
        }
    }
    /**
     * 获取表单
     * @return string
     */
    private function getInput(){
        $cardNoInput = '<input type="text" name="cardNo" placeholder="Please input Card NO.(pin)" value="" class="form-control text-cc-input input-lg"   id="input_card_no"><br />';
        $serialNoInput = '<input type="text" name="serialNo" placeholder="Please input Serial NO." value="" class="form-control text-cc-input input-lg"  id="input_serial_no"><br />';
        $splitCardNoInput = '<center><input type="text" id="abcdId" name = "abcd" placeholder="หมายเลขบัตร" value="" class=" input-lg" style="width:50%; border: solid 1px #ccc;padding:5px;float:left;" > - <input type="text" name="efgh" id="efghId" placeholder="รหัสบัตร" value="" class=" input-lg" style="width: 30%; border: solid 1px #ccc;padding:5px;  "></center><br />';
        if(in_array(strtolower($this->getProvider()),$this->_only_need_card_no_providers)){
            return $cardNoInput;
        }elseif(in_array(strtolower($this->getProvider()),$this->_split_card_no_providers)){
            return $splitCardNoInput;
        }elseif(in_array(strtolower($this->getProvider()),$this->_need_serialNo_providers)){
            return $cardNoInput.$serialNoInput;
        }else{
            throw new \ParamsException("provider error.");
        }
    }

    /**
     * 是否显示怎样获取bluepaycoins的提示
     * @return string
     */
    private function getThGuideBlueCoins(){
        if(in_array($this->getProvider(),['BlueCoins'])){
            return '<a href="http://www.jmtt.co.th/bcguide/en/activity/bluecoins.html" id="th_guide_bluecoins" class="th_guide_bluecoins">How to get BlueCoins?</a>';
        }
        return '';
    }

    /**
     * 是否显示logo
     * @return bool
     */
    private  function isShowLogo(){
        if(in_array($this->getProductId(),$this->display_none_logo_productId)){
            return false;
        }
        return true;
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
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
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
    public function getProvider()
    {
        $op = new Ini(CFG."/config.common.ini");
        if(is_null($this->provider)){
            if(CT == 'test'){
                $this->setProvider(strtolower($op->get("operators")->get("cashcard")->get("th")->toArray()[0]));
            }else{
                $this->setProvider(strtolower($op->get("operators")->get("cashcard")->toArray()[0]));
            }
        }
        return $this->provider;
    }

    /**
     * @param mixed $provider
     */
    public function setProvider($provider)
    {
        $this->provider = strtolower($provider);
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

    /**
     * @return mixed
     */
    public function getSerialNo()
    {
        return $this->serialNo;
    }

    /**
     * @param mixed $serialNo
     */
    public function setSerialNo($serialNo)
    {
        $this->serialNo = $serialNo;
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


}