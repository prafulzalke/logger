<?php

class Application_Model_User 
{

    public function fetchAll()
    {
        $db = new Application_Model_DbTable_User();
        $result = $db->fetchAll(null);
        return $result;
    }

    public function find($id) 
    {
        $db = new Application_Model_DbTable_User();
        $result = $db->find($id);
        return $result[0];
    }
    
    public function findByEmail($email)
    {
         $db = new Application_Model_DbTable_User();
         $select = $db->select()->where('email = ?', $email);
         $result = $db->fetchRow($select);

         return $result;
    }

    public function save($data) 
    {
        $loggedInUserId = (Zend_Auth::getInstance()->getIdentity()->id);
        $db = new Application_Model_DbTable_User();
        
        if (isset($data['id']) && $data['id'] != ""){
            $data['updated_by'] = $loggedInUserId;
            $data['updated_at'] = date('Y-m-d H-i-s');
            $db->update($data, "id = " . $data['id']);
        }    
        else{
            $data['created_by'] = $loggedInUserId;
            $data['created_at'] = date('Y-m-d H-i-s');
            $data['updated_by'] = $loggedInUserId;
            $data['updated_at'] = date('Y-m-d H-i-s');
            $password = $this->_generatePassword();
            $data['password'] = md5($password);
            $result = $db->insert($data);
        }
        $result = $data;
        return array('user' => $result, 'password' => $password);
    }
    
    private function _generatePassword()
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') , 0 , 10 );
    }
    
    public function updatePassword($data)
    {
        $password = $this->_generatePassword();
        $data['password'] = md5($password);
        $this->save($data);
        return $password;
    }
    public function delete($id) 
    {
        $db = new Application_Model_DbTable_User();
        $db->delete('id=' . $id);
        
    }

    public function _getDepartmentDataSql()
    {
        $dao = new Application_Model_DbTable_User();
        $select = $dao->getDefaultAdapter()->select();
               
        $select->from(array('u' => "users"), array('id',
            'u.first_name',
            'u.last_name',
            'email_address',
            'type',
            'created_at' => new Zend_Db_Expr('DATE_FORMAT(u.created_at,"%d-%m-%Y %H:%i:%s")'),
            'updated_at' => new Zend_Db_Expr('DATE_FORMAT(u.updated_at,"%d-%m-%Y %H:%i:%s")'),
            'created' => 'u1.first_name'
           )
        )->JoinInner(array('u1' => 'users'),'u.created_by = u1.id', array());
        return $select;
    }

    public function getUserDataGridOption($editLink, $deleteLink, $viewLink, $url) 
    {
        $sourceObject = $this->_getDepartmentDataSql();
        /* Set department listing options in a grid */
        $options = array(
            'sourceObject' => $sourceObject,
            'columns' => array(
                'id' => array('hidden' => true),
                'first_name' => array('title' => 'First name', 'position' => '1'),
                'last_name' => array('title' => 'Last Name', 'position' => '2'),
                'email_address' => array('title' => 'Email-address', 'position' => '3'),
                'type' => array('title' => 'Type', 'position' => '4'),
                'created_at' => array('title' => 'Created At', 'position' => '6'),
                'created' => array('title' => 'User created By     ', 'position' => '5'),
                'updated_at' => array('title' => 'Updated At', 'position' => '7'),
                
            ),
            'actions' => array(
                'data' => array(
                    'view' => array(
                        'title' => 'Edit',
                        'image' => $url.'/images/text.png',
                        'link' => $viewLink,
                        'param1' => 'id'
                    ),
                    'edit' => array(
                        'title' => 'Edit',
                        'image' => $url.'/images/edit.gif',
                        'link' => $editLink,
                        'param1' => 'id'
                    ),
                    'delete' => array(
                        'title' => 'Delete',
                        'class' => 'deleteDepartmentRecord',
                        'image' => $url.'/images/grid_delete.png',
                        'link' => $deleteLink,
                        'param1' => 'id'
                    ),
                ),
                'position' => 'right',
                'title' => '    ',
            ),
            'filtersText' => array(
                'first_name' => array('class'=>'smallTextFilters'),
                'last_name' => array('class'=>'smallTextFilters'),
                'email_address' => array('class'=>'bigTextFilters', 'width' => '100px'),
            ),
            'filters' => array(),
            'recordPerPage' => '2',
            'setShowOrderImage' => false,
            'setAjax' => 'dataGridDepartment',
            'setKeyEventsOnFilters' => false,
            'paginationInterval' => array('50' => '50', '100' => '100', '200' => '200'),
        );

        return $options;
    }

}

