<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/11/10
 * Time: 12:10
 */
class Twig implements \Yaf\View_Interface{
    /**
     * (Yaf >= 2.2.9)
     * 传递变量到模板
     *
     * 当只有一个参数时，参数必须是Array类型，可以展开多个模板变量
     *
     * @param string | array $name 变量
     * @param string $value 变量值
     *
     * @return Boolean
     */
    public function assign($name, $value = null){

    }

    /**
     * (Yaf >= 2.2.9)
     * 渲染模板并直接输出
     *
     * @param string $tpl 模板文件名
     * @param array $var_array 模板变量
     *
     * @return Boolean
     */
    public function display($tpl, $var_array = array()){

    }

    /**
     * (Yaf >= 2.2.9)
     * 渲染模板并返回结果
     *
     * @param string $tpl 模板文件名
     * @param array $var_array 模板变量
     *
     * @return String
     */
    public function render($tpl, $var_array = array()){

    }

    /**
     * (Yaf >= 2.2.9)
     * 设置模板文件目录
     *
     * @param string $tpl_dir 模板文件目录路径
     *
     * @return Boolean
     */
    public function setScriptPath($tpl_dir){

    }

    /**
     * (Yaf >= 2.2.9)
     * 获取模板目录文件
     *
     * @return String
     */
    public function getScriptPath(){

    }
}