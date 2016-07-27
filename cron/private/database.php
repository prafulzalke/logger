<?php

class Database
{
    public function insertData($data)
    {

        $conn = $this->_getConnection();
        foreach ($data as $row) {
            $sql = sprintf("INSERT INTO error_log (node, type, message, log_date, stack_trace, location, sub_type, referer)
	        VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $row['node'],
                $row['type'],
                $row['message'],
                $row['log_date'],
                $row['stack_trace'],
                $row['location'],
                isset($row['sub_type']) ? $row['sub_type'] : NULL,
                $row['referer']
            );

            if ($conn->exec($sql)) {
                echo '.';
            }
        }

    }

    protected function _getConnection()
    {
        $serverName = "basext1";
        $username = "logger";
        $password = "*******";
        $conn = new PDO("mysql:host=$serverName;dbname=logger", $username, $password);

        return $conn;
    }
	
	public function deleteLog($logDate, $node)
	{
		$conn = $this->_getConnection();
        $sql = "delete from `error_log` WHERE date(log_date) = '$logDate' and node = '$node'";
		$conn->exec($sql);

	}

    public function updateMantisId()
    {
        $conn = $this->_getConnection();
        $sql = "select id, assign, message, location, status from mantis";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        foreach ($stmt->fetchAll() as $row) {
            $sql2 = sprintf("update error_log set mantis_id = %d, assign = '%s', status = '%s' where location = '%s'",
                $row['id'], $row['assign'],$row['status'], $row['location']);
            $conn->exec($sql2);

            $sql3 = sprintf("update error_log set mantis_id = %d, assign = '%s', status = '%s' where message = '%s'",
                $row['id'], $row['assign'],$row['status'], $row['message']);
            $conn->exec($sql3);
        }


    }
	
	public function insertMonthData($row)
    {
        $conn = $this->_getConnection();

        $sql = sprintf('delete from month_log where log_date = "%s" and node = "%s"', $row['log_date'], $row['node']);
        $conn->exec($sql);

        $sql = sprintf("INSERT INTO month_log (node, error, warning, notice, log_date, month)
	        VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
            $row['node'],
            $row['error'],
            $row['warning'],
            $row['notice'],
            $row['log_date'],
            $row['month']
        );

        if ($conn->exec($sql)) {
                echo '.';
            }
    }


}
