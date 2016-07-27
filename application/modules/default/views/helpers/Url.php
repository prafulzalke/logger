<?php
class Default_View_Helper_Url extends Zend_View_Helper_Abstract
{
    public function url()
    {
        echo Zend_Registry::get('config')->baseUrl;
    } 
}
