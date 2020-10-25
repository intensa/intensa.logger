<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Новая страница");

if (CModule::IncludeModule('intensa.logger')) {
    $logger = new \Intensa\Logger\ILog('test');
    $logger->log('test', [1,2,3]);
}

?>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>