<?php

include_once('database.php');

class ErrorLog
{

    public function assignMantis()
    {
        $database = new Database();
        $database->updateMantisId();
    }

    public function process($logFile, $date, $host, $ssh)
    {

        $database = new Database();
        $suffix = '';

        if (substr($logFile, -3) == 'bz2') {
            $suffix = '.bz2';
        }

        exec(sprintf("ssh $ssh cat /var/log/apache/%s/$logFile > /tmp/error_%s$suffix", str_replace('-', '/', $date), $date));

        if ($suffix != '') {
            shell_exec(sprintf("bzip2 -d -v /tmp/error_%s$suffix", $date));
        }

        if (file_exists('/tmp/fatal.txt')) {
            shell_exec("rm /tmp/fatal.txt");
        }

        shell_exec("grep 'PHP Fatal error:' /tmp/error_$date > /tmp/fatal.txt");

        $data = explode('PHP Fatal error:', file_get_contents('/tmp/fatal.txt'));
        unset($data[0]);

        $result = [];
        $database->deleteLog($date, $host);

        foreach ($data as $key => $value) {
            $data2 = explode('with message', $value);
            if (isset($data2[1])) {
                $data3 = (explode('Stack trace', $data2[1]));
            } else {
                $data3 = (explode('Stack trace', $data2[0]));
            }

            $message = trim($data3[0], '\n');
            $messageData = explode(' in ', $message);

            $result[$key]['message'] = $messageData[0];
            $result[$key]['location'] = $messageData[1];
            $result[$key]['node'] = $host;
            $result[$key]['type'] = 'ERROR';
            $result[$key]['log_date'] = $date;

            if (isset($data3[1])) {
                $result[$key]['stack_trace'] = trim($data3[1], '\n');
            } else {
                $result[$key]['stack_trace'] = '';
            }
        }

        $database->insertData($result);
        $result = [];
        if (file_exists('/tmp/warning.txt')) {
            shell_exec("rm /tmp/warning.txt");
        }

        shell_exec("grep 'PHP Warning' /tmp/error_$date > /tmp/warning.txt");
        $data = explode('PHP Warning:', file_get_contents('/tmp/warning.txt'));

        foreach ($data as $key => $value) {
            $data1 = explode('referer', $value);

            if (isset($data1[0]) && isset($data1[1])) {
                $result[$key]['message'] = trim($data1[0]);
                $result[$key]['stack_trace'] = trim($data1[1]);
            } else {
                $result[$key]['message'] = trim($value);
                $result[$key]['stack_trace'] = '';
            }

            $messageData = explode(' in ', $result[$key]['message']);

            $result[$key]['message'] = $messageData[0];
            $result[$key]['location'] = $messageData[1];

            $result[$key]['node'] = $host;
            $result[$key]['type'] = 'WARNING';
            $result[$key]['log_date'] = $date;

        }
        $database->insertData($result);
        // inserting notice
        $result = [];
        if (file_exists('/tmp/notice.txt')) {
            shell_exec("rm /tmp/notice.txt");
        }

        shell_exec("grep 'PHP Notice' /tmp/error_$date > /tmp/notice.txt");
        $data = explode('PHP Notice', file_get_contents('/tmp/notice.txt'));


        foreach ($data as $key => $value) {
            $data1 = explode('referer', $value);

            if (isset($data1[0]) && isset($data1[1])) {
                $result[$key]['message'] = trim($data1[0]);
                $result[$key]['stack_trace'] = trim($data1[1]);
            } else {
                $result[$key]['message'] = trim($value);
                $result[$key]['stack_trace'] = '';
            }

            $messageData = explode(' in ', $result[$key]['message']);

            $result[$key]['message'] = $messageData[0];
            $result[$key]['location'] = $messageData[1];
            $result[$key]['node'] = $host;
            $result[$key]['type'] = 'NOTICE';
            $result[$key]['log_date'] = $date;

        }
        $database->insertData($result);
        shell_exec("rm /tmp/fatal.txt");
        shell_exec("rm /tmp/warning.txt");
        shell_exec("rm /tmp/notice.txt");
        shell_exec("rm /tmp/error_*");

        $this->assignMantis();
    }

}


date_default_timezone_set('Europe/Amsterdam');
$errorLog = new ErrorLog();

$dates = [date('Y-m-d')];

foreach ($dates as $date) {
    $extension = '';
    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb1', 'vandam@172.16.32.103');
    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb2', 'vandam@172.16.32.104');
}


