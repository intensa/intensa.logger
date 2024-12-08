<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
require  __DIR__ . '/../include.php';

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

if (class_exists('intensa_logger')) {
    return;
}

class intensa_logger extends CModule
{
    const DEFAULT_EVENT_TYPE = 'INTENSA_LOGGER_ALERT';
    const DEFAULT_EVENT_MESSAGE = 'INTENSA_LOGGER_FATAL_TEMPLATE';
    public $MODULE_ID = 'intensa.logger';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    protected $errors = [];

    public function __construct()
    {
        $arModuleVersion = [];
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('LOGGER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('LOGGER_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'Intensa';
        $this->PARTNER_URI = 'https://intensa.ru';
    }

    public function DoInstall()
    {
        global $APPLICATION;
        $createDir = $this->createDirectory();
        $this->checkPermission($createDir);
        $this->createAccessFile($createDir);

        $this->installAgents();

        \Intensa\Logger\Tools\Settings::getInstance()->installOptions();

        if (!empty($this->errors)) {
            $APPLICATION->ThrowException(implode('<br>', $this->errors));
            return false;
        } else {
            ModuleManager::registerModule($this->MODULE_ID);
        }
    }

    public function DoUninstall()
    {
        $this->removeSendEvent();
        $this->unInstallAgents();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function installAgents(): bool
    {
        \Intensa\Logger\Tools\LogCleaner::installAgent();
        return true;
    }

    public function unInstallAgents(): bool
    {
        \Intensa\Logger\Tools\LogCleaner::deleteAgent();
        return true;
    }

    public function createDirectory()
    {
        $dirPath = $_SERVER['DOCUMENT_ROOT'] . '/logs/';

        if (!file_exists($dirPath)) {
            $mkdir = mkdir($dirPath, 0775);

            if (!$mkdir) {
                $this->errors[] = 'Ошибка создания основной директории для логов ' . $dirPath;
                return false;
            }
        }

        chmod($dirPath, 0775);

        return $dirPath;
    }

    public function createAccessFile($createPath)
    {
        $result = file_put_contents($createPath . '.htaccess', 'Deny from all');

        if (!$result) {
            $this->errors[] = 'Ошибка при создании .htaccess файла';
        }
    }

    public function checkPermission($file)
    {
        if (is_writable($file)) {
            return true;
        } else {
            $this->errors[] = 'Проблема с правами директории ' . $file;
        }
    }
}
