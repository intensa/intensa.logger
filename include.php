<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('intensa.logger', array(
    'Intensa\Logger\Settings'	=> 'lib/general/Settings.php',
    'Intensa\Logger\ILog'	=> 'lib/general/ILog.php',
    'Intensa\Logger\ILogAlert'	=> 'lib/general/ILogAlert.php',
    'Intensa\Logger\ILogTimer'	=> 'lib/general/ILogTimer.php',
    'Intensa\Logger\Reader\DirectoryController'	=> 'lib/general/Reader/DirectoryController.php',
));
