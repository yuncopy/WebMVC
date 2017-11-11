<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/27
 * Time: 17:59
 */
error_reporting(E_ALL^E_NOTICE);
define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
$app  = new \Yaf\Application(APP_PATH . "/conf/application.ini",ini_get("yaf.environ"));
$app->bootstrap()->run();