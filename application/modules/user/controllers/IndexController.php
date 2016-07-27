<?php

class User_IndexController extends Zend_Controller_Action
{
    
    public function indexAction()
    {
		$auth = Zend_Auth::getInstance();
		if($auth->getIdentity()->type!='salesagent'){
			return $this->_helper->redirector('list','index', 'user');
		}else{
			return $this->_helper->redirector('list','index', 'client');
		}
    }
    public function listAction()
    {
        $user = new Application_Model_User();
        
        $editLink = $this->view->url(array('module' => 'user',
                'controller' => 'index',
                'action' => 'edit',
                'id' => '#'),
            'default', true);
         $deleteLink = $this->view->url(array('module' => 'user',
                'controller' => 'index',
                'action' => 'delete',
                'id' => '#'),
            'default', true);
         $viewLink = $this->view->url(array('module' => 'user',
                'controller' => 'index',
                'action' => 'view',
                'user' => '#'),
            'default', true);
        $options = $user->getUserDataGridOption($editLink, $deleteLink, $viewLink, $this->view->baseUrl());
        $dataGrid = new Bvb_MyGrid();
        
        /* Set grid in view */
        $this->view->grid = $dataGrid->getGridObject($options);
        $this->view->userList=$user->read();
    }
    public function editAction()
    {
        $userId=$this->getRequest()->getParam('id');
        $user = new Application_Model_User();
        $data=null;
        if(isset($userId))
        $data=$user->find($userId);
        $userForm = new Application_Form_User();
        $userForm->setAction($this->view->url(array ('module' => 'user','controller' => 'index','action' => 'save',)));
        $translate = Zend_Registry::get('translate');
        $userForm->setTranslator($translate);
        $userForm->customPopulate($data);
        $this->view->userForm=$userForm;
   }
   
   public function viewAction()
   {
        $userId=$this->getRequest()->getParam('user');
        $user = new Application_Model_User();
        $data=null;
        if(isset($userId)){
            $data = $user->find($userId);
			
            $this->view->user = $data[0];
        }
        
        $proejct = new Application_Model_Project();
        $deleteLink = $this->view->url(array('module' => 'project',
                'controller' => 'index',
                'action' => 'delete-user-project',
                'id' => '#'),
            'default', true);
        
		$options = $proejct->getUserProjectDataGridOption($deleteLink, $userId,$this->view->baseUrl());
		$dataGrid = new Bvb_MyGrid();
        
        /* Set grid in view */
        if(isset($userId)){
            $this->view->grid = $dataGrid->getGridObject($options);
        }
        $this->view->userId = $userId;
        $this->view->projectList=$proejct->projectUser($userId);
       
		
   }
    public function saveAction()
    {
        $userForm = new Application_Form_User();
        $request = $this->getRequest();
        if($userForm->isValid($request->getPost())) {
            $data = $userForm->getValues();
			$data['rights'] = implode(",",$data['rights']);
			$user = new Application_Model_User();
			$result = $user->save($data);
		    if(isset($data['id']) && $data['id'] != ""){
                $this->_helper->getHelper('FlashMessenger')->addMessage('User information updated succesfully !!!!!');
            }    
            else{
                $this->_helper->getHelper('FlashMessenger')->addMessage('User added succesfully !!!!!');
                $this->_sendMail($result);
            }    
            $this->_helper->redirector("list","index","user");
        } else {
            $this->view->userForm = $userForm;
            return $this->render('edit');
        }
        
    }     
    public function deleteAction()
    {
        $userId=$this->getRequest()->getParam('id');
        $user = new Application_Model_User();
        $user->delete($userId);
        $this->_helper->getHelper('FlashMessenger')->addMessage('User deleted succesfully !!!!!');
        $this->_helper->redirector("list","index","user");
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
        $mailer->setBodyHtml($this->view->render('index/account-created.phtml'));
        $mailer->send();
    }
    
    public function assignProjectAction()
    {
        $data = $this->getRequest()->getPost();
        $projectUser = new Application_Model_ProjectUser();
        $projectUser->save($data);
        
        return $this->_helper->redirector('view','index', 'user', array('user' => $data['user_id']));
        
    }
    
    public function updatePasswordAction()
    {
        $changePasswordForm = new Application_Form_ChangePassword();
        $data = $this->getRequest()->getParams();
        if($this->getRequest()->isPost() && $changePasswordForm->isValid($data)){
            $user = new Application_Model_User();
            $userData = array();
            $userData['id'] = Zend_Auth::getInstance()->getIdentity()->id;
            $userData['password'] = md5($data['newPassword']);
            $user->save($userData);
            $this->_helper->getHelper('FlashMessenger')->addMessage('Password updated succesfully !!!!!');
            return $this->_helper->redirector('list','index', 'user');
        }
        $this->view->changePassword = $changePasswordForm;
    }
}

