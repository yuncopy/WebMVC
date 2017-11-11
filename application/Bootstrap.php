<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/27
 * Time: 18:25
 */
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Yaf\Application;
use Yaf\Registry;
use Yaf\Loader;
use Illuminate\Database\Capsule\Manager as DB;
use Yaf\Config\Ini;
use Yaf\Route\Regex;
class Bootstrap extends Bootstrap_Abstract{

    /**
     * 初始化loader
     */
    public function _initLoader(Dispatcher $dispatcher){
        Loader::import(APP_PATH . "/vendor/autoload.php");
        $dispatcher->getInstance()->setErrorHandler("myErrorHandle");
        register_shutdown_function("myShutdownFunc");
    }

    /**
     * 定义常量
     * @param Dispatcher $dispatcher
     */
    public function _initDefine(Dispatcher $dispatcher){
        defined("CONF_PATH") or define("CONF_PATH",APP_PATH."/conf/");
        defined("STROAGE_PATH") or define("STROAGE_PATH",APP_PATH."/storage/");
        defined("LOG_PATH") or define("LOG_PATH",STROAGE_PATH."/log/");
        defined("NOW") or define("NOW",time());
        defined("CT") or define("CT",getCt());
        defined("CFG") or define("CFG",CONF_PATH.CT);
        defined("IS_AJAX") or define("IS_AJAX",isAjax());
        defined("IS_PC") or define("IS_PC",isPc());
    }

    /**
     * 初始化配置
     */
    public function _initConfig(Dispatcher $dispatcher) {
        $config = Application::app()->getConfig();
        Registry::set("config", $config);
    }

    /**
     * 初始化whoops
     */
    public function _initWhoops(Dispatcher $dispatcher){
        if (Registry::get('config')->whoops->handler) {
            $run = new Whoops\Run;
            $handler = new \Whoops\Handler\PrettyPageHandler();
            $handler->setPageTitle(Registry::get('config')->whoops->pagetTitle);
            $run->pushHandler($handler);
            if (Whoops\Util\Misc::isAjaxRequest()) {
                $run->pushHandler(new \Whoops\Handler\JsonResponseHandler());
            }
            $run->register();
        }
    }

    /**
     * 默认控制器
     * @param Dispatcher $dispatcher
     */
    public function _initDefaultName(Dispatcher $dispatcher) {
        $dispatcher->setDefaultModule("Index")->setDefaultController("Sms")->setDefaultAction("index");
    }

    /**
     * 路由
     * @param Dispatcher $dispatcher
     */
    public function _initRoute(Dispatcher $dispatcher) {
        $router = Dispatcher::getInstance()->getRouter();
        /**
         * 添加配置中的路由
         */
        $router->addConfig(Registry::get("config")->routes);
        //短代路由修改
        $smsRoute = new Regex('#bluepay/index.php#',['module'=>'index','controller'=>'sms','action'=>'index'],[],[]);
        $router->addRoute('smsRoute',$smsRoute);
        //点卡路由修改
        $cashcardRoute = new Regex('#bluepay/cashcard/#',['module'=>'index','controller'=>'cashcard','action'=>'index'],[],[]);
        $router->addRoute('cashcardRoute',$cashcardRoute);
        //银行路由修改
        $bankRoute = new Regex('#bank/bank.php#',['module'=>'index','controller'=>'bank','action'=>'index'],[],[]);
        $router->addRoute('bankRoute',$bankRoute);
        //offline路由修改
        $offlineRoute = new Regex('#bluepay/offline.php#',['module'=>'index','controller'=>'offline','action'=>'index'],[],[]);
        $router->addRoute('offlineRoute',$offlineRoute);
        //qr路由修改
        $qrRoute = new Regex('#bluepay/qr.php#',['module'=>'index','controller'=>'qr','action'=>'index'],[],[]);
        $router->addRoute('qrRoute',$qrRoute);
        //钱包路由修改
        $walletRoute = new Regex('#bluepay/wallet.php#',['module'=>'index','controller'=>'wallet','action'=>'index'],[],[]);
        $router->addRoute('walletRoute',$walletRoute);
    }

    /**
     * Eloquent
     * @param Dispatcher $dispatcher
     */
    public function _initEloquent(Dispatcher $dispatcher) {
        $dbFile = CFG."/config.db.ini";
        if(file_exists($dbFile)){
            $dbConfig = new Ini($dbFile,ini_get("yaf.environ"));
            $capsule = new DB();
            foreach($dbConfig->database->toArray() as $key => $value){
                $capsule->addConnection($value,$key);
            }
            // 设置全局静态可访问
            $capsule->setAsGlobal();
            // 启动Eloquent
            $capsule->bootEloquent();
        }
    }

    /**
     * 注册插件
     * @param Dispatcher $dispatcher
     */
    public function _initPlugin(Dispatcher $dispatcher) {
        //注册一个插件
        $objRoutePlugin = new RoutePlugin();
        $dispatcher->registerPlugin($objRoutePlugin);
    }
}