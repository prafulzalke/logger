<?php

include_once('database.php');

class ErrorLog
{

    public function process($logFile, $date, $host, $ssh)
    {
        $database = new Database();
        $suffix = '';

        if (substr($logFile, -3) == 'bz2') {
            $suffix = '.bz2';
        }

        exec(sprintf("ssh $ssh cat /var/log/apache/%s/$logFile > /tmp/month_error_%s$suffix", str_replace('-', '/', $date), $date));

        if ($suffix != '') {
            shell_exec(sprintf("bzip2 -d -v /tmp/month_error_%s$suffix", $date));
        }

        if (file_exists('/tmp/month_fatal.txt')) {
            shell_exec("rm /tmp/month_fatal.txt");
        }

        shell_exec("grep 'PHP Fatal error:' /tmp/month_error_$date | wc -l > /tmp/month_fatal.txt");
        $error = file_get_contents('/tmp/month_fatal.txt');
        shell_exec("grep 'PHP Warning' /tmp/month_error_$date | wc -l > /tmp/month_warning.txt");
        $warning = file_get_contents('/tmp/month_warning.txt');
        shell_exec("grep 'PHP Notice' /tmp/month_error_$date | wc -l > /tmp/month_notice.txt");
        $notice = file_get_contents('/tmp/month_notice.txt');

        $result = [];
        $result['error'] = $error;
        $result['warning'] = $warning;
        $result['notice'] = $notice;
        $result['node'] = $host;
        $result['log_date'] = $date;
        $result['month'] = date('Ym', strtotime($date));

        $database->insertMonthData($result);

        shell_exec("rm /tmp/month_fatal.txt");
        shell_exec("rm /tmp/month_warning.txt");
        shell_exec("rm /tmp/month_notice.txt");
        shell_exec("rm /tmp/month_error_*");
    }

}

date_default_timezone_set('Europe/Amsterdam');
$errorLog = new ErrorLog();

/*$year = 2016;
$month = '04';

for ($i = 4; $i <= 6; $i++) {
    $dates[] = sprintf("%s-%s-%'.02d", $year, $month, $i);
}*/

$dates = date('Y-m-d',strtotime("-1 days"));
//$dates = ['2016-04-06'];

foreach ($dates as $date) {
    $extension = '';

    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb1', 'vandam@172.16.31.107');
    $errorLog->process("bastrucks.erp.err$extension", $date, 'basprivweb2', 'vandam@172.16.31.108');

    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb1', 'vandam@172.16.31.107');
    $errorLog->process("bastrucks.esb.err$extension", $date, 'basprivwebesb2', 'vandam@172.16.31.108');

}
