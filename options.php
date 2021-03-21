<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'intensa.logger');

global $USER;
global $APPLICATION;

Cmodule::IncludeModule('intensa.logger');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
function ShowParamsHTMLByarray($arParams)
{
    foreach ($arParams as $Option) {
        if (is_array($Option)) {
            $Option[0] = 'LOGGER_' . $Option[0];
        }
        __AdmSettingsDrawRow(ADMIN_MODULE_NAME, $Option);
    }
}
if (isset($_REQUEST['save']) && check_bitrix_sessid()) {

    foreach ($_POST as $key => $option) {
        if (strpos($key, 'LOGGER_') !== false) {
            if (is_array($option)) {
                $option = implode(',', $option);
            }

            if ($key === 'LOGGER_LOG_DIR') {
                if (substr($option, -1) !== '/') {
                    $option = $option . '/';
                }
            }

            COption::SetOptionString(ADMIN_MODULE_NAME, str_replace('LOGGER_', '', $key), $option);
        }
    }
}

IncludeModuleLangFile($_SERVER[ 'DOCUMENT_ROOT' ] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);
include("install/version.php");

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit1',
        'TAB'   => getMessage('MAIN_TAB_SET'),
        'TITLE' => getMessage('MAIN_TAB_TITLE_SET'),
    ]
]);
$arAllOptions = [
    [
        'LOG_DIR',
        getMessage('LOG_DIR'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'LOG_DIR', '/logs/'),
        ['text']
    ],
    [
        'LOG_FILE_EXTENSION',
        getMessage('LOG_FILE_EXTENSION'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'LOG_FILE_EXTENSION', '.log'),
        ['text']
    ],
    [
        'DATE_FORMAT',
        getMessage('DATE_FORMAT'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'DATE_FORMAT', 'Y-m-d H:i:s'),
        ['text']
    ],
    [
        'USE_BACKTRACE',
        getMessage('USE_BACKTRACE'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'USE_BACKTRACE', 'Y'),
        ['checkbox']
    ],
    [
        'DEV_MODE',
        getMessage('DEV_MODE'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'DEV_MODE', 'Y'),
        ['checkbox']
    ],
    [
        'CEVENT_TYPE',
        getMessage('CEVENT_TYPE'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CEVENT_TYPE', 'INTENSA_LOGGER_ALERT'),
        ['text']
    ],
    [
        'CEVENT_MESSAGE',
        getMessage('CEVENT_TYPE'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'CEVENT_MESSAGE', 'INTENSA_LOGGER_FATAL_TEMPLATE'),
        ['text']
    ],
    [
        'USE_CP1251',
        getMessage('USE_CP1251'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'USE_CP1251', 'N'),
        ['checkbox']
    ],
    [
        'ALERT_EMAIL',
        getMessage('ALERT_EMAIL'),
        COption::GetOptionString(ADMIN_MODULE_NAME, 'ALERT_EMAIL', COption::GetOptionString("main", "email_from")),
        ['text']
    ],
];
?>

<form name='intensa-logger-options' method='POST' action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid)
?>&amp;lang=<? echo LANG ?>'>
    <?= bitrix_sessid_post() ?>
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();

    ShowParamsHTMLByArray($arAllOptions);

    $tabControl->EndTab();

    $tabControl->Buttons(); ?>
    <input type='submit' class='adm-btn-save' name='save' value='<?=getMessage('SAVE')?>'>
    <?= bitrix_sessid_post(); ?>
    <? $tabControl->End(); ?>

    <? $tabControl->End(); ?>
</form>
