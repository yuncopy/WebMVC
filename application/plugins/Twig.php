<?php
/**
 * Created by PhpStorm.
 * User: SolarZi
 * Date: 2017/10/9
 * Time: 17:27
 */
use Yaf\Registry;
use Yaf\Dispatcher;
class TwigPlugin extends \Yaf\Plugin_Abstract {
    /**
     * @param \Yaf\Request_Abstract $request
     * @param \Yaf\Response_Abstract $response
     */
    public function routerShutdown(\Yaf\Request_Abstract $request, \Yaf\Response_Abstract $response) {
        if (!isCli()) {
            $config = Registry::get("config");
            $modules_names = explode(',', $config->application->modules);
            $paths = [APP_PATH .'/application/views',];
            foreach($modules_names as $k => $v){
                if (is_dir(APP_PATH . '/application/modules/' . $request->getModuleName() . '/views')) {
                    array_push($paths, APP_PATH . '/application/modules/' . $request->getModuleName() . '/views');
                }
            }
            Dispatcher::getInstance()->setView(new Twig($paths, isset($config->twig) ? $config->twig->toArray() : []));
        }
    }
}