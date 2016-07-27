<?php

echo 'ssssss'; die;

class exception
{
    public function process()
    {
        echo 'ssss';
        error_reporting(E_ALL);
        $content = file_get_contents('my_log');
        $logs = explode('2016-04', $content);
        echo count($logs);die;
    }

}
echo '4444';die;
$exception = new Exception();
$exception->process();