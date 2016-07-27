<?php

include_once('database.php');

class ErrorLog
{

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

        shell_exec("grep 'PHP Fatal error:' error_$date | wc -l > fatal.txt");
        $error = file_get_contents('fatal.txt');
        shell_exec("grep 'PHP Warning' error_$date | wc -l > warning.txt");
        $warning = file_get_contents('warning.txt');
        shell_exec("grep 'PHP Notice' error_$date | wc -l > notice.txt");
        $notice = file_get_contents('notice.txt');

        $result = [];
        $result['error'] = $error;
        $result['warning'] = $warning;
        $result['notice'] = $notice;
        $result['node'] = $host;
        $result['log_date'] = $date;
        $result['month'] = date('Ym', strtotime($date));

        $database->insertMonthData($result);

        shell_exec("rm fatal.txt");
        shell_exec("rm warning.txt");
        shell_exec("rm notice.txt");
        shell_exec("rm error_*");
    }

}

$errorLog = new ErrorLog();

/*$year = 2016;
$month = '04';

for ($i = 4; $i <= 6; $i++) {
    $dates[] = sprintf("%s-%s-%'.02d", $year, $month, $i);
}*/

$dates[] = date('Y-m-d',strtotime("-1 days"));

foreach ($dates as $date) {
    //$date = '2016-03-24'; //date('Y-m-d');
    $extension = '';

    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb1', 'vandam@172.16.32.103', null);
    $errorLog->process("bastrucks.website.err$extension", $date, 'baspubweb2', 'vandam@172.16.32.104', null);

    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb1', 'vandam@172.16.31.107', null);
    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb2', 'vandam@172.16.31.108', null);

    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb1', 'vandam@172.16.31.107', null);
    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb2', 'vandam@172.16.31.108', null);
}
