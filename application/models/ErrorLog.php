<?php

class Application_Model_ErrorLog
{

    public function getTraceLog($type, $logDate, $node)
    {
        $db = new Application_Model_DbTable_ErrorLog();
        switch ($node) {
            case 'baspubweb':
                $node = ['baspubweb1', 'baspubweb2'];
                break;
            case 'basprivweb':
                $node = ['basprivweb1', 'basprivweb2'];
                break;
        }

        $select = $db->select()->from(['e' => 'error_log'],
            [
                'message' => 'message',
                'location' => 'location',
                'stack_trace' => 'stack_trace',
                'mantis_id' => 'mantis_id',
                'assign' => 'assign',
                'status' => 'status',
                'cnt' => 'count(*)',
                'log_date' => 'log_date',
                'referer' => 'referer',
                //'log_date' => new Zend_Db_Expr('(select e2.log_date from error_log as e2 where e2.message = e.message order by e2.log_date desc limit 1)')
            ]
        );
        $select->setIntegrityCheck(false);
        $select->joinLeft(['m' => 'mantis'], 'e.mantis_id = m.id', ['mantis_created', 'comment', 'issue_fixed']);
        $select->where('date(log_date) = ?', $logDate);

        if ($type == 'Fixed') {
            $select->where('e.status = ?', $type);
        } else {
            $select->where('e.type = ?', $type);
        }
        $select->where('e.node in (?)', $node);
        $select->group('e.message');
        $select->order('cnt desc');

        $results = $db->fetchAll($select);

        return $results;
    }

    public function getAssignedIssue($userId)
    {
        $db = new Application_Model_DbTable_ErrorLog();
        $select = $db->select()->from(['e' => 'error_log'],
            [
                'message' => 'message',
                'location' => 'location',
                'stack_trace' => 'stack_trace',
                'mantis_id' => 'mantis_id',
                'assign' => 'assign',
                'status' => 'status',
                'cnt' => 'count(*)',
                'log_date' => 'log_date',
                'referer' => 'referer',
                //'log_date' => new Zend_Db_Expr('(select e2.log_date from error_log as e2 where e2.message = e.message order by e2.log_date desc limit 1)')
            ]
        );
        $select->setIntegrityCheck(false);
        $select->join(['u' => 'user'], 'u.name = e.assign', []);
        $select->joinLeft(['m' => 'mantis'], 'e.mantis_id = m.id', ['mantis_created', 'comment', 'issue_fixed']);
        $select->where('e.status = ?', 'Open');
        $select->where('u.id = ?', $userId);
        $select->group('e.message');
        $select->order('cnt desc');

        $results = $db->fetchAll($select);

        return $results;
    }
    
    public function getLatestDataLog($node = 'baspubweb')
    {
        $nodeReq = $node;
        $db = new Application_Model_DbTable_ErrorLog();
        $where = '';

        switch ($node) {
            case 'basprivweb':
                $node = ['basprivweb1', 'basprivweb2'];
                break;
            case 'baspubweb':
                $node = ['baspubweb1', 'baspubweb2'];
                break;
        }

        if (is_array($node)) {
            $where = "where node in ('" . implode($node, "','") . "')";
        } else if ($node != null) {
            $where = "where node = '" . $node . "'";
        }

        $sql = "SELECT node, type , date(log_date) as log_date, count( * ) as cnt FROM error_log $where GROUP BY type , date(log_date) ORDER BY log_date DESC";
        $results = $db->getAdapter()->fetchAll($sql);
        
        $logs = [];
        foreach ($results as $row) {
            $logs[$row['log_date']][$row['type']] = $row['cnt'];
        }

        $select = $db->select()->from(['e' => 'error_log'], ['cnt' => 'count(*)']);
        $select->where('node in (?)', $node);
        $select->where('status = "Fixed"');
        $select->where('date(log_date) = ?', date('Y-m-d'));

        $fixedResult = $db->fetchRow($select);

        return [
            'result' => $logs,
            'node' => $nodeReq,
            'fixed' => $fixedResult->cnt
        ];
    }

    public function updateMantisId($mantisId, $message, $location, $params = [])
    {
        $db = new Application_Model_DbTable_ErrorLog();

        $data = ['mantis_id' => $mantisId];

        if (isset($params['assign'])) {
            $data['assign'] = $params['assign'];
        }

        if (isset($params['status'])) {
            $data['status'] = $params['status'];
        }

        $db->update($data, ["message = ?" => $message]);

        if (strlen($location) > 50) {
            $db->update(['mantis_id' => $mantisId], ["location = ?" => $location]);
        }
    }

    public function update($data, $id)
    {
        $db = new Application_Model_DbTable_ErrorLog();
        $db->update($data, ['mantis_id = ?' => $id]);
    }

    public function findByMessage($message, $node)
    {
        $where = ['message = ?' => $message];

        switch ($node) {
            case 'basprivweb':
                $where['node in (?)'] = ['basprivweb1', 'basprivweb2'];
                break;
            case 'basprivweb1':
                $where['node in (?)'] = ['basprivweb1'];
                break;
            case 'basprivweb2':
                $where['node in (?)'] = ['basprivweb2'];
                break;
            case 'baspubweb':
                $where['node in (?)'] = ['baspubweb1', 'baspubweb2'];
                break;
            case 'baspubweb1':
                $where['node in (?)'] = ['baspubweb1'];
                break;
            case 'baspubweb2':
                $where['node in (?)'] = ['baspubweb1'];
                break;
        }

        $db = new Application_Model_DbTable_ErrorLog();
        return $db->fetchAll($where, 'log_date desc', 300);
    }
}

