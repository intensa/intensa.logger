<?php
define("NOT_CHECK_PERMISSIONS", true);
define("NO_AGENT_CHECK", true);
$_SERVER["DOCUMENT_ROOT"] = __DIR__ . '/../../..';
var_dump($_SERVER['DOCUMENT_ROOT']);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
