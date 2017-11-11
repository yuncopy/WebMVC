<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/11/1
 * Time: 12:04
 */
namespace crontab\models;

use Illuminate\Database\Eloquent\Model;
class TaskModel extends Model{
    protected $connection="crontab";
    /**
     * 表名
     * @var string
     */
    protected $table = 'task';
    protected $primaryKey = 'id';
    public $timestamps = false;
}