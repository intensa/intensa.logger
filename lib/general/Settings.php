<?php

namespace Intensa\Logger;

/**
 * Class Settings
 * @method string LOG_DIR()
 * @method string LOG_FILE_EXTENSION()
 * @method string DATE_FORMAT()
 * @method boolean USE_BACKTRACE()
 * @method boolean DEV_MODE()
 * @method string CEVENT_TYPE()
 * @method string CEVENT_MESSAGE()
 * @method boolean USE_CP1251()
 * @method string ALERT_EMAIL()
 * @package Intensa\Logger
 */
class Settings
{
    protected $settings = [];
    private static $instance = null;
    private $optionsList = [
        'LOG_DIR',
        'LOG_FILE_EXTENSION',
        'DATE_FORMAT',
        'USE_BACKTRACE',
        'DEV_MODE',
        'CEVENT_TYPE',
        'CEVENT_MESSAGE',
        'USE_CP1251',
        'ALERT_EMAIL',
    ];

    private function __construct()
    {
        foreach ($this->optionsList as $optionCode) {
            $value = \COption::GetOptionString('intensa.logger', $optionCode, '');
            $this->settings[$optionCode] = $value;
        }
    }

    protected function __clone()
    {
    }

    static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function __call($name, $arg = [])
    {
        return (array_key_exists($name, $this->settings)) ? $this->settings[$name] : null;
    }
}