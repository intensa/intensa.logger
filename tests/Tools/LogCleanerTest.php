<?php

namespace Intensa\Logger\Tools;

use PHPUnit\Framework\TestCase;

class LogCleanerTest extends TestCase
{
    private $obj = null;

    protected function setUp(): void
    {
        $this->obj = new LogCleaner();
    }

    public function testIsLoggerDirectory()
    {
        $incorrectPath = [
            '/var/www/project/logs/2021-11-22/',
            '/var/www/project/2021-11-22/',
            '/var/www/',
        ];

        foreach ($incorrectPath as $item) {
            $this->assertFalse($this->obj->isLoggerDirectory($item));
        }

        $correctValue = '/var/www/project/logs/2021-11-22';
        $this->assertTrue($this->obj->isLoggerDirectory($correctValue));
    }

    public function testIsOldDirectory()
    {
        $this->obj->setClearTime('-1 week');
        $this->assertTrue($this->obj->isOldDirectory(strtotime('-8 day')));
        $this->assertFalse($this->obj->isOldDirectory(strtotime('-5 day')));
    }

    public function testSetClearTime()
    {
        $incorrectValue = [
            '',
            'asd',
            false,
            null,
            0,
            '-',
            []
        ];

        foreach ($incorrectValue as $item) {
            $this->assertFalse($this->obj->setClearTime($item));
        }

        $this->assertTrue($this->obj->setClearTime('-1 week'));
    }

    public function testSetRootLogDirectory()
    {
        $this->assertTrue($this->obj->setRootLogDirectory('/var/www/logs/'));
        $this->assertFalse($this->obj->setRootLogDirectory(''));
        $this->assertFalse($this->obj->setRootLogDirectory(false));
    }

}
