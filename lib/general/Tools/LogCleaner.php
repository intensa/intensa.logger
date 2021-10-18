<?php


namespace Intensa\Logger\Tools;

use Intensa\Logger\Settings;

class LogCleaner
{
    protected $clearTime = 0;
    protected $rootLogDirectory = '';
    protected $allowExecute = false;

    public function __construct()
    {
        $settingTimeValue = Settings::getInstance()->CLEAR_LOGS_TIME();

        if ($settingTimeValue !== 'never') {
            if (
                $this->setClearTime($settingTimeValue)
                && $this->setRootLogDirectory(Settings::getInstance()->LOG_DIR())
            ) {
                $this->allowExecute = true;
            }
        }
    }

    public static function installAgent()
    {
        $now = new DateTime();

        \CAgent::AddAgent(
            self::clear(),
            Settings::getInstance()->getModuleId(),
            'N',
            86400,
            $now,
            'Y',
            $now,
            30
        );
    }

    public function allow(): bool
    {
        return $this->allowExecute;
    }

    public static function deleteAgent()
    {
        \CAgent::RemoveAgent(self::clear(), Settings::getInstance()->getModuleId());
    }

    public function getClearTime()
    {
        return $this->clearTime;
    }

    public function setClearTime($setValue): bool
    {
        $return = false;

        if (!empty($setValue)) {
            $time = strtotime($setValue);

            if ($time > 0) {
                $this->clearTime = $time;
                $return = true;
            }
        }

        return $return;
    }

    public function setRootLogDirectory($path): bool
    {
        $return = false;

        if (!empty($path)) {
            $this->rootLogDirectory = $path;
            $return = true;
        }

        return $return;
    }

    public function getOldLogsDirectories(): array
    {
        $return = [];

        try {
            $objDirectoryController = new DirectoryController();
            $rootDirectoryItems = $objDirectoryController->getRootDirectoryItems();

            foreach ($rootDirectoryItems['directories'] as $dateKey => $item) {
                if (
                    $this->isAllowDirectory($item['path'])
                    && $this->isLoggerDirectory($item['path'])
                    && $this->isOldDirectory($item['mtime'])
                ) {
                    $return[$dateKey] = $item['path'];
                }
            }
        } catch (\Exception $e) {

        }

        return $return;
    }

    public function isLoggerDirectory($path): bool
    {
        $arPath = explode('/', $path);
        $return = false;

        if (!empty($arPath) && is_array($arPath)) {
            $lastPathItem = end($arPath);

            if (preg_match("/\d{4}-\d{2}-\d{2}/m", $lastPathItem)) {
                $return = true;
            }
        }

        return $return;
    }

    protected function isAllowDirectory($path): bool
    {
        return strpos($path, $this->rootLogDirectory) !== false;
    }

    public function isOldDirectory($timeModify): bool
    {
        return (time() - $timeModify > time() - $this->clearTime);
    }

    public static function clear($run = false): string
    {
        if ($run) {
            $selfObj = new self();

            if ($selfObj->allow()) {
                $oldDirectories = $selfObj->getOldLogsDirectories();

                if (!empty($oldDirectories) && is_array($oldDirectories)) {
                    foreach ($oldDirectories as $item) {
                        $selfObj->deleteDirectory($item);
                    }
                }
            }
        }

        return __METHOD__ . '(true);';
    }

    protected function deleteDirectory($dirPath) : void
    {
        $dirIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($dirIterator as $path) {
            $pathName = $path->getPathname();
            ($path->isDir() && ($path->isLink() === false)) ? rmdir($pathName) : unlink($pathName);
        }

        rmdir($dirPath);
    }
}