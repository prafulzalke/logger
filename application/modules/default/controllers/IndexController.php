<?php

class IndexController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $log = new Application_Model_ErrorLog();
        $monthLog = new Application_Model_MonthLog();

        $logs = $log->getLatestDataLog($this->getRequest()->getParam('node', 'baspubweb'));
        $monthLogs = $monthLog->getLog($this->getRequest()->getParam('node', 'baspubweb'));
        $months = [];

        foreach ($monthLogs as $data) {
            $months['error'][] = $data['error'];
            $months['notice'][] = $data['notice'];
            $months['warning'][] = $data['warning'];
        }

        $this->view->node = $logs['node'];
        $this->view->logs = $logs['result'];
        $this->view->monthlogs = $months;
        $this->view->fixed = $logs['fixed'];
    }
    
    public function detailsAction()
    {
        $type = $this->getRequest()->getParam('type', 'ERROR');
        $logDate = $this->getRequest()->getParam('log_date', date('Y-m-d'));
        $node = $this->getRequest()->getParam('node', 'baspubweb');

        $log = new Application_Model_ErrorLog();
        $this->view->result = $log->getTraceLog($type, $logDate, $node);

        if ($type == 'ERROR') {
            $class = 'bg-red';
        } else if ($type == 'NOTICE') {
            $class = 'bg-aqua';
        } else {
            $class = 'bg-yellow';
        }

        $users = new Application_Model_User();
        $users = $users->fetchAll();

        $this->view->class = $class;
        $this->view->node = $node;
        $this->view->users = $users;
    }
    
    public function addMantisAction()
    {
        //$functionName = 'mc_enum_project_status';
        $message = $this->getRequest()->getParam('message');
        $stackTrace = $this->getRequest()->getParam('stack_trace');
        $mantisId = $this->getRequest()->getParam('mantis_id');
        $location = $this->getRequest()->getParam('location');
        $comment = $this->getRequest()->getParam('comment', '');
        $userId = $this->getRequest()->getParam('userId', '');
        $status = $this->getRequest()->getParam('status', NULL);

        $functionName = 'mc_issue_add';

        if ($mantisId == 0) {
            $data = [
                'issue' => [
                    'project' => [
                        'id' => 52,
                        'name' => 'Website BI'
                    ],
                    'summary' => $message,
                    'description' => $location .'<br>'.$stackTrace,
                    'category' => 'Bug'
                ]
            ];
            $mantisId = $this->_processMantis($functionName, $data);
        } else {
            $data = [
                'message' => $message,
                'location' => $location
            ];
            $data['status'] = $status;
        }

        $data['mantis_id'] = $mantisId;
        $data['comment'] = $comment;

        if ($userId > 0) {
            $user = new Application_Model_User();
            $model = $user->find($userId);
            $data['user_id'] = $model->id;
            $data['assign'] = $model->name;
        }

        $mantis = new Application_Model_Mantis();
        $data['mantis_created'] = date('Y-m-d H:i:s');
        $mantisId = $mantis->insert($data);

        if ((int)$mantisId > 0) {
            $this->_updateMantisId($mantisId, $message, $location, $data);
        }

        $this->_helper->json(['result' => $mantisId]);
    }

    protected function _updateMantisId($mantisId, $message, $location, $data = [])
    {
        $log = new Application_Model_ErrorLog();
        $log->updateMantisId($mantisId, $message, $location, $data);
    }
    
    protected function _processMantis($functionName, $data)
    {
        //return date('YmdHis');
        define('MANTISCONNECT_URL', 'https://mantis.bas-dev.net/api/soap/mantisconnect.php');
        define('USERNAME', 'praful');
        define('PASSWORD', 'buntyzalke');
        $args = array_merge(['username' => USERNAME, 'password' => PASSWORD], $data);

        try {
            $client = new SoapClient(MANTISCONNECT_URL . '?wsdl');
            $result = $client->__soapCall($functionName, $args);
        } catch (SoapFault $e) {
            $result = array(
                'error' => $e->faultstring
            );
        }

        return $result;
    }

    public function issueDashboardAction()
    {
        $users = new Application_Model_User();
        $users = $users->fetchAll();
        $data = [];

        $status = [
            'Open' => 0,
            'Fixed' => 0
        ];

        foreach($users as $key => $user) {
            $status['name'] = $user->name;
			$status['team'] = $user->team;
            $status['logo'] = $user->logo;
            $data[$user->id] = $status;
        }

        $mantis = new Application_Model_Mantis();
        $issues = $mantis->getIssueList();
        foreach($issues as $issue) {
            $data[$issue->userId][$issue->status] = $issue->cnt;
        }

        $this->view->users = $data;
    }

    public function updateMantisAction()
    {
        $mantis = new Application_Model_Mantis();
        $mantisId = $this->getRequest()->getParam('mantis_id');
        $comment = $this->getRequest()->getParam('comment', '');
        $status = $this->getRequest()->getParam('status', NULL);
        $data = [
            'status' => $status,
            'comment' => $comment
        ];
		
		if ($status == 'Fixed') {
			$data['issue_fixed'] = date('Y-m-d H:i:s');
		}
        $mantis->update($data, $mantisId);

        $error = new Application_Model_ErrorLog();
        $res = $error->update(['status' => $status], $mantisId);

        $this->_helper->json(['result' => $res]);
    }

    public function historyAction()
    {
        $message = $this->getRequest()->getParam('mes', null);
        $node = $this->getRequest()->getParam('node', null);
        $log = new Application_Model_ErrorLog();
        $this->view->results = $log->findByMessage($message, $node);
    }

    public function issueAssignedAction()
    {
        $log = new Application_Model_ErrorLog();
        $this->view->result = $log->getAssignedIssue(Zend_Auth::getInstance()->getIdentity()->id);

        $users = new Application_Model_User();
        $users = $users->fetchAll();

        $this->view->users = $users;
        $this->view->class = 'bg-yellow';

        $this->render('details');
    }
}

