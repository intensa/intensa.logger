<?php

namespace Intensa\Logger;


class ILogAlert
{
    protected $eventName = '';
    protected $objILog = null;
    protected $message = '';
    protected $subject = '';
    protected $settings = null;

    public function __construct(ILog $obj)
    {
        $this->settings = Settings::getInstance();
        $this->objILog = $obj;
        $this->buildMessage();
        $this->buildSubject();
    }

    protected function buildMessage()
    {
        $arMessage = [];
        $execFilePath = $this->objILog->getExecPathFile();

        if ($execFilePath) {
            $arMessage[] = 'Файл вызова: <b>' . $execFilePath . '</b>';
        }

        $arMessage[] = 'Код лога: <b>' . $this->objILog->getLoggerCode() . '</b>'; // код лога
        $arMessage[] = 'Пусть к файлу лога: <b>' . $this->objILog->getWritePathFile() . '</b>'; // путь к файлу лога
        $arMessage[] = 'Данные: <br/><i>' . implode('<br>',
                $this->objILog->getLogDataItems()) . '</i>'; // сообщений лога

        $this->message = implode('<br>', $arMessage);
    }

    protected function buildSubject()
    {
        // @todo: тут нужно вынести тему для сообщения в настройки. делаем после того как пернеесем setting storage
        $this->subject = 'Intensa.logger:' . $_SERVER['SERVER_NAME'] . ':' . $this->objILog->getLoggerCode();
    }

    public function setAdditionalEmail($emails)
    {
        if (!empty($emails)) {
            $this->additionalEmail = $emails;
        }
    }

    protected function getEmails()
    {
        $emails = $this->settings->DEFAULT_EMAIL();

        // добавим дополнительные адреса
        if (!empty($this->additionalEmail) && is_array($this->additionalEmail)) {
            $strEmails = implode(',', $this->additionalEmail);
            if (!empty($strEmails)) {
                $emails .= ',' . $strEmails;
            }
        }

        return $emails;
    }

    public function send()
    {
        if (!empty($this->settings->DEFAULT_EMAIL())) {
            $arEventFields = [
                'EMAIL_TO' => $this->getEmails(),
                'SUBJECT' => $this->subject,
                'MESSAGE' => $this->message,
            ];

            \CEvent::SendImmediate(
                $this->settings->CEVENT_TYPE(),
                SITE_ID, $arEventFields,
                'N',
                $this->settings->CEVENT_MESSAGE()
            );
        }
    }

    public static function alert()
    {

    }
}