<?php


namespace Intensa\Logger;

/**
 * Class Settings
 * @method string LOG_DIR()
 * @method string LOG_FILE_EXTENSION()
 * @method string DATE_FORMAT()
 * @method string USE_BACKTRACE()
 * @method string DEV_MODE()
 * @method string CEVENT_TYPE()
 * @method string CEVENT_MESSAGE()
 * @method string USE_CP1251()
 * @method string ALERT_EMAIL()
 * @method string WRITE_JSON()
 * @method string LOG_FILE_PERMISSION()
 * @method string CLEAR_LOGS_TIME()
 * @package Intensa\Logger
 */
class Settings
{
    protected $settings = [];
    private static $instance = null;
    private $optionsList = [
        'LOG_DIR' => '/logs/',
        'LOG_FILE_EXTENSION' => '.log',
        'LOG_FILE_PERMISSION' => '0775',
        'DATE_FORMAT' => 'Y-m-d H:i:s',
        'USE_BACKTRACE' => 'Y',
        'DEV_MODE' => 'Y',
        'CEVENT_TYPE' => 'INTENSA_LOGGER_ALERT',
        'CEVENT_MESSAGE' => 'INTENSA_LOGGER_FATAL_TEMPLATE',
        'USE_CP1251' => 'N',
        'ALERT_EMAIL' => '',
        'WRITE_JSON' => 'N',
        'CLEAR_LOGS_TIME' => 'never'
    ];

    private function __construct()
    {
        foreach ($this->optionsList as $optionCode => $defaultValue) {
            $value = \COption::GetOptionString('intensa.logger', $optionCode, $defaultValue);
            $this->settings[$optionCode] = $value;
        }
    }

    static function getInstance(): Settings
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function installOptions()
    {
        foreach ($this->optionsList as $optionCode => $value) {

            if ($optionCode === 'LOG_DIR') {
                $optionValue = $_SERVER['DOCUMENT_ROOT'] . $value;
            }

            \COption::SetOptionString($this->getModuleId(), $optionCode, $value);
        }
    }

    public function getDefaultOptionValue($optionCode) : string
    {
        $return = '';

        if (array_key_exists($optionCode, $this->optionsList)) {
            $optionValue = $this->optionsList[$optionCode];

            if ($optionCode === 'LOG_DIR') {
                $optionValue = $_SERVER['DOCUMENT_ROOT'] . $optionValue;
            }

            $return = $optionValue;
        }

        return $return;
    }

    public function checkDirSecurity($logDir) : bool
    {
        $htaccessPath = $logDir . '.htaccess';
        $result = false;

        if (strpos($logDir, $_SERVER['DOCUMENT_ROOT'] . '/') === false) {
            $result = true;
        } else {
            if (file_exists($htaccessPath)) {
                $getHtaccessContent = file_get_contents($htaccessPath);
                $htRules = explode(PHP_EOL, $getHtaccessContent);
                $firstHtaccessLine = trim(current($htRules));

                if (
                    !empty($htRules)
                    && is_array($htRules)
                    && $firstHtaccessLine === 'Deny from all'
                ) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function checkDirAvailability($logDir) : bool
    {
        $result = false;

        if (file_exists($logDir) && is_writable($logDir)) {
            $result = true;
        }

        return $result;
    }

    public function getModuleId()
    {
        return 'intensa.logger';
    }

    public function __call($name, $arg = [])
    {
        return (array_key_exists($name, $this->settings)) ? $this->settings[$name] : null;
    }

    protected function __clone()
    {
    }
}