<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/30
 * Time: 10:18
 */
use Illuminate\Database\Eloquent\Model;

class RefProducterModel extends Model{
    protected $connection="main";
    /**
     * 表名
     * @var string
     */
    protected $table = 'ref_producer';
    protected $primaryKey = 'producer_id';
}