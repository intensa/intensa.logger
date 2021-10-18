<?php


namespace Intensa\Logger;


use PHPUnit\Framework\TestCase;

class ILogAlertTest extends TestCase
{
    protected $obj = null;

    protected function setUp(): void
    {
        $this->obj = new ILogAlert(new ILog());
    }

    public function testSetAdditionalEmail()
    {
        $additionalEmails = ['test@test.ru'];
        $this->obj->setAdditionalEmails($additionalEmails);

        $this->assertIsArray($this->obj->getAdditionalEmail());
        $this->assertNotEmpty($this->obj->getAdditionalEmail());
    }
}