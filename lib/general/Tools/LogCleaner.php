<?php
namespace Intensa\Logger\Tools;

class LogCleaner
{
    public static function getOldLogsDirectories()
    {
        $objDirectoryController = new DirectoryController();
        $rootDirectoryItems = $objDirectoryController->getDirectoryItems();

    }

    public static function clear()
    {

    }
}