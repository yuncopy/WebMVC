<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/11/1
 * Time: 14:57
 */
namespace crontab\library;

use crontab\models\TaskModel;
use crontab\service\CrontabService;
class Process {
    private $task;
    private $jobName;

    /**
     *创建一个子任务进程
     *@param $task
     */
    public function createProcess($task){
        $this->task	   = $task;
        $this->jobName = $task['name'];
        CrontabService::$processList[$this->jobName] = new \swoole_process([$this,'run']);
        $pid = CrontabService::$processList[$this->jobName]->start();
        if(!$pid){
            \Crontab::log('子任务创建失败');
        }else{
            CrontabService::$taskList[$pid] = [
                'start'=>time(),
                'task'=>$task
            ];
            CrontabService::$tasks[$this->jobName]['pid']=$pid;
        }
    }

    /**
     *子进程执行函数
     *@param $worker
     */
    public function run($worker){
        $id = $this->task['id'];
        $last_exec_time = $this->task['next_exec_time'];
        //修改数据库状态正在执行中，修改最后一次执行时间
        $result = TaskModel::where('id',$id)->update([
            'status'=>1,
            'last_exec_time'=>$last_exec_time,
            'pid'=>$worker->pid
        ]);
        if(!$result){
            $worker->exit(0);
        }
        //定义子进程的名称
        $worker->name('web_sdk_crontab_worker_' . $this->task['name'] . ' time:' . $this->task['next_exec_time']);
        //任务执行类
        $className = "\\crontab\\task\\".$this->task['name'];
        if(!class_exists($className)){
            \Crontab::log("工作类：$className 不存在");
        }
        (new $className)->run();
        //修改数据状态 status=0, 修改下一次执行时间
        $next_exec_time = date('Y-m-d H:i:s',strtotime($this->task['next_exec_time']) + $this->task['separate_time']);
        TaskModel::where('id','=',$id)->update([
            'status'=>0,
            'next_exec_time'=>$next_exec_time,
            'pid'=>0
        ]);
        $worker->exit(0);
    }
}