<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/30
 * Time: 11:59
 */
define("APP_PATH",  realpath(dirname(__FILE__) . '/../../')); /* 指向public的上一级 */
define("CONF_PATH",APP_PATH."/conf/");
define("LOG_PATH",APP_PATH."/log/");
define("NOW",time());
class Crontab {
    private static $optStr = "c:s:d";
    private static $longopts = ["h",];
    private static $actList = ['start','stop','restart','reload'];
    public static $options = [];
    private static $help = <<<EOF
web_sdk 定时任务帮助信息：

    用法：/path/to/php Crontab.php -s=参数1 -c=参数2 [选项N=参数N]
      或：/path/to/php Crontab.php -s参数1 -c参数2 [选项N参数N]
      或：/path/to/php Crontab.php -s 参数1 -c 参数2 [选项N 参数N]

    参数：
    -s    start--启动  stop--停止  restart--重启
    -c    国家缩写可以填th(泰国)，vn(越南)，in(印尼)  必填
    -d    是否以守护进程运行
    --h   帮助信息
EOF;
    //运行时log日志目录
    static private $logPath;
    //Monnolog对象
    static public $logger;
    //Monolog handler
    static public $logHandler;
    //主进程的进程id文件
    static public  $pidFile;
    //主进程名
    static private $processName;
    /**
     * 启动
     */
    public static function run(){
        self::$options = getopt(self::$optStr,self::$longopts);
        $app  = new \Yaf\Application(APP_PATH . "/conf/application.ini",ini_get("yaf.environ"));
        $app->bootstrap()->execute(function(){
            //设置主进程pid文件路径
            self::$pidFile = APP_PATH . '/application/command/Crontab_Master.pid';
            //设置日志文件路径
            self::$logPath = STROAGE_PATH . '/log/crontab/';
            //主进程名称
            self::$processName = 'web_sdk_crontab_master';
            //设置日志相关
            self::$logger = new \Monolog\Logger('crontab');
            \Monolog\ErrorHandler::register(self::$logger,[E_ERROR]);
            self::$logHandler = new \Monolog\Handler\StreamHandler(self::$logPath . '/crontab.log',\Monolog\Logger::DEBUG);
            self::$logger->pushHandler(self::$logHandler);
            //设置主进程名称
            self::setProcessName();
            //帮助信息
            self::option_h();
            //国家
            self::option_c();
            //守护进程
            self::option_d();
            //动作
            self::option_s();
        },self::$options);
    }

    /**
     * 动作
     * @param $opt
     */
    private static function option_s(){
        if(!isset(self::$options['s'])||!in_array(self::$options['s'],self::$actList)){
            exit("参数错误：".PHP_EOL.self::$help.PHP_EOL);
        }
        switch(self::$options['s']){
            case 'start':
                echo "正在启动服务中...\n";
                sleep(1);
                \crontab\service\CrontabService::start();
                break;
            case 'stop':
                echo "正在停止服务...\n";
                sleep(1);
                \crontab\service\CrontabService::stop();
                break;
            case 'restart':
                echo "正在重启服务...\n";
                sleep(1);
                \crontab\service\CrontabService::restart();
                break;
            case 'reload';
                \crontab\service\CrontabService::reload();
                break;
        }
    }
    /**
     * 设置进程名
     */
    private static function setProcessName(){
        \swoole_set_process_name(self::$processName);
    }

    /**
     * 帮助信息
     * @param $opt
     */
    private static function option_h(){
        if(isset(self::$options['h'])){
            exit(self::$help.PHP_EOL);
        }
    }

    /**
     * 国家
     * @param $opt
     */
    private static function option_c(){
        if(!isset(self::$options['c'])||!in_array(self::$options['c'],['th','vn','in'])){
            exit("参数错误：".PHP_EOL.self::$help.PHP_EOL);
        }
    }

    /**
     * 是否守护进程运行
     * @param $opt
     */
    private static function option_d(){
        if(isset(self::$options['d']) || isset(self::$options['daemon'])){
            \crontab\service\CrontabService::$daemon = true;
        }
    }
    /**
     *log日志
     */
    public static function log($log){
        $now = date('Y-m-d H:i:s',time());
        //守护进程方式运行时记log到log文件，否则打印到屏幕
        if(\crontab\service\CrontabService::$daemon === true) {
            $text = "[$now] : {$log}\n";
            $logPath = self::$logPath.'/log-'.date('Y-m-d').'.log';
            file_put_contents($logPath,$text,FILE_APPEND);
        }else{
            echo "[$now] : {$log}\n";
        }
    }
}
Crontab::run();

