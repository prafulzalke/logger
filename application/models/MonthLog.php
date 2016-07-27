<?php

class Application_Model_MonthLog
{

    public function getLog($node)
    {
        $db = new Application_Model_DbTable_MonthLog();
        $where = '';

        switch ($node) {
            case 'baspubweb':
                $node = ['baspubweb1', 'baspubweb2'];
                break;
            case 'basprivweb':
                $node = ['basprivweb1', 'basprivweb2'];
                break;
        }

        if (is_array($node)) {
            $where = " node in ('" . implode($node, "','") . "')";
        } else if ($node != null) {
            $where = " node = '" . $node . "'";
        }

        $sql = "SELECT month, sum(error) as error, sum(notice) as notice, sum(warning) as warning
        FROM `month_log` WHERE $where
        group by month";

        $results = $db->getAdapter()->fetchAll($sql);

        //echo '<pre>';print_r($results);die;

        return $results;
    }
    

}

