<?php
namespace Intensa\Logger\Tools;

use Intensa\Logger\Settings;

class LogCleaner
{
    protected $clearTime = 0;
    protected $rootLogDirectories = '';

    public function __construct()
    {
        $settingTimeValue = Settings::getInstance()->CLEAR_LOGS_TIME();

        if ($settingTimeValue !== 'never') {
            $this->clearTime = $this->prepareSettingsClearTime($settingTimeValue);
            $this->rootLogDirectories = Settings::getInstance()->LOG_DIR();
        }
    }

    public function installAgent()
    {
        // @todo тут метод установки агнета.
        // я думаю стоит сделать проверку, если агент не зареган регаем его , если зареган активируем
        // в настройках стоит сделать фичу, которая будет активировать или диактивировать агнет в зависимости от сохраняемого значения
    }

    public function getClearTime()
    {
        return $this->clearTime;
    }

    protected function prepareSettingsClearTime($time)
    {
        return strtotime($time);
    }

    public function getOldLogsDirectories(): array
    {
        $return = [];

        $objDirectoryController = new DirectoryController();
        $rootDirectoryItems = $objDirectoryController->getDirectoryItems();

        foreach ($rootDirectoryItems['directories'] as $dateKey => $item) {
            // todo сюда я бы предложил добавить проверку верности названия папки при помощи preg+match
            if (
                isset($item['mtime'])
                && $this->isOldDirectory($item['mtime'])
                && $this->isAllowDirectory($item['path'])
            ) {
                $return[$dateKey] = $item['path'];
            }
        }

        return $return;
    }

    protected function isAllowDirectory($path): bool
    {
        return strpos($path, $this->rootLogDirectories) !== false;
    }

    public function isOldDirectory($timeModify): bool
    {
        return (time() - $timeModify > time() - $this->clearTime);
    }

    public static function clear(): string
    {
        $selfObj = new self();

        if ($selfObj->getClearTime() > 0) {
            $oldDirectories = $selfObj->getOldLogsDirectories();

            if (!empty($oldDirectories) && is_array($oldDirectories)) {
                foreach ($oldDirectories as $item) {
                    // @todo: это не работает нормально, нужно отлаживать
                    $res = unlink($item);
                }
            }

            return __METHOD__ . '();';
        }

    }
}