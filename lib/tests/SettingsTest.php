<?php
namespace Intensa\Logger;

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    protected $settingList = [
        'LOG_DIR',
        'LOG_FILE_EXTENSION',
        'LOG_FILE_PERMISSION',
        'DATE_FORMAT',
        'USE_BACKTRACE',
        'DEV_MODE',
        'CEVENT_TYPE',
        'CEVENT_MESSAGE',
        'USE_CP1251',
        'ALERT_EMAIL',
        'WRITE_JSON',
        'CLEAR_LOGS_TIME',
    ];

    public function testNotEmptyLogDir()
    {
        $this->assertNotEmpty(Settings::getInstance()->LOG_DIR());
    }

    public function testMethodExist()
    {
        foreach ($this->settingList as $item) {
            $this->assertNotNull(Settings::getInstance()->{$item}());
        }
    }

    public function testGetModuleId()
    {
        $this->assertSame('intensa.logger', Settings::getInstance()->getModuleId());
    }
}