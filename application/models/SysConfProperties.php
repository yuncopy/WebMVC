<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/12
 * Time: 11:04
 */
use Illuminate\Database\Eloquent\Model;
class SysConfPropertiesModel extends Model{
    protected $connection="main";
    /**
     * 表名
     * @var string
     */
    protected $table = 'sys_conf_properties';
    protected $primaryKey = 'id';
}