<?php
namespace Intensa\Logger\Tools;

use Intensa\Logger\Settings;

class LogCleaner
{
    protected $clearTime = 0;
    protected $rootLogDirectories = '';

    public function __construct()
    {
        $settingTimeValue = '-1 week';
        $this->clearTime = $this->prepareSettingsClearTime($settingTimeValue);
        $this->rootLogDirectories = Settings::getInstance()->LOG_DIR();
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
        $oldDirectories = $selfObj->getOldLogsDirectories();

        if (!empty($oldDirectories) && is_array($oldDirectories)) {
            foreach ($oldDirectories as $item) {
                $res = unlink($item);
            }
        }

        return __METHOD__ . '();';
    }
}