<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('LOGGER_MODULE_NAME') or define('LOGGER_MODULE_NAME', 'intensa.logger');

global $USER;
global $APPLICATION;

CModule::IncludeModule('intensa.logger');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
function ShowParamsHTMLByarray($arParams)
{
    foreach ($arParams as $Option) {
        if (is_array($Option)) {
            $Option[0] = 'LOGGER_' . $Option[0];
        }
        __AdmSettingsDrawRow(LOGGER_MODULE_NAME, $Option);
    }
}
$mayEmptyProps = [
    'LOGGER_USE_BACKTRACE',
    'LOGGER_DEV_MODE',
    'LOGGER_USE_CP1251',
    'LOGGER_WRITE_JSON',
];

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

            COption::SetOptionString(LOGGER_MODULE_NAME, str_replace('LOGGER_', '', $key), $option);
        }
    }

    foreach ($mayEmptyProps as $mayEmptyProp) {
        if (!isset($_POST[$mayEmptyProp])) {
            COption::SetOptionString(LOGGER_MODULE_NAME, str_replace('LOGGER_', '', $mayEmptyProp), '');
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

$logDirOptionValue = COption::GetOptionString(
    LOGGER_MODULE_NAME,
    'LOG_DIR',
    \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('LOG_DIR')
);

$logDirLabel = getMessage('LOG_DIR');

if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {

    if (\Intensa\Logger\Tools\Settings::getInstance()->checkDirSecurity($logDirOptionValue)) {
        $logDirLabel .= '<br>' . getMessage('SECURITY_LOG_DIR_TRUE');
    } else {
        $logDirLabel .= '<br>' . getMessage('SECURITY_LOG_DIR_FALSE');
    }
}

if (\Intensa\Logger\Tools\Settings::getInstance()->checkDirAvailability($logDirOptionValue)) {
    $logDirLabel .= '<br>' . getMessage('AVAIL_LOG_DIR_TRUE');
} else {
    $logDirLabel .= '<br>' . getMessage('AVAIL_LOG_DIR_FALSE');
}

$arAllOptions = [
    [
        'LOG_DIR',
        $logDirLabel,
        $logDirOptionValue,
        ['text']
    ],
    [
        'LOG_FILE_EXTENSION',
        getMessage('LOG_FILE_EXTENSION'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'LOG_FILE_EXTENSION',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('LOG_FILE_EXTENSION')
        ),
        ['text']
    ],
    [
        'LOG_FILE_PERMISSION',
        getMessage('LOG_FILE_PERMISSION'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'LOG_FILE_PERMISSION',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('LOG_FILE_PERMISSION')
        ),
        [
            'selectbox',
            [
                '0644' => '0644',
                '0755' => '0755',
                '0775' => '0775',
                '0777' => '0777',
            ]
        ]
    ],
    [
        'DATE_FORMAT',
        getMessage('DATE_FORMAT'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'DATE_FORMAT',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('DATE_FORMAT')
        ),
        ['text']
    ],
    [
        'USE_BACKTRACE',
        getMessage('USE_BACKTRACE'),
        COption::GetOptionString(
                LOGGER_MODULE_NAME,
                'USE_BACKTRACE',
                \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('USE_BACKTRACE')
        ),
        ['checkbox']
    ],
    [
        'DEV_MODE',
        getMessage('DEV_MODE'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'DEV_MODE',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('DEV_MODE')
        ),
        ['checkbox']
    ],
    [
        'USE_CP1251',
        getMessage('USE_CP1251'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'USE_CP1251',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('USE_CP1251')
        ),
        ['checkbox']
    ],
    [
        'WRITE_JSON',
        getMessage('WRITE_JSON'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'WRITE_JSON',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('WRITE_JSON')
        ),
        ['checkbox']
    ],
    [
        'CLEAR_LOGS_TIME',
        getMessage('CLEAR_LOGS_TIME'),
        COption::GetOptionString(
            LOGGER_MODULE_NAME,
            'CLEAR_LOGS_TIME',
            \Intensa\Logger\Tools\Settings::getInstance()->getDefaultOptionValue('CLEAR_LOGS_TIME')
        ),
        [
            'selectbox',
            [
                'never' => getMessage('never'),
                '-1 week' => getMessage('-1 week'),
                '-2 week' => getMessage('-2 week'),
                '-1 month' => getMessage('-1 month'),
                '-2 month' => getMessage('-2 month'),
                '-3 month' => getMessage('-3 month'),
                '-6 month' => getMessage('-6 month')
            ]
        ]
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
