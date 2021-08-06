<?php
set_time_limit(0);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('intensa.logger');



$obj = new \Intensa\Logger\Reader\DirectoryController();
$dirs = $obj->getDirectoryItems();
var_dump($dirs);

