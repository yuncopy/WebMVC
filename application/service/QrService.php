<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/14
 * Time: 18:08
 */
namespace service;

class QrService extends CommonService{
    private $img;
    private $keyword;
    private $telco;
    private $price;
    protected $map = ['pricelist'=>'price'];

    public function create(){
        if (!empty($this->getTelco())) {
            $telco = "&telco=".$this->getTelco();
        }else{
            $telco = "";
        }
        //*兼容keyword*/
        if (empty($this->getKeyword())) {
            $transactionId= $this->getTransactionId();
            if (!empty($transactionId)) {
                $transactionId = "&transactionId=".$transactionId;
            }else{
                $transactionId = "";
            }
            $productId = $this->getProductId();
            if (!empty($productId)) {
                $productId = "&productId=".$productId;
            }else{
                $productId = "";
            }
        }else{
            if (!empty($this->getKeyword())) {
                $keyword = "&keyword=".$this->getKeyword();
            }else{
                $keyword = "";
            }
        }
        if(empty($this->getPrice())){
            $pricelist = "";
        }else{
            $pricelist = "&price=".$this->getPrice();
        }
        $str = 'http://'.$_SERVER['HTTP_HOST']."/c=sms&a=index?".$pricelist.$keyword.$productId.$transactionId."&from=qr";
        $this->erweima($str,$telco);
    }

    /**
     * @param $chl
     * @param $telco
     * @param string $x
     * @param string $level
     * @param string $margin
     */
    private function erweima($chl,$telco,$x ='150',$level='L',$margin='0'){
        if ($telco != "" && $telco!=null) {
            $this->setImg("http://chart.apis.google.com/chart?chs=".$x."x".$x."&cht=qr&chld=".$level."|".$margin."&chl=".urlencode($chl));
        }else{
            $this->setImg('<img src="http://chart.apis.google.com/chart?chs='.$x.'x'.$x.'&cht=qr&chld='.$level.'|'.$margin.'&chl='.urlencode($chl).'" />');
        }
    }
    /**
     * @return mixed
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param mixed $img
     */
    private function setImg($img)
    {
        $this->img = $img;
    }

    /**
     * @return mixed
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @param mixed $keyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
    }

    /**
     * @return mixed
     */
    public function getTelco()
    {
        return $this->telco;
    }

    /**
     * @param mixed $telco
     */
    public function setTelco($telco)
    {
        $this->telco = $telco;
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
}