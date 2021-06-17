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
    const DEFAULT_EVENT_TYPE = 'INTENSA_LOGGER_ALERT';
    const DEFAULT_EVENT_MESSAGE = 'INTENSA_LOGGER_FATAL_TEMPLATE';
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
        $this->MODULE_VERSION = '0.1.0';
        $this->MODULE_VERSION_DATE = '2021-03-21 10:10:10';
        $this->MODULE_NAME = 'IntensaLogger';
        $this->MODULE_DESCRIPTION = 'Модуль для логирования данных в проекте';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'Intensa';
        $this->PARTNER_URI = 'https://intensa.ru';
    }

    public function doInstall()
    {
        global $APPLICATION;
        $createDir = $this->createDirectory();
        $this->checkPermission($createDir);
        $this->createAccessFile($createDir);
        $this->createSendEvent();


        if (!empty($this->errors)) {
            $APPLICATION->ThrowException(implode('<br>', $this->errors));
            return false;
        } else {
            ModuleManager::registerModule($this->MODULE_ID);
        }
    }

    public function doUninstall()
    {
        $this->removeSendEvent();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function createDirectory()
    {
        $dirPath = $_SERVER['DOCUMENT_ROOT'] . '/logs/';

        if (!file_exists($dirPath)) {
            $mkdir = mkdir($dirPath, 0777);

            if (!$mkdir) {
                $this->errors[] = 'Ошибка создания основной директории для логов ' . $dirPath;
                return false;
            }
        }

        chmod($dirPath, 0777);

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

    public function createSendEvent() : bool
    {
        global $APPLICATION;

        $defaultEventType = self::DEFAULT_EVENT_TYPE;
        $defaultEventName = self::DEFAULT_EVENT_MESSAGE;

        $result = false;

        $arActiveSitesIDs = [];
        $rsSite = \CSite::GetList($by = "sort", $order = "desc", ['ACTIVE' => 'Y']);

        while ($site = $rsSite->Fetch()) {
            $arActiveSitesIDs[] = $site['ID'];
        }

        $objCEventType = new \CEventType;

        $filterCEventType = ['TYPE_ID' => $defaultEventType];
        $objResultCEventType = $objCEventType->GetList($filterCEventType);



        if ($eventType = $objResultCEventType->Fetch()) {
            $createType = $eventType['ID'];
        } else {
            $createType = $objCEventType->Add([
                'LID' => $arActiveSitesIDs,
                'EVENT_NAME' => $defaultEventType,
                'NAME' => 'intensa.logger',
            ]);
        }

        if ($createType) {
            $objCEventMessage = new \CEventMessage;
            $objResultCEventMessage = $objCEventMessage->GetList($by = 'id', $order = 'desc',
                ['TYPE_ID' => $defaultEventType]);

            if ($eventMessage = $objResultCEventMessage->Fetch()) {
                $createMessage = true;
            } else {

                $createMessage = $objCEventMessage->Add([
                    'ACTIVE' => 'Y',
                    'EVENT_NAME' => $defaultEventType,
                    'LID' => $arActiveSitesIDs,
                    'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                    'EMAIL_TO' => '#EMAIL_TO#',
                    'BCC' => '#BCC#',
                    'SUBJECT' => GetMessage('CEVENT_SUBJECT'),
                    'MESSAGE' => GetMessage('CEVENT_MESSAGE'),
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
        $eventType = self::DEFAULT_EVENT_TYPE;

        if (!empty($eventType)) {
            $objCEventMessage = new \CEventMessage;
            $objResultCEventMessage = $objCEventMessage->GetList($by = 'id', $order = 'desc',
                ['TYPE_ID' => $eventType]);

            while ($eventMessage = $objResultCEventMessage->Fetch()) {
                $objCEventMessage->Delete($eventMessage['ID']);
            }

            $objCEventType = new CEventType;
            $objCEventType->Delete($eventType);
        }
    }
}
