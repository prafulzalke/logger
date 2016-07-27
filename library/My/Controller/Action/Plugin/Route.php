<?php


class My_Controller_Action_Plugin_Route extends Zend_Controller_Plugin_Abstract
{
    

    public function __construct()
    {
        
    }
    
    /**
     * Set static and dynamic routes for website
     * 
     * @param \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {       
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        // Load our new configuration file for static routes
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', 'production');
        // Add our custom routes to the router
        $router->addConfig($config, 'routes');
        
        //print_r($router);die;
       
    }
}
