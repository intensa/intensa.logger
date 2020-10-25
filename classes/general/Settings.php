<?php

namespace Intensa\Logger;

/**
 * Class Settings
 * @method string LOG_DIR()
 * @method string OWN_LOG_DIR()
 * @method string LOG_FILE_EXTENSION()
 * @method string DATE_FORMAT()
 * @method boolean USE_DELIMITER()
 * @method boolean USE_BACKTRACE()
 * @method boolean DEV()
 * @method string CEVENT_TYPE()
 * @method string CEVENT_MESSAGE()
 * @method boolean CP1251()
 * @method string DEFAULT_EMAIL()
 * @package Intensa\Logger
 */
class Settings
{
    protected $settings = [];
    private static $instance = null;

    //public $test;

    private function __construct()
    {
        $settingsFilePath = __DIR__ . '/../../logger.config.php';
        $settingsFile = include_once(realpath($settingsFilePath));

        if (!$settingsFile && !is_array($settingsFile)) {
            //throw new \Exception('Проблемы с определением настроек системы');
        } else {
            $this->settings = $settingsFile;
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

    public function check()
    {
        return (!empty($this->settings));
    }

    public function __call($name, $arg = [])
    {
        return (array_key_exists($name, $this->settings)) ? $this->settings[$name] : null;
    }
}