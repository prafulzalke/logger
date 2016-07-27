<?php
class Default_View_Helper_Message extends Zend_View_Helper_Abstract
{
    private $_flashMessenger = null;
    public function Message() 
    {
        $flashMessenger = $this->_getFlashMessenger();
        $messages = $flashMessenger->getMessages();
        $flashMessenger->clearMessages();
        return $messages;
    } 
    public function _getFlashMessenger()
    {
        if (null === $this->_flashMessenger) {
            $this->_flashMessenger =
                Zend_Controller_Action_HelperBroker::getStaticHelper(
                    'FlashMessenger');
        }
        return $this->_flashMessenger;
    }
}
