<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Intensa\Logger\Settings;

//Loc::loadMessages(__FILE__);

if (class_exists('intensa_logger')) {
    return;
}

class intensa_logger extends CModule
{
    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public $moduleSettings = null;
    protected $errors = [];

    public function __construct()
    {
        $this->MODULE_ID = 'intensa.logger';
        $this->MODULE_VERSION = '0.0.1';
        $this->MODULE_VERSION_DATE = '2018-09-28 10:10:10';
        $this->MODULE_NAME = 'Логирование';
        $this->MODULE_DESCRIPTION = 'Логирование в файлы';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = "Intensa";
        $this->PARTNER_URI = "http://intensa.ru";
    }

    public function doInstall()
    {
        global $APPLICATION;

        $settingsInclude = $this->includeSettings();

        if ($settingsInclude) {
            $createDir = $this->createDirectory();
            $this->checkPermission($createDir);
            $this->createSendEvent();
        } else {
            $errorMgs = 'Не удалось получить файл конфигурации модуля. Пожалуйста, убедитесь, что файл logger.config.php присутствует в папке модуля.';

            if (file_exists(__DIR__ . '/../logger.config.example.php')) {
                $errorMgs .= '<br>Найден файл примера конфига. Нужно изменить название данного файла (<b>' . realpath(__DIR__ . '/../logger.config.example.php') . '</b>) на <b>logger.config.php</b> и изменить нужные настройки';
            } else {
                $errorMgs .= '<br>Пожалуйста, обратитесь в поддержку <a href="mailto:i.shishkin@intensa.ru">i.shishkin@intensa.ru</a>';
            }

            $this->errors[] = $errorMgs;
        }

        if (!empty($this->errors)) {
            $APPLICATION->ThrowException(implode('<br>', $this->errors));
            return false;
        } else {
            ModuleManager::registerModule($this->MODULE_ID);
        }
    }

    public function doUninstall()
    {
        //$this->uninstallDB();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function createDirectory()
    {
        $result = false;
        $defDirName = $this->moduleSettings['LOG_DIR'];

        if (empty($defDirName)) {
            $this->errors[] = 'Не установлена основная директория. Проверьте настройки файла logger.config.php';
        } else {
            $dirPath = $_SERVER['DOCUMENT_ROOT'] . $defDirName;

            if (!file_exists($dirPath)) {
                $mkdir = mkdir($dirPath, 0777);

                if (!$mkdir) {
                    $this->errors[] = 'Ошибка создания основной директории для логов ' . $dirPath;
                }
            }

            chmod($dirPath, 0777);
            $result = $dirPath;
            $this->checkPermission($dirPath);
        }

        return $result;
    }

    public function includeSettings()
    {
        $return = false;
        $settingsFilePath = __DIR__ . '/../logger.config.php';
        $settingsFile = include_once(realpath($settingsFilePath));

        if ($settingsFile && is_array($settingsFile)) {
            $this->moduleSettings = $settingsFile;
            $return = true;
        } else {
            $this->errors[] = 'Проблема с получанием настроек из файла logger.config.php';
        }

        return $return;
    }

    public function createAccessFile($createPath)
    {
        // create access file
    }

    public function checkPermission($file)
    {
        if (is_writable($file)) {
            return true;
        } else {
            $this->errors[] = 'Проблема с правами директории ' . $file;
        }
    }

    public function createSendEvent()
    {
        global $APPLICATION;

        $result = false;
        $objCEventType = new \CEventType;

        $filterCEventType = ['TYPE_ID' => $this->moduleSettings['CEVENT_TYPE']];
        $objResultCEventType = $objCEventType->GetList($filterCEventType);

        if ($eventType = $objResultCEventType->Fetch()) {
            $createType = $eventType['ID'];
        } else {
            $createType = $objCEventType->Add([
                'LID' => 'ru',
                'EVENT_NAME' => $this->moduleSettings['CEVENT_TYPE'],
                'NAME' => 'intensa.logger',
            ]);
        }

        if ($createType) {
            $objCEventMessage = new \CEventMessage;
            $objResultCEventMessage = $objCEventMessage->GetList($by = 'id', $order = 'desc',
                ['TYPE_ID' => $this->moduleSettings['CEVENT_TYPE']]);

            if ($eventMessage = $objResultCEventMessage->Fetch()) {
                $createMessage = true;
            } else {
                $arActiveSitesIDs = [];
                $rsSite = \CSite::GetList($by = "sort", $order = "desc", ['ACTIVE' => 'Y']);

                while ($site = $rsSite->Fetch()) {
                    $arActiveSitesIDs[] = $site['ID'];
                }

                $createMessage = $objCEventMessage->Add([
                    'ACTIVE' => 'Y',
                    'EVENT_NAME' => $this->moduleSettings['CEVENT_TYPE'],
                    'LID' => $arActiveSitesIDs,
                    'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                    'EMAIL_TO' => '#EMAIL_TO#',
                    'BCC' => '#BCC#',
                    'SUBJECT' => '#SUBJECT#',
                    'MESSAGE' => '#MESSAGE#',
                ]);
            }

            if ($createMessage) {
                $result = true;
            }

        }

        $appErrors = $APPLICATION->LAST_ERROR;

        if (!empty($appErrors)) {
            $this->errors[] = 'Создание почтового события: ' . SITE_ID . $appErrors->msg;
        }

        return $result;
    }

    public function removeSendEvent()
    {
        // удаление почтового события и шаблона
    }
}
