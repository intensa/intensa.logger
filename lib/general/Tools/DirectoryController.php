<?php


namespace Intensa\Logger\Tools;


use Intensa\Logger\Settings;

class DirectoryController
{
    protected $logDirPath = false;
    protected $dirData = [
        'dirs' => [],
        'files' => [],
    ];
    protected $showAllFiles = false;


    public function __construct()
    {
        $this->logDirPath = Settings::getInstance()->LOG_DIR();
    }

    public function getRootLogDir()
    {
        return $this->logDirPath;
    }


    public function flagShowAllFiles()
    {
        $this->showAllFiles = true;
        return $this;
    }

    public function getDirectoryItems($path = false)
    {
        if (empty($path)) {
            $path = $this->logDirPath;
        }

        $return = [
            'directories' => [],
            'files' => [],
        ];

        $dirIterator = new \DirectoryIterator($path);

        foreach ($dirIterator as $item) {
            if (!$item->isDot()) {
                $typeItem = false;

                if ($item->isDir()) {
                    $typeItem = 'directories';
                }
                elseif ($item->isFile()) {
                    $typeItem = 'files';
                }

                if (
                    $typeItem === 'files'
                    && !$this->showAllFiles
                    && !$this->filterFileExtension($item->getBasename())) {
                    continue;
                }

                $return[$typeItem][$item->getBasename()] = [
                    'name' => $item->getBasename(),
                    'path' => $item->getPathname(),
                    'size' => $item->getSize(),
                    'mtime' => $item->getMTime(),
                    'mtime_format' => date(Settings::getInstance()->DATE_FORMAT(), $item->getMTime())
                ];
            }
        }

        return $return;
    }

    protected function filterFileExtension($fileName)
    {
        return (strpos($fileName, '.json.log') !== false);
    }
}