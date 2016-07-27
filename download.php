<?php

class Database
{
    public function insertData($data)
    {

        $serverName = "localhost";
        $username = "root";
        $password = "root";
        $database = "bastrucks_logs";
        // Create connection
        $conn = new mysqli($serverName, $username, $password, $database);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        foreach ($data as $row) {
            $sql = sprintf("INSERT INTO error_log (node, type, message, log_date, stack_trace)
	        VALUES ('%s', '%s', '%s', '%s', '%s')", $row['node'], $row['type'], $conn->real_escape_string($row['message']), $row['log_date'], $conn->real_escape_string($row['stack_trace']));

            if ($conn->query($sql) === TRUE) {
                echo "New record created successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
        $conn->close();
    }


//shell_exec("ssh vandam@172.16.32.103 cat /var/log/apache/2016/03/08/bastrucks.website.err.bz2 > error_20160308.bz2");

}
