<?php
set_time_limit(0);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('intensa.logger');


$obj = new \Intensa\Logger\ILog('my_test');
$obj->setAdditionalDir('add');
$obj->startTimer('start');
$obj->log('11', 1211);

$obj->log('22', 22211);

$obj->log('33', 32311);


die();
$fileText = file_get_contents('file.txt');
//var_dump($fileText);
$obj = new \Intensa\Logger\ILog('test10');
var_dump($obj);
$range = range(1, 1000);
var_dump($range);
$obj->startTimer('common');
foreach ($range as $item) {

    $obj->log('test' . $item, [$fileText]);
    //sleep(1);
}
$obj->stopTimer();
