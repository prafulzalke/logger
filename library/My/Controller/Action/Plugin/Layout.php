<?php

/**
 * Class for Layout
 *
 * Switch layout module wise
 */
class My_Controller_Action_Plugin_Layout extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {  
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->dashboard = $this->getCountForDashboard();
    }
    
    public function getCountForDashboard()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $where = "where date(log_date) = '" . date('Y-m-d') . "' and type = 'ERROR'";
        $sql = "SELECT node, count( * ) as cnt FROM error_log $where GROUP BY node ORDER BY date(log_date) DESC";
        $result = $db->fetchAll($sql);
        $results = [
            'baspubweb1' => 0,
            'baspubweb2' => 0,
            'basprivweb1' => 0,
            'basprivweb2' => 0,
            'basprivwebesb1' => 0,
            'basprivwebesb2' => 0,
        ];
        foreach ($result as $row) {
            $results[$row['node']] = $row['cnt'];
        }
        
        return $results;
    }
}