<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/16
 * Time: 17:23
 */
namespace service;
use Yaf\Config\Ini;
use Yaf\Registry;

class WalletService extends CommonService{

    private $price = null;
    private $promotionId = 1000;
    private $propsName = 'propsName';
    private $schema = 'schema';
    private $tradeType = null;
    private $_forApp = [1108,177];
    protected $map = ['payType'=>'tradeType'];
    public $_res = [];

    /**
     * @return bool
     * @throws \ParamsException
     */
    protected function validata(){
        if (empty($this->getProductId())) {
            throw new \ParamsException("ProductId is required",400);
        }
        if (empty($this->getTransactionId())) {
            throw new \ParamsException("transactionId is required",400);
        }
        if (empty($this->getPrice())) {
            throw new \ParamsException("price is required",400);
        }
        if (empty($this->getPropsName())) {
            throw new \ParamsException("propsName is required",400);
        }
        if (empty($this->getTradeType())) {
            throw new \ParamsException("tradeType is required",400);
        }else{
            if (!in_array($this->getProductId(), $this->_forApp) && strcasecmp($this->getTradeType(),'native')) {
                throw new \ParamsException("payType must be native or NATIVE",400);
            }
            if (in_array($this->getProductId(), $this->_forApp) && (strcasecmp($this->getTradeType(),'natice')==0 && strcasecmp($this->getTradeType(),'app') == 0) ) {
                throw new \ParamsException("payType must be native or app",400);
            }
        }
        if (CT != "th") {
            throw new \ParamsException("Does not support your Area.",500);
        }
        return true;
    }

    /**
     *
     */
    public function run(){
        $result = new class{
            public $status;
            public $description;
            public $schema="";//pay.bluepay”/*app支付**/
            public $qrUrl="";//bluepay/d472a005d7cc48 /*扫码支付**/
        };
        $output = $this->doWallet();
        $result->status = $output['status'];
        $result->description = "";
        //生成二维码
        //app 支付生成schema
        if ($result->status == 201) {
            if (($output['codeUrl'] != "" || empty($output['codeUrl'])) && strcasecmp($this->getTradeType(),"NATIVE") == 0 ) {
                # code...
                $result->qrUrl = $this->erweima($output['codeUrl']);
            }else{
                $result->schema = $output['schema']. "?tradeNo=".$output['tradeNo'];
            }
        }else{
            $result -> description = $output['description'];
        }
        $this->_res = $result;
    }

    /**
     * @return mixed
     */
    private function doWallet(){
        if (CT == 'th') {
            $path = 'http://203.150.54.214:21101/charge/bluewallet/createOrder?';
        }else{
            $host = Registry::get("config")->country->host[CT];
            $path = $host.'/charge/bluewallet/createOrder?';
        }
        $str="";
        $str .= 'productId='.$this->getProductId();
        $str .= '&promotionId='.$this->getPromotionId();
        $str .= '&transactionId='.$this->getTransactionId();
        $str .= '&price='.$this->getPrice();
        $str .= '&tradeType='.$this->getTradeType();
        $str .= "&currency=THB";
        $str .= "&msisdn=095457481";
        $str .= "&propsName=".$this->getPropsName();
        $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
        $hash =md5($str.$key);
        $encrypt= "";
        $encrypt .= '&encrypt='.$hash;
        $url = $path.$str.$encrypt;
        $resJosn = CommonService::httpClient()->request("GET",$url);
        $resJosn = json_decode($resJosn->getBody(),true);
        \Logs::debug("wallet")->addInfo("",[
            'tradeType'=>$this->getTradeType(),
            'promotionId'=>$this->getPromotionId(),
            'price'=>$this->getPrice(),
            'transactionId'=>$this->getTransactionId(),
            'productId'=>$this->getProductId(),
            'resJosn'=>$resJosn,
            'url'=>$url
        ]);
        return $resJosn;
    }

    /**
     * @param $chl
     * @param string $x
     * @param string $level
     * @param string $margin
     * @return string
     */
    private function erweima($chl,$x ='150',$level='L',$margin='0'){
        return "http://chart.apis.google.com/chart?chs=".$x."x".$x."&cht=qr&chld=".$level."|".$margin."&chl=".urlencode($chl);
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
     * @return null
     */
    public function getPropsName()
    {
        return $this->propsName;
    }

    /**
     * @param null $propsName
     */
    public function setPropsName($propsName)
    {
        $this->propsName = $propsName;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return null
     */
    public function getTradeType()
    {
        return $this->tradeType;
    }

    /**
     * @param null $tradeType
     */
    public function setTradeType($tradeType)
    {
        $this->tradeType = $tradeType;
    }
}