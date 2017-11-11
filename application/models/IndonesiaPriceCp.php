<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/14
 * Time: 16:47
 */
use Illuminate\Database\Eloquent\Model;
class IndonesiaPriceCpModel extends Model{
    protected $connection="main";
    /**
     * 表名
     * @var string
     */
    protected $table = 'indonesia_price_cp';
    protected $primaryKey = 'id';

}