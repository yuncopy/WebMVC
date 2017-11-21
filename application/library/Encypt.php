<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/11/7
 * Time: 15:27
 */
class Encypt{
    private static $cipher='AES-256-CBC';
    private static $key = '';
    /**
     * 加密
     * @param $data
     * @param $productId
     * @return string
     */
    public static function encrypt($data,$productId){
        self::setKey($productId);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, self::$cipher, self::$key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, self::$key, $as_binary=true);
        return urlencode(base64_encode( $iv.$hmac.$ciphertext_raw ));
    }
    /**
     * 解密
     * @param $data
     * @param $productId
     * @return string
     */
    public static function decrypt($data,$productId){
        self::setKey($productId);
        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::$cipher, self::$key, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, self::$key, $as_binary=true);
        if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
        {
            return $original_plaintext;
        }
        return '';
    }
    /**
     * @return string
     */
    public static function getKey(){
        return self::$key;
    }
    /**
     * @param $productId
     */
    public static function setKey($productId){
        self::$key = \ProductInfoModel::find($productId)->producter->md5Suf;
    }
}