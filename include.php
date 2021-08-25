<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('intensa.logger', array(
    'Intensa\Logger\Settings'	=> 'lib/general/Settings.php',
    'Intensa\Logger\ILog'	=> 'lib/general/ILog.php',
    'Intensa\Logger\ILogAlert'	=> 'lib/general/ILogAlert.php',
    'Intensa\Logger\ILogTimer'	=> 'lib/general/ILogTimer.php',
    'Intensa\Logger\ILogReader'	=> 'lib/general/ILogReader.php',
    'Intensa\Logger\Tools\LogCleaner'	=> 'lib/general/Tools/LogCleaner.php',
    'Intensa\Logger\Tools\DirectoryController'	=> 'lib/general/Tools/DirectoryController.php',
));
