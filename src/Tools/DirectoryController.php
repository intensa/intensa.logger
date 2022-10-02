<?php

namespace Intensa\Logger\Tools;

use Intensa\Logger\Settings;

class DirectoryController
{
    protected string $logDirPath = '';
    protected bool $showAllFiles = false;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (Settings::getInstance()->LOG_DIR()) {
            $this->logDirPath = Settings::getInstance()->LOG_DIR();
        } else {
            throw new \Exception('Фатальная ошибка. Не задан путь к корневой папке логов.');
        }

    }

    public function getRootDirectoryItems(): array
    {
        return $this->getDirectoryItems($this->logDirPath);
    }

    public function getDirectoryItems($path): array
    {
        $return = [
            'directories' => [],
            'files' => [],
        ];

        $dirIterator = new \DirectoryIterator($path);

        foreach ($dirIterator as $item) {
            if (!$item->isDot()) {
                $typeItem = ($item->isDir()) ? 'directories' : 'files';

                $return[$typeItem][$item->getBasename()] = [
                    'name' => $item->getBasename(),
                    'path' => $item->getPathname(),
                    'size' => $item->getSize(),
                    'mtime' => $item->getMTime(),
                    'mtime_format' => date(Settings::getInstance()->DATE_FORMAT(), $item->getMTime()),
                ];
            }
        }

        return $return;
    }

    public function flagShowAllFiles(): DirectoryController
    {
        $this->showAllFiles = true;
        return $this;
    }

    protected function filterFileExtension($fileName): bool
    {
        return (strpos($fileName, '.json.log') !== false);
    }
}