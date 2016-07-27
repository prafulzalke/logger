<?php

/**
 * Class for Auth
 *
 * Auth plugin check the user is logged in or not.
 */
class My_Controller_Action_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{

    /**
     * Check user authentication.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $moduleName = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        if (!Zend_Auth::getInstance()->hasIdentity()) {
            $request->setModuleName('user');
            $request->setControllerName("auth");
            $request->setActionName("index");
        }

    }
}