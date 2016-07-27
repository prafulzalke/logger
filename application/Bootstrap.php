<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    public function _initLog()
    {
        $option = $this->getOptions();
        $config = new Zend_Config($option);
        Zend_Registry::set('config', $config);
    }

}
