<?php


namespace Intensa\Logger;


class ILogReader
{
    protected $logDirPath = false;
    protected $dirData = [
        'dirs' => [],
        'files' => [],
    ];

    public function __construct()
    {
        $this->logDirPath = Settings::getInstance()->LOG_DIR();
    }

    public function getRootLogDir()
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->logDirPath;
    }

    public function getDirectories($path = false)
    {
        $return = [
            'dirs' => [],
            'files' => [],
        ];

        $dirIterator = new \DirectoryIterator($this->getRootLogDir());
        foreach ($dirIterator as $item) {
            if (!$item->isDot()) {
                if ($item->isDir()) {
                    $return['dirs'][$item->getBasename()] = [
                        'name' => $item->getBasename(),
                        'path' => $item->getPathname()
                    ];
                }
            }
        }

        var_dump($return);

    }
}