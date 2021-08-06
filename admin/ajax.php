<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('intensa.logger');
$request = json_decode(file_get_contents('php://input'), true);

if (!empty($request)) {
    $obj = new \Intensa\Logger\Reader\DirectoryController();
    if ($request['method'] === 'init') {
        $return = $obj->getDirectoryItems();
    } elseif ($request['method'] === 'openDirectory') {
        $return = $obj->getDirectoryItems($request['path']);
    }
    echo json_encode($return);
}
