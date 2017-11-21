<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/28
 * Time: 12:14
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class Logs{
    /**
     * @return Logger
     */
    public static function php(){
        $log = new Logger('PHP');
        if($log->isHandling(400)){
            return $log;
        }
        $log->pushHandler(new StreamHandler(LOG_PATH."/php/".date("Ymd").".log", Logger::ERROR));
        return $log;
    }
    /**
     * @return Logger
     */
    public static function debug($filename=null){
        $log = new Logger('DEBUG');
        if($log->isHandling(100)){
            return $log;
        }
        $filename = is_null($filename)?"debug.log":$filename;
        $log->pushHandler(new StreamHandler(LOG_PATH."/debug/$filename.log", Logger::DEBUG));
        return $log;
    }
}