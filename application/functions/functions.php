<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/28
 * Time: 11:35
 */
/**
 * 错误处理函数，当application.throwException=1的时候，系统错误会交给此函数处理
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @param $errcontext
 */
function myErrorHandle($errno, $errstr, $errfile, $errline,$errcontext){
    switch($errno){
        case E_ERROR:
            Logs::php()->addError("",[
                '错误码'=>$errno,
                '错误信息'=>$errstr,
                '错误文件'=>$errfile.'第'.$errline.'行',
                '错误上下文'=>$errcontext
            ]);
            break;
    }
}
function myShutdownFunc(){}

/**
 * 是否电脑
 * @return bool
 */
function isPc()
{
    $agent = strtolower(\Yaf\Dispatcher::getInstance()->getRequest()->getServer('HTTP_USER_AGENT'));
    $is_pc = (strpos($agent, 'windows nt')) ? true : false;
    $is_mac = (strpos($agent, 'mac os')) ? true : false;
    return $is_mac || $is_pc;
}

/**
 * 获取手机操作系统类型 1.IOS：&  2.Android：？
 */
function getOs()
{
    $userAgent = \Yaf\Dispatcher::getInstance()->getRequest()->getServer('HTTP_USER_AGENT');
    $os = '?';
    if (strpos($userAgent, "iPhone") > 0 || strpos($userAgent, "iPad") > 0 || strpos($userAgent, "iPod") > 0) {
        $os = '&';
    }
    return $os;
}

/**
 * @return array
 */
function getallheaders()
{
    $headers = [];
    foreach (\Yaf\Dispatcher::getInstance()->getRequest()->getServer() as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

/**
 * 是否为jquery发过来的ajax请求
 * @return bool
 */
function isAjax()
{
    $value  = \Yaf\Dispatcher::getInstance()->getRequest()->getServer('HTTP_X_REQUESTED_WITH','');
    $result = ('xmlhttprequest' == strtolower($value)) ? true : false;
    return $result;
}

/**
 * 获取国家
 */
function getCt(){
    if(isCli()){
        $ct = Crontab::$options['c'];
    }else{
        $name = explode(".",\Yaf\Dispatcher::getInstance()->getRequest()->getServer("SERVER_NAME"));
        $ct = reset($name);
    }
    return $ct;
}

/**
 * 是否是CLI
 * @return bool
 */
function isCli(){
    return \Yaf\Dispatcher::getInstance()->getRequest()->isCli();
}

/**
 * @param array $array
 * @return string
 */
function juu($array = []){
    return json_encode($array,JSON_UNESCAPED_UNICODE );
}



