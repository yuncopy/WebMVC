<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/31
 * Time: 12:20
 */
namespace crontab\service;

use crontab\library\Process;
use crontab\models\TaskModel;
class CrontabService {
    static $daemon = false;
    //当前任务数组
    static public $taskList   = array();
    //任务配置队列
    static public $tasks      = array();
    //当前时间戳
    static public $time;
    //退出主进程的信号
    static public $stopSignal = false;
    //config.php的配置
    static public $config;
    //任务进程池
    static public $processList = array();
    /**
     * 启动
     */
    static public function start(){
        if(file_exists(\Crontab::$pidFile)){
            exit("pid文件已经存在！\n");
        }
        //初始化任务
        $data = TaskModel::all()->toArray();
        foreach($data as $row){
            self::$tasks[$row['name']] = $row;
        }
        self::daemon();//守护进程
        self::registerSignal();                 //注册监听的信号
        self::registerTimerTask();              //注册定时器
        self::writePidFile();//将父进程pid写入文件
        \Crontab::log("服务启动成功");
    }

    /**
     * 停止
     */
    static public function stop(){
        $pid = file_get_contents(\Crontab::$pidFile);
        if($pid){
            if(@\swoole_process::kill($pid,0)){        //检查$pid进程是否存在
                \swoole_process::kill($pid);
                unlink(\Crontab::$pidFile);
                \Crontab::log('进程'.$pid.'已经结束，服务关闭成功');
            }else{
                unlink(\Crontab::$pidFile);
                \Crontab::log('进程'.$pid.'不存在，删除pid文件');
            }
        }else{
            \Crontab::log("服务没有启动");
        }
    }

    /**
     * 重启
     */
    static public function restart(){
        self::stop();
        self::$daemon=true;
        self::start();
    }

    /**
     * 重新加载配置
     */
    static public function reload(){
        $pid = file_get_contents(\Crontab::$pidFile);
        if($pid){
            $res = \swoole_process::kill($pid,SIGUSR1);
            if($res){
                \Crontab::log("reload success");
            }
        }else{
            \Crontab::log("进程不存在，reload失败");
        }
    }

    /**
     *注册定时器
     */
    static private function registerTimerTask(){
        swoole_timer_tick(1000,function(){
            self::$time = time();
            try{
                self::runJob();
            }catch(\Exception $e){
                \Crontab::log("抛出异常：".$e->getMessage());
            }
        });
    }
    /**
     *注册监听的信号
     */
    static private function registerSignal(){
        //SIGCHLD，子进程结束时，父进程会收到这个信号
        \swoole_process::signal(SIGCHLD,function($signo){
            //这里可以做任务执行完后的事情，比如：改变任务状态，统计任务执行时间
            while($status =  \swoole_process::wait(false)) {
                $task      = self::$taskList[$status['pid']];
                $startTime = $task['start'];
                self::updateTasks($task);
                $runTime   = time() - $startTime;
                \Crontab::log($task['task']['name'] . "执行了".$runTime."秒");
                unset(self::$taskList[$status['pid']]);
            }
        });
        //命令行输入ctrl+c时发出此信号给主进程
        \swoole_process::signal(SIGINT,function($signo){
            self::resetStatus();
            unlink(\Crontab::$pidFile);
            exit;
        });
        //重新载入任务
        \swoole_process::signal(SIGUSR1,function(){
            $data = TaskModel::all()->toArray();
            foreach($data as $row){
                self::$tasks[$row['name']] = $row;
            }
        });
    }
    /**
     *每秒执行一次这个函数,这里面做最少的工作
     */
    static private function runJob(){
        foreach(self::$tasks as $jobName => $job){
            //这里判断是否要执行，如果要执行，开启一个线程  条件：1、到达开始执行时间 2、status状态未执行0 3、到达下一次执行时间
            if(self::$time >= strtotime($job['start_time']) && $job['status']==0 && self::$time >= strtotime($job['next_exec_time'])){
                //条件成立，执行任务
                self::$tasks[$jobName]['status'] = 1;       //修改状态未正在执行当中
                (new Process())->createProcess($job);
            }else{
                continue;
            }
        }
    }
    /**
     *重置修改任务的状态未0
     */
    static public function resetStatus(){
        TaskModel::where("id",">",0)->update(['status'=>0,'pid'=>0]);
    }
    /**
     *更新任务列表
     */
    static private function updateTasks($data){
        $task = $data['task'];
        $id   = $task['id'];
        /**
         * cli模式下定时任务无法使用pdo持久连接，因为当子进程关闭之后，释放了端口，导致跟数据库的tcp连接断开，
         * 但是父进程没有意识到数据库连接已经断开了，下一个任务fork出来的进程使用此连接的时候就会报一个警告，所以这种定时任务的模式无法使用数据库持久链接，但是eloqument会进行重连
         */
        $config = TaskModel::find($id)->toArray();
        if($config){
            self::$tasks[$task['name']]['status']		  = $config['status'];
            self::$tasks[$task['name']]['last_exec_time'] = $config['last_exec_time'];
            self::$tasks[$task['name']]['next_exec_time'] = $config['next_exec_time'];
            self::$tasks[$task['name']]['pid']		      = $config['pid'];
        }
    }
    /**
     *写入当前进程pid到pid文件
     */
    static private function writePidFile(){
        file_put_contents(\Crontab::$pidFile,self::getPid());
    }
    /**
     *获取当前的进程pid
     */
    static private function getPid(){
        return posix_getpid();
    }
    /**
     *是否以守护进程的方式运行
     */
    private static function daemon(){
        if(self::$daemon===true){
            \swoole_process::daemon();
        }
    }
}