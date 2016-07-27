<?php

class Application_Model_Mantis
{

    public function insert($data)
    {
        $db = new Application_Model_DbTable_Mantis();

        return $db->insert($data);

    }

    public function getIssueList()
    {
        $db = new Application_Model_DbTable_Mantis();
        $select = $db->select();
        $select->from(['m' => 'mantis'], ['userId' => 'user_id', 'cnt' => 'count(*)', 'status'])
            ->group(['m.user_id', 'm.status']);

        return $db->fetchAll($select);
    }

    public function update($data, $id)
    {
        $db = new Application_Model_DbTable_Mantis();
        $db->update($data, ['id = ?' => $id]);
    }
}

