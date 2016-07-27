<?php
class Default_View_Helper_PrintVal extends Zend_View_Helper_Abstract
{
    public function printVal($val, $dataSet = null)
    {
        if ($dataSet !== null && isset($dataSet[$val])) {
            echo number_format($dataSet[$val], 0);
        } else {
            echo 0;
        }
    } 
}
