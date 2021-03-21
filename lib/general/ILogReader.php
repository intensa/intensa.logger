<?php


namespace Intensa\Logger;


class ILogReader
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
        return $_SERVER['DOCUMENT_ROOT'] . $this->logDirPath;
    }

    public function getDirectoriesByDay()
    {
        return $this->getDirectoryItems($this->getRootLogDir());
    }

    public function flagShowAllFiles()
    {
        $this->showAllFiles = true;
        return $this;
    }

    public function getDirectoryItems($path)
    {
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
                    'path' => $item->getPathname()
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