<?php
class Default_View_Helper_Time extends Zend_View_Helper_Abstract
{
    public function time($time)
    {
        if ($time == '0000-00-00 00:00:00') {
            echo ''; return;
        }

        $start_date = new DateTime($time);
        $since_start = $start_date->diff(new DateTime(date('Y-m-d H:i:s')));

        if ($since_start->d > 0) {
            echo date('d-m-Y H:i:s', strtotime($time));
            return;
        }

        if ($since_start->h > 0) {
            echo $since_start->h . ' hours ';
        }

        if ($since_start->i > 0) {
            echo $since_start->i . ' minutes ';
        } else if ($since_start->i == 1) {
            echo $since_start->i . ' minute ';
        } else {
            echo ' 0 minute ';
        }

        echo 'ago';
    } 
}
