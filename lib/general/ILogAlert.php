<?php


namespace Intensa\Logger;


class ILogAlert
{
    protected $objILog = null;
    protected $settings = null;
    protected $additionalEmail = [];

    public function __construct(ILog $obj)
    {
        $this->settings = Settings::getInstance();
        $this->objILog = $obj;
    }

    public function setAdditionalEmails($emails)
    {
        if (!empty($emails)) {
            $this->additionalEmail = $emails;
        }
    }

    public function getAdditionalEmail(): array
    {
        return $this->additionalEmail;
    }

    protected function getEmails(): string
    {
        $emails = $this->settings->ALERT_EMAIL();
        // добавим дополнительные адреса
        $additionalEmail = $this->getAdditionalEmail();

        if (!empty($additionalEmail) && is_array($additionalEmail)) {
            $strEmails = implode(',', $additionalEmail);
            if (!empty($strEmails)) {
                $emails .= ',' . $strEmails;
            }
        }

        return $emails;
    }

    public function send($message)
    {
        $emails = $this->getEmails();

        if (!empty($emails)) {
            $arEventFields = [
                'EMAIL_TO' => $emails,
                'LOGGER_CODE' => $this->objILog->getLoggerCode(),
                'LOGGER_PATH' => $this->objILog->getWritePathFile(),
                'LOGGER_MESSAGE' => $message,
            ];

            $execFilePath = $this->objILog->getExecPathFile();

            if ($execFilePath) {
                $arEventFields['LOGGER_EXEC_PATH'] = $execFilePath;
            }

            \CEvent::SendImmediate(
                $this->settings->CEVENT_TYPE(),
                SITE_ID, $arEventFields,
                'N',
                $this->settings->CEVENT_MESSAGE()
            );
        }
    }
}