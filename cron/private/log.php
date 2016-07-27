<?php

include_once('database.php');

class ErrorLog
{

    public function assignMantis()
    {
        $database = new Database();
        $database->updateMantisId();
    }

    public function getExplodeDate($text)
    {
        return substr($text, 12, 8);
    }

    public function process($logFile, $date, $host, $ssh, $params = null)
    {

        $database = new Database();
        $suffix = '';

        if (substr($logFile, -3) == 'bz2') {
            $suffix = '.bz2';
        }

        exec(sprintf("ssh $ssh cat /var/log/apache/%s/$logFile > error_%s$suffix", str_replace('-', '/', $date), $date));

        if ($suffix != '') {
            shell_exec(sprintf("bzip2 -d -v error_%s$suffix", $date));
        }

        if (file_exists('fatal.txt')) {
            shell_exec("rm fatal.txt");
        }

        shell_exec("grep 'PHP Fatal error:' error_$date > fatal.txt");

        $data = explode(PHP_EOL, file_get_contents('fatal.txt'));
        unset($data[0]);

        $result = [];

        $database->deleteLog($date, $host);

        foreach ($data as $key => $value) {

            if (empty($value)) {
                continue;
            }

            $newData = explode('PHP Fatal error:', $value);
            $value = $newData[1];
            $time = $this->getExplodeDate($newData[0]);

            $data2 = explode('with message', $value);
            if (isset($data2[1])) {
                $data3 = (explode('Stack trace', $data2[1]));
            } else {
                $data3 = (explode('Stack trace', $data2[0]));
            }

            $message = trim($data3[0], '\n');
            $messageData = explode(' in ', $message);

            $result[$key]['message'] = $messageData[0];
            $location = $messageData[1];

            $locationData = explode('referer', $location);

            $result[$key]['location'] = trim($locationData[0], ',');
            $result[$key]['referer'] = isset($locationData[1]) ? trim($locationData[1], ' :') : null;
            $result[$key]['node'] = $host;
            $result[$key]['type'] = 'ERROR';
            $result[$key]['log_date'] = $date . ' ' . $time;

            if (isset($data3[1])) {
                $result[$key]['stack_trace'] = trim($data3[1], '\n');
            } else {
                $result[$key]['stack_trace'] = '';
            }
        }

        $database->insertData($result);

        $result = [];
        if (file_exists('warning.txt')) {
            shell_exec("rm warning.txt");
        }

        shell_exec("grep 'PHP Warning' error_$date > warning.txt");
        $data = explode(PHP_EOL, file_get_contents('warning.txt'));

        foreach ($data as $key => $value) {

            if (empty($value)) {
                continue;
            }

            $newData = explode('PHP Warning:', $value);
            $value = $newData[1];
            $time = $this->getExplodeDate($newData[0]);

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
            $result[$key]['log_date'] = $date . ' ' . $time;
            $result[$key]['referer'] = null;

        }
        $database->insertData($result);

        // inserting notice
        $result = [];
        if (file_exists('notice.txt')) {
            shell_exec("rm notice.txt");
        }

        shell_exec("grep 'PHP Notice' error_$date > notice.txt");
        $data = explode(PHP_EOL, file_get_contents('notice.txt'));


        foreach ($data as $key => $value) {

            if (empty($value)) {
                continue;
            }

            $newData = explode('PHP Notice:', $value);
            $value = $newData[1];
            $time = $this->getExplodeDate($newData[0]);

            $data1 = explode('referer', $value);

            if (isset($data1[0]) && isset($data1[1])) {
                $result[$key]['message'] = trim($data1[0]);
                $result[$key]['stack_trace'] = trim($data1[1]);
            } else {
                $result[$key]['message'] = trim($value);
                $result[$key]['stack_trace'] = '';
            }

            $messageData = explode(' in ', $result[$key]['message']);

            if (!isset($messageData[1])) {
                unset($result[$key]);
                continue;
            }

            $result[$key]['message'] = $messageData[0];
            $result[$key]['location'] = $messageData[1];

            $result[$key]['node'] = $host;
            $result[$key]['type'] = 'NOTICE';
            $result[$key]['log_date'] = $date . ' ' . $time;
            $result[$key]['referer'] = null;

        }
        $database->insertData($result);
        shell_exec("rm fatal.txt");
        shell_exec("rm warning.txt");
        shell_exec("rm notice.txt");
        shell_exec("rm error_*");

        $this->assignMantis();
    }

}

date_default_timezone_set('Europe/Amsterdam');
$errorLog = new ErrorLog();

$dates = [date('Y-m-d')];
foreach ($dates as $date) {

    $extension = '';

    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb1', 'vandam@172.16.31.107', null);
    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb2', 'vandam@172.16.31.108', null);

    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb1', 'vandam@172.16.31.107', null);
    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb2', 'vandam@172.16.31.108', null);
}


