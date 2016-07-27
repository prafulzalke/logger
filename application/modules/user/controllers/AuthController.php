<?php

class User_AuthController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->layout->disableLayout();

        if ($this->getRequest()->isPost()) {

            $params = $this->getRequest()->getParams();

            if (isset($params['username']) && isset($params['password'])) {
                if ($this->_process($params)) {
                    $result = $this->_helper->redirector('index', 'index', 'default');
                }
            }

            if (isset($params['username'])) {

                $user = new Application_Model_User();
                $userModel = $user->findByEmail($params['username']);

                $this->view->email = $params['username'];
                $this->view->userModel = $userModel;
            }
        }
    }

    protected function _process($values)
    {
        // Get our authentication adapter and check credentials
        $adapter = $this->_getAuthAdapter();
        $adapter->setIdentity($values['username']);
        $adapter->setCredential(md5($values['password']));

        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if ($result->isValid()) {
            $user = $adapter->getResultRowObject();
            $auth->getStorage()->write($user);
            return true;
        }
        else{
            $this->_helper->redirector('index', 'auth', 'user');
        }
        return false;
    }

    protected function _getAuthAdapter()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('user')
                    ->setIdentityColumn('email')
                    ->setCredentialColumn('password');
        return $authAdapter;
    }

    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect($this->view->url() . '/login');
    }
    
    public function forgotPasswordAction()
    {
        if($this->getRequest()->isPost()){
            $email = $this->getRequest()->getParam('email');
            $user = new Application_Model_User();
            $result = $user->findByEmail($email);print_r($result);
            if(NULL == $result){
                $this->_helper->getHelper('FlashMessenger')->addMessage('Invalid email address.');
                $this->_helper->redirector('forgot-password', 'auth', 'user');
            }else{
                $password = $user->updatePassword(array('id' => $result['id']));
                $result['password'] = $password;
                $this->_sendMail($result);
                $this->_helper->getHelper('FlashMessenger')->addMessage('New password is sent to your email address.');
                $this->_helper->redirector('index');
            }
      }
    }  
    private function _sendMail($result)
    {
        $mailer = Zend_Registry::get('logmailer');
        $mailer->clearSubject();
        $mailer->clearRecipients();
        $mailer->addTo('praful.zalke@gmail.com');
        $mailer->setFrom('prafultestmail@gmail.com');
        $this->view->result = $result;
        $mailer->setSubject("Account is creatd for user :" . $result['first_name'] . " " . $result['last_name']);
        $mailer->setBodyHtml($this->view->render('index/password-changed.phtml'));
        $mailer->send();
   
    }

}



