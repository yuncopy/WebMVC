<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/9/30
 * Time: 10:26
 */
use Illuminate\Database\Eloquent\Model;

class ProductInfoModel extends Model{
    public $connection="main";
    /**
     * 表名
     * @var string
     */
    public $table = 'product_info';
    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * 所属procter
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function producter(){
        return $this->belongsTo('RefProducterModel','producer_id','producer_id');
    }
}