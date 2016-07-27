<?php

class Database
{
    public function insertData($data)
    {

        $serverName = "localhost";
        $username = "logger";
        $password = "*******";
        $database = "logger";
        // Create connection
        $conn = new mysqli($serverName, $username, $password, $database);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        foreach ($data as $row) {
            $sql = sprintf("INSERT INTO error_log (node, type, message, log_date, stack_trace, location, sub_type)
	        VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $row['node'],
                $row['type'],
                $conn->real_escape_string($row['message']),
                $row['log_date'],
                $conn->real_escape_string($row['stack_trace']),
                $conn->real_escape_string($row['location']),
                isset($row['sub_type']) ? $row['sub_type'] : NULL
            );

            if ($conn->query($sql) === TRUE) {
                echo '.';
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
        $conn->close();
    }

    public function getLatestDataLog($node = null)
    {
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
            $where = "where node in ('" . implode($node, "','") . "')";
        } else if ($node != null) {
            $where = "where node = '" . $node . "'";
        }

        $conn = $this->_getConnection();
        $sql = "SELECT node, type , log_date, count( * ) as cnt FROM error_log $where GROUP BY type , log_date ORDER BY log_date DESC";
        $result = $conn->query($sql);
        $results = [];
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $results[$row['log_date']][$row['type']] = $row['cnt'];
            }
        }
        $conn->close();

        return $results;
    }

    public function getCountForDashboard()
    {
        $conn = $this->_getConnection();
        $where = "where log_date = '" . date('Y-m-d') . "' and type = 'ERROR'";
        $sql = "SELECT node, count( * ) as cnt FROM error_log $where GROUP BY node ORDER BY log_date DESC";
        $result = $conn->query($sql);
        $results = [];
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $results[$row['node']] = $row['cnt'];
            }
        }
        $conn->close();

        return $results;
    }

    public function getTraceLog($type, $logDate, $node)
    {
        $and = '';

        switch ($node) {
            case 'baspubweb':
                $node = ['baspubweb1', 'baspubweb2'];
                break;
            case 'basprivweb':
                $node = ['basprivweb1', 'basprivweb2'];
                break;

        }

        if (is_array($node)) {
            $and = " and node in ('" . implode($node, "','") . "')";
        } else if ($node != null) {
            $and = " and node = '" . $node . "'";
        }


        $conn = $this->_getConnection();
        $sql = "SELECT message, count(*) as cnt,location, stack_trace FROM `error_log` WHERE log_date = '$logDate' and type= '$type' $and group by message order by cnt desc";
        $result = $conn->query($sql);
        $results = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $conn->close();

        return $results;
    }


    protected function _getConnection()
    {
        $serverName = "basext1";
        $username = "logger";
        $password = "xp45Fxnmu";
        $database = "logger";
        // Create connection
        $conn = new mysqli($serverName, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        return $conn;
    }
	
	public function deleteLog($logDate, $node)
	{
		$conn = $this->_getConnection();
        $sql = "delete from `error_log` WHERE date(log_date) = '$logDate' and node = '$node'";
        $result = $conn->query($sql);
        if ($result) {
            echo "Result deleted" . PHP_EOL;
        }
	}

    public function deleteCriticalLog($node)
    {
        $conn = $this->_getConnection();
        $sql = "delete from `error_log` WHERE sub_type = 'CRIT' and node = '$node'";
        $result = $conn->query($sql);
        echo "Result deleted :" . $result;
    }

    public function insertMonthData($row)
    {
        $conn = $this->_getConnection();
        $sql = sprintf('delete from month_log where log_date = "%s" and node = "%s"', $row['log_date'], $row['node']);
        $conn->query($sql);

        $sql = sprintf("INSERT INTO month_log (node, error, warning, notice, log_date, month)
	        VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
            $row['node'],
            $row['error'],
            $row['warning'],
            $row['notice'],
            $row['log_date'],
            $row['month']
        );

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully : " . $row['node'] . '\n';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    public function updateMantisId()
    {
        $conn = $this->_getConnection();
        $sql = "select mantis_id, assign, message, location, status from mantis";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $sql2 = sprintf("update error_log set mantis_id = %d, assign = '%s', status = '%s' where location = '%s'",
                    $row['mantis_id'], $row['assign'],$row['status'], $conn->real_escape_string($row['location']));
                $conn->query($sql2);
            }
        }

    }


}
