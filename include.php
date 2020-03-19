<?php
use Bitrix\Main\Loader;
//use Bitrix\Main\EventManager;

Loader::registerAutoLoadClasses('intensa.logger', array(
    'Intensa\Logger\Settings'	=> 'classes/general/Settings.php',
    'Intensa\Logger\ILog'	=> 'classes/general/ILog.php',
    'Intensa\Logger\ILogAlert'	=> 'classes/general/ILogAlert.php',
    'Intensa\Logger\ILogTimer'	=> 'classes/general/ILogTimer.php',
));
