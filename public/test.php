<?php

include_once('../database.php');

class test
{
    public function process($node, $explode)
    {
        $content = file_get_contents('my_log_' . $node);
        $logs = explode('2016-04', $content);
        $results = [];
        foreach ($logs as $log) {
            if (strpos($log, $explode) == true) {
                $stackTrace = '';
                $location = '';
                $string = explode($explode, $log);
                $time = $string[0];
                $string = explode(' in ', $string[1]);
                $message = $string[0];
		        if (isset($string[1])) {
                    $string = explode('Stack trace:', $string[1]);
                    $location = $string[0];
                    $stackTrace = isset($string[1]) ? $string[1] : '';
                } else {

                }
                $results[] = [
                    'type' => 'ERROR',
                    'sub_type' => 'CRIT',
                    'message' => trim($message),
                    'location' => trim($location),
                    'stack_trace' => trim($stackTrace),
                    'log_date' => date('Y-m-d H:i:s', strtotime(substr('2016-04' . $time, 0, 25))),
                    'node' => $node
                ];
            }
        }

        $database = new Database();
        $database->deleteCriticalLog($node);
        $database->insertData($results);
    }
}

$test = new test();
$test->process('basprivweb1', 'exception');
$test->process('basprivweb2', 'exception');
