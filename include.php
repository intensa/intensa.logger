<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('intensa.logger', [
    'Intensa\Logger\Settings' => 'lib/general/Settings.php',
    'Intensa\Logger\ILog' => 'lib/general/ILog.php',
    'Intensa\Logger\ILogAlert' => 'lib/general/ILogAlert.php',
    'Intensa\Logger\ILogTimer' => 'lib/general/ILogTimer.php',
    'Intensa\Logger\ILogSql' => 'lib/general/ILogSql.php',
    'Intensa\Logger\Tools\LogCleaner' => 'lib/general/Tools/LogCleaner.php',
    'Intensa\Logger\Tools\DirectoryController' => 'lib/general/Tools/DirectoryController.php',
]);
