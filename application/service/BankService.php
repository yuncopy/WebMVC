<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/10
 * Time: 15:52
 */
namespace service;

use Illuminate\Validation\Rules\In;
use Yaf\Config\Ini;
use Yaf\Request_Abstract;

class BankService extends CommonService{
    private $price = null;//商品价格
    private $propsName = 'propsName';//道具名称
    private $promotionId = 1000;//渠道ID
    private $keyword = null;//关键字，CP使用泰国银行的时候可能会传递过来这个参数，这个参数可以解析出来商品ID跟交易号
    private $ui = null;//无界面参数,CP使用泰国银行的时候，可能会传递ui=none这个参数
    private $telco = null;//运营商，CP使用泰国银行的时候，当选择了ui=none，则会传递这个参数
    private $customerId = NOW;//cp使用印尼跟越南银行的时候可能会传过来用户id
    private $cc_number = null;//印尼首页表单提交过来的借记卡号码
    private $email = null;//印尼首页表单提交过来的邮箱
    private $reqName = null;//印尼首页表单提交过来的用户名
    private $CHALLENGE_CODE_3 = null;//印尼
    private $responseToken = 'response_token';//印尼
    private $msisdn = 'BLUEGUEST';
    private $str_url = null;
    private $bank_id;//越南银行的银行ID
    private $country;//测试环境传递的参数
    private $bankCardNo;//测试环境传递的参数
    private $orderId;//测试环境传递的参数
    private $_hidden_blue_pay_log_productId = [812,622,518];//当CP使用泰国银行的时候，需要隐藏bluepay_logo的产品id
    const SMS_CP_CMD = 6;//银行CP传递过来的命令字
    const IN_BANK_TAX_FEE = 6500;//印尼银行手续费
    protected $map = [
        'pricelist'=>'price',
        'reqEmail'=>'email',
        'transactionid'=>'transactionId',
    ];
    /**
     * 参数验证
     * @return bool
     * @throws \ParamsException
     */
    protected function validata(){
        if(CT == 'th'){
            if(is_null($this->getKeyword())){
                if(empty($this->getProductId())){
                    throw new \ParamsException("parameter error [productId]",400);
                }
                if(empty($this->getPrice())){
                    throw new \ParamsException("parameter error [price]",400);
                }
                if(empty($this->getTransactionId())){
                    throw new \ParamsException("parameter error [transactionId]",400);
                }
            }else{
                if(substr($this->getKeyword(), 0,1)!=self::SMS_CP_CMD){
                    throw new \ParamsException("parameter error [keyword]",400);
                }
                //验证成功之后设置产品ID跟交易ID
                $this->setProductId(substr($this->getKeyword(), 1,4));
                $this->setTransactionId(substr($this->getKeyword(), 5,strlen($this->getKeyword())));
            }
        }else{
            if(empty($this->getProductId())){
                throw new \ParamsException("parameter error [productId]",400);
            }
            if(empty($this->getPrice())){
                throw new \ParamsException("parameter error [price]",400);
            }
            if(empty($this->getTransactionId())){
                throw new \ParamsException("parameter error [transactionId]",400);
            }
        }
        if(CT == 'test'){
            if(empty($this->getCountry())){
                throw new \ParamsException("parameter error [country]",400);
            }
        }
        return true;
    }

    /**
     * @param $name
     * @param array $arg
     * @return array
     */
    public function __call($name,$arg=null){
        $action = $name . CT;
        if (method_exists($this, $action)) {
            return $this->$action($arg[0]);
        }
        throw new \ParamsException("server error.",400);
    }

    /**
     * 泰国银行
     */
    private function indexth(){
        $data = [
            'productId'=>$this->getProductId(),
            'price'=>$this->getPrice(),
            'transactionId'=>$this->getTransactionId(),
            'propsName'=>$this->getPropsName(),
            'promotionId'=>$this->getPromotionId(),
            'telco'=>$this->getTelco(),
            'ui'=>$this->getUi(),
            'bank'=>true,
        ];
        return ["location"=>"/c=sms&a=index?".http_build_query($data)];
    }
    /**
     * 印尼银行
     * @param Request_Abstract $request
     * @return array
     */
    private function indexin(){
        $data = [
            'productId' => $this->getProductId(),
            'promotionId' => $this->getPromotionId(),
            'price' => $this->getPrice(),
            'transactionid' => $this->getTransactionId(),
            'propsName' => $this->getPropsName(),
            'customerId' => $this->getCustomerId(),
            'taxFee'=>self::IN_BANK_TAX_FEE,
            'str'=>str_shuffle("1234567").date('s')
        ];
        \Logs::debug("bank" . CT)->addInfo("", $data);
        return $data;
    }
    /**
     * 越南
     * @return array
     */
    private function indexvn(){
        $vtcChannelPid = new Ini(CFG."/config.common.ini");
        if(in_array($this->getProductId(),$vtcChannelPid->get("vtc")->toArray())){
            //使用VTC渠道
            $str ='';
            $path = 'http://125.212.202.118:8160/charge/paysByBank/createOrder?';
            $str .= 'productId='.$this->getProductId();
            $str .= '&promotionId='.$this->getPromotionId();
            $str .= '&price='.$this->getPrice();
            $str .= '&transactionid='.urlencode($this->getTransactionId());
            $str .= '&propsName='.urlencode($this->getPropsName());
            $str .= '&msisdn='.$this->getMsisdn();
            $str .= '&payChannel=1';
            //获取该用户的md5Sf
            $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
            $hash =md5($str.$key);
            $encrypt = '&encrypt='.$hash;
            //6. 拼接请求URL
            $url = $path.$str.$encrypt;
            //7. CRUL请求
            $resJson = CommonService::httpClient()->request("GET",$url)->getBody();
            //8. 判断返回的状态，并提示用户回复短信完成计费
            $resArr = json_decode($resJson,true);
            if($resArr ['status'] == 200){
                $requertPath = trim($resArr['url']);
                //日志
                $content = "Request parameter:{$str} | Request path:{$requertPath}";
                \Logs::debug("vtc_req_success")->addInfo($content);
                //跳转
                return ['location'=>$requertPath];
            }
        }else{
            //epay渠道
            $payChannel = 3;  //epay
            $str = '';
            $str .= 'productId=' . $this->getProductId();
            $str .= '&promotionId=' . $this->getPromotionId();
            $str .= '&price=' . $this->getPrice();
            $str .= '&transactionid=' . urlencode($this->getTransactionId());
            $str .= '&propsName=' . urlencode($this->getPropsName());
            $str .= '&payChannel=' . $payChannel;
            //隐藏提交值
            $input_str = '<input type="hidden" name="str_url" value="' . $this->crypt()->encrypt($str, DESKEY, DESIV) . '">
                       <input type="hidden" name="productId" value="' . $this->crypt()->encrypt($this->getProductId(), DESKEY, DESIV) . '">';
            $comCfg = new Ini(CFG."/config.common.ini");
            $bankIds = $comCfg->get("bank")->get("id")->toArray();
            $banks = [];
            foreach($bankIds as $k => $v){
                $banks[$k] =$this->crypt()->encrypt($v, DESKEY, DESIV);
            }
            if(count($banks)>8){
                $banks = array_chunk($banks,3,TRUE);
            }
            return [
                'input_str'=>$input_str,
                'transactionId'=>$this->getTransactionId(),
                'productId'=>$this->getProductId(),
                'promotionId'=>$this->getPromotionId(),
                'propsName'=>$this->getPropsName(),
                'price'=>$this->getPrice(),
                'banks'=>$banks,
                'str'=>str_shuffle("1234567").date('s')
            ];
        }
    }

    /**
     * 测试
     * @return array
     */
    private function indextest(){
        $data['price'] = $this->getPrice();
        $data['productId'] = $this->getProductId();
        $data['transactionId'] = $this->getTransactionId();
        $data['promotionId'] = $this->getPromotionId();
        $data['bankType'] = $this->getCountry();
        $path = 'http://120.76.101.146:8160/charge/test/testBankCreateOrder?';
        $str = http_build_query($data);
        $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
        $hash =md5($str.$key);
        $encrypt = '&encrypt='.$hash;
        $url = $path.$str.$encrypt;
        \Logs::debug("testBankCreateOrder")->addInfo("银行测试下单",['url'=>$url]);
        $resultJson = $this->httpClient()->request("GET",$url)->getBody();
        $result = json_decode($resultJson,true);
        if($result['status'] != 201){
            return ['status'=>400,'description'=>(new Ini(CFG."/config.lang.ini"))->get($result['status'])];
        }else{
            return [
                'price'=>$this->getPrice(),
                'transactionId'=>$this->getTransactionId(),
                'productId'=>$this->getProductId(),
                'country'=>$this->getCountry(),
                'orderId'=>$result['orderId']
            ];
        }
    }

    /**
     * 印尼银行充值提交逻辑处理
     * @param Request_Abstract $request
     * @return string
     */
    public function submitin(){
        //2. 接收参数并数据过滤
        $data['productId'] = $this->getProductId();
        $data['promotionId'] = $this->getPromotionId();
        $data['price'] = $this->getPrice();
        $data['transactionid'] = $this->getTransactionId();
        $data['propsName'] = $this->getPropsName();
        $data['msisdn'] = '';
        $data['payChannel'] = 2;
        $data['customerId'] = $this->getCustomerId();
        $data['cardNumber'] = $this->getCc_number();
        $data['responseToken'] = $this->getResponseToken();
        $data['uniqueCode'] = $this->getCHALLENGE_CODE_3();
        $data['reqEmail'] = $this->getEmail();
        $data['reqName'] = $this->getReqName();
        $str = 'http://52.221.94.242:8160/charge/paysByBank/createOrder?';
        $pam = http_build_query($data);
        //4. 获取该用户的md5Sf
        $key = \ProductInfoModel::find($data['productId'])->producter->md5Suf;
        $mdValue = md5($pam.$key);
        $encrypt = '&encrypt=' . $mdValue;
        //5. 拼接请求URL
        $url = $str.$pam.$encrypt;
        $pamInfo = $pam.$encrypt;
        //6. CRUL请求
        $resJosn = CommonService::httpClient()->request("GET", $url)->getBody();
        //7. 处理结果
        $resArr = json_decode($resJosn, true);
        if (isset($resArr['status']) && !empty($resArr['status'])) {
            //200 成功  601 余额不足  400 参数错误  631 token 错误  600 银行系统异常
            $statusArr = array('s200' => '成功', 's400' => '参数错误', 's600' => '银行系统异常', 's601' => '余额不足', 's631' => 'token 错误','s409'=>'商品不存在');
            $status = trim($resArr['status']);
            $info = ['pamInfo' => $pamInfo, "status" => $status . '-' . $statusArr['s' . $status]];
            if ($status == 200) {
                \Logs::debug("doku_succcess")->addInfo("", $info);
            } else {
                \Logs::debug("doku_error")->addInfo("", $info);
            }
        } else {
            \Logs::debug("doku_error")->addInfo("", ['pamInfo' => $pamInfo, "desc" => "没有返回状态码"]);
        }
        return ['url'=>"/c=bank&a=response?".http_build_query($resArr+['productId'=>$this->getProductId(),'transactionId'=>$this->getTransactionId()])];
    }
    /**
     * 越南
     * @return array
     */
    public function submitvn(){
        $str_url_post = $this->getStr_url();
        $product_id_post = $this->getProductId();
        $bank_id_post = $this->getBank_id();
        $this->setProductId(CommonService::crypt()->decrypt($product_id_post, DESKEY, DESIV));
        $this->setBank_id(CommonService::crypt()->decrypt($bank_id_post, DESKEY, DESIV));
        $this->setStr_url(CommonService::crypt()->decrypt($str_url_post, DESKEY, DESIV));
        if ($this->getBank_id() && $this->getStr_url() && $this->getProductId()) {
            //4、获取该用户的md5Sf
            $this->setStr_url($this->getStr_url().'&bankId=' . $this->getBank_id());
            $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
            $md_value =md5($this->getStr_url().$key);
            $encrypt = '&encrypt=' . $md_value;
            //5、拼接请求URL
            $path = new Ini(CFG.'/config.common.ini');
            $url = $path['bank']['order'] . $this->getStr_url() . $encrypt;
            $response = CommonService::httpClient()->request("GET",$url);//CRUL请求
            $response_array = json_decode($response->getBody(), true); //对象转数组
            if($response->getStatusCode() != 200){
                \Logs::debug("runvn_error")->addInfo("网络请求失败",$response_array);
            }elseif($response->getStatusCode() == 200){
                \Logs::debug("runvn_success")->addInfo("网络请求成功",$response_array);
                //6、处理请求结果
                if ($response_array['status'] == 200) {
                    $requertPath = trim($response_array['url']);
                    if ($requertPath) {
                        return ['location'=>$requertPath];
                    } else {
                        return ['status'=>404,'msg'=>'response url error'];
                    }
                } else {
                    return ['status'=>404,'msg'=>'response error'];
                }
            } else {
                \Logs::debug("runvn_error")->addInfo(''.$response_array);
                return ['status'=>404,'msg'=>'server error'];
            }
        } else {
            return ['status'=>404,'msg'=>'Request error [Transaction failed]'];
        }
    }

