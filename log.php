<?php

include_once('database.php');

class ErrorLog
{

    public function prepareNoticeData($log, $host, $date)
    {
        $result = [];

        $log = explode('PHP Notice:', $log);
        $data1 = explode('referer', $log[1]);

        if (isset($data1[0]) && isset($data1[1])) {
            $result['message'] = trim($data1[0]);
            $result['stack_trace'] = trim($data1[1]);
        } else {
            $result['message'] = trim($log[1]);
            $result['stack_trace'] = '';
        }

        $messageData = explode(' in ', $result['message']);

        $result['message'] = $messageData[0];
        $result['location'] = $messageData[1];

        $locationData = explode('referer', $result['location']);
        $result['referer'] = isset($locationData[1]) ? trim($locationData[1], ' :') : null;

        $result['node'] = $host;
        $result['type'] = 'NOTICE';
        $result['log_date'] = date('Y-m-d H:i:s', strtotime($date));

        return $result;
    }

    public function prepareWarningData($log, $host, $date)
    {
        $result = [];

        $log = explode('PHP Warning:', $log);
        $data1 = explode('referer', $log[1]);

        if (isset($data1[0]) && isset($data1[1])) {
            $result['message'] = trim($data1[0]);
            $result['stack_trace'] = trim($data1[1]);
        } else {
            $result['message'] = trim($log[1]);
            $result['stack_trace'] = '';
        }

        $messageData = explode(' in ', $result['message']);

        $result['message'] = $messageData[0];
        $result['location'] = $messageData[1];
        $locationData = explode('referer', $result['location']);
        $result['referer'] = isset($locationData[1]) ? trim($locationData[1], ' :') : null;

        $result['node'] = $host;
        $result['type'] = 'WARNING';
        $result['log_date'] = date('Y-m-d H:i:s', strtotime($date));
        return $result;
    }

    public function prepareErrorData($log, $host, $date)
    {
        $result = [];

        $log = explode('PHP Fatal error:', $log);

        $data2 = explode('with message', $log[1]);
        if (isset($data2[1])) {
            $data3 = (explode('Stack trace', $data2[1]));
        } else {
            $data3 = (explode('Stack trace', $data2[0]));
        }

        $message = trim($data3[0], '\n');
        $messageData = explode(' in ', $message);

        $result['message'] = $messageData[0];
        $result['location'] = $messageData[1];
        $locationData = explode('referer', $result['location']);
        $result['referer'] = isset($locationData[1]) ? trim($locationData[1], ' :') : null;
        $result['node'] = $host;
        $result['type'] = 'ERROR';
        $result['log_date'] = date('Y-m-d H:i:s', strtotime($date));

        if (isset($data3[1])) {
            $result['stack_trace'] = trim($data3[1], '\n');
        } else {
            $result['stack_trace'] = '';
        }

        return $result;
    }

    public function prepareOtherData($log, $ip, $host, $date)
    {
        $result = [];
        $log = explode("$ip] ", $log);
        $otherData = explode('referer', $log[1]);

        $result['message'] = $otherData[0];
        $result['location'] = $otherData[0];
        $result['referer'] = isset($otherData[1]) ? $otherData[1] : null;
        $result['node'] = $host;
        $result['type'] = 'WARNING';
        $result['log_date'] = date('Y-m-d H:i:s', strtotime($date));
        $result['stack_trace'] = '';

        return $result;
    }

    public function assignMantis()
    {
        $database = new Database();
        $database->updateMantisId();
    }

    public function process($logFile, $date, $host, $ssh)
    {

        $results = [];
        $database = new Database();
        $suffix = '';

        if (substr($logFile, -3) == 'bz2') {
            $suffix = '.bz2';
        }

        echo "Reading log file started for node :" . $host . PHP_EOL;
        exec(sprintf("ssh $ssh cat /var/log/apache/%s/$logFile > error_%s$suffix", str_replace('-', '/', $date), $date));

        if ($suffix != '') {
            shell_exec(sprintf("bzip2 -d -v error_%s$suffix", $date));
        }

        if (file_exists('fatal.txt')) {
            shell_exec("rm fatal.txt");
        }

        echo "Log file processed" . PHP_EOL;
        $logs = explode(PHP_EOL, file_get_contents("error_$date"));
        $database->deleteLog($date, $host);


        foreach ($logs as $log) {
            if (!empty($log)) {

                preg_match("/(?P<date>\w{3}\s\w{3}\s\d{2}\s\d{2}:\d{2}:\d{2}\s\d{4})/i", $log, $dateMatch);
                preg_match("/(?P<ipAddress>\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b)/i", $log, $ipMatch);

                preg_match("/(?P<type>PHP Notice:)/i", $log, $type);

                if (!empty($type)) {
                    $results[] = $this->prepareNoticeData($log, $host, $dateMatch['date']);
                    continue;
                }

                preg_match("/(?P<type>PHP Warning:)/i", $log, $type);

                if (!empty($type)) {
                    $results[] = $this->prepareWarningData($log, $host, $dateMatch['date']);
                    continue;
                }

                preg_match("/(?P<type>PHP Fatal error:)/i", $log, $type);

                if (!empty($type)) {
                    $results[] = $this->prepareErrorData($log, $host, $dateMatch['date']);
                    continue;
                }

                $results[] = $this->prepareOtherData($log, $ipMatch['ipAddress'], $host, $dateMatch['date']);
            }
        }

        $database->insertData($results);

        shell_exec("rm error_*");

        $this->assignMantis();
        echo PHP_EOL . 'Process done for node :' . $host . PHP_EOL;
    }

}

date_default_timezone_set('Europe/Amsterdam');
$errorLog = new ErrorLog();

$dates = [date('Y-m-d')];
foreach ($dates as $date) {

    $extension = '';

    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb1', 'vandam@172.16.32.103');
    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb2', 'vandam@172.16.32.104');
	
    //$errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb1', 'vandam@172.16.31.107', null);
    //$errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb2', 'vandam@172.16.31.108', null);

    //$errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb1', 'vandam@172.16.31.107', null);
    //$errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb2', 'vandam@172.16.31.108', null);*/
}


