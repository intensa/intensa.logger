<?php
set_time_limit(0);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('intensa.logger');

$fileText = file_get_contents('file.txt');
//var_dump($fileText);
$obj = new \Intensa\Logger\ILog('test10');
$range = range(1, 1000);
$obj->startTimer('common');
foreach ($range as $item) {

    $obj->log('test' . $item, [$fileText]);
    //sleep(1);
}
$obj->stopTimer();