    /**
     * @return array
     */
    private function submittest(){
        $data['productId'] = $this->getProductId();
        $data['orderId'] = $this->getOrderId();
        $data['cardNo'] = $this->getBankCardNo();
        $path = 'http://120.76.101.146:8160/charge/test/testBankPayOrder?';
        $str =  http_build_query($data);
        $key = \ProductInfoModel::find($this->getProductId())->producter->md5Suf;
        $hash =md5($str.$key);
        $encrypt = '&encrypt='.$hash;
        $url = $path.$str.$encrypt;
        \Logs::debug("testBankPay")->addInfo("测试银行支付",[$url]);
        $resJosn = $this->httpClient()->request("GET",$url)->getBody();
        $result = json_decode($resJosn,true);
        if ($result["status"] == "200") {
            return $result;
        }
        return ['status'=>$result['error']['code'],'description'=>(new Ini(CFG."/config.lang.ini"))->get($result['error']['code'])];
    }

    /**
     * 印尼银行充值结果响应
     * @param Request_Abstract $request
     * @return array
     */
    public function responsein(Request_Abstract $request)
    {
        $statusArr = array('s540' => 'Bank card number error', 's200' => 'Success', 's400' => 'Parameter error', 's600' => 'Transaction failed', 's601' => 'Insufficient balance', 's631' => 'token error', 's500' => 'Payment error');
        //$statusArr  = array('s540'=>'银行卡号错误','s200'=>'成功','s400'=>'参数错误','s600'=>'交易失败','s601'=>'余额不足','s631'=>'token 错误');
        $status = $request->getQuery("status", 2);
        $imgValue = ($status == 200) ? 1 : 2;
        $myValue = $statusArr['s' . $status];
        $mystatus = 's' . $status;
        if(!in_array($mystatus,$statusArr)){
            $myValue = 'Payment error';
        }
        return[
            'myValue'=>$myValue,
            'imgValue'=>$imgValue,
            'msgValue'=>$myValue,
            'transaction_id'=>'',
            'time'=>$request->getQuery("time"),
            'price'=>$request->getQuery("price"),
            'bt_id'=>$request->getQuery("bt_id")
        ];
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
     * @return null
     */
    public function getKeyword()
    {
        return $this->keyword;
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
     * @return null
     */
    public function getTelco()
    {
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
    public function getResponseToken()
    {
        return $this->responseToken;
    }

    /**
     * @param null $response_token
     */
    public function setResponseToken($response_token)
    {
        $this->responseToken = $response_token;
    }

    /**
     * @return null
     */
    public function getCc_number()
    {
        return $this->cc_number;
    }

    /**
     * @param null $cc_number
     */
    public function setCc_number($cc_number)
    {
        $cc_number = str_replace(['-',' '], '', $cc_number);
        $this->cc_number = $cc_number;
    }

    /**
     * @return null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param null $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return null
     */
    public function getReqName()
    {
        return $this->reqName;
    }

    /**
     * @param null $reqName
     */
    public function setReqName($reqName)
    {
        $this->reqName = $reqName;
    }

    /**
     * @return null
     */
    public function getCHALLENGE_CODE_3()
    {
        return $this->CHALLENGE_CODE_3;
    }

    /**
     * @param null $CHALLENGE_CODE_3
     */
    public function setCHALLENGE_CODE_3($CHALLENGE_CODE_3)
    {
        $this->CHALLENGE_CODE_3 = $CHALLENGE_CODE_3;
    }
    /**
     * @return string
     */
    public function getMsisdn()
    {
        return $this->msisdn;
    }

    /**
     * @param string $msisdn
     */
    public function setMsisdn($msisdn)
    {
        $this->msisdn = $msisdn;
    }
    /**
     * @return mixed
     */
    public function getBank_id()
    {
        return $this->bank_id;
    }

    /**
     * @param mixed $bank_id
     */
    public function setBank_id($bank_id)
    {
        $this->bank_id = $bank_id;
    }
    /**
     * @return null
     */
    public function getStr_url()
    {
        return $this->str_url;
    }

    /**
     * @param null $str_url
     */
    public function setStr_url($str_url)
    {
        $this->str_url = $str_url;
    }
    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }
    /**
     * @return mixed
     */
    public function getBankCardNo()
    {
        return $this->bankCardNo;
    }

    /**
     * @param mixed $bankCardNo
     */
    public function setBankCardNo($bankCardNo)
    {
        $this->bankCardNo = $bankCardNo;
    }
    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }
}