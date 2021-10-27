<?php


namespace Intensa\Logger;


use Intensa\Logger\Tools\Helper;

class Writer
{
    const DEFAULT_MEMORY_LIMIT = 10 * 1024 * 1024;
    const FILE_MODE_APPEND = 'a';
    const FILE_MODE_REWRITE = 'w';

    protected $filePath = '';
    protected $mode;
    protected $initFlag = false;
    protected $file;
    protected $storage = [];
    protected $memoryLimitValue = 0;
    protected $enableFlush = true;


    public function __construct($path)
    {
        $this->filePath = $path;
        $this->mode = self::FILE_MODE_APPEND;
    }

    public function setFileModeRewrite()
    {
        $this->mode = self::FILE_MODE_REWRITE;
    }

    public function disableFlush()
    {
        $this->enableFlush = false;
    }

    public function write($data)
    {
        $this->storage[] = $data;

        if ($this->enableFlush) {
            $this->flush();
        }
    }

    protected function init()
    {
        $this->file = fopen($this->filePath, $this->mode);
        $this->initFlag = true;
    }

    protected function getMemoryLimit()
    {
        if (empty($this->memoryLimitValue))  {
            if ($memoryLimit = Helper::convertToBytes(ini_get('memory_limit'))) {
                $this->memoryLimitValue = $memoryLimit / 10;
            } else {
                $this->memoryLimitValue = self::DEFAULT_MEMORY_LIMIT;
            }
        }

        return $this->memoryLimitValue;
    }

    protected function resetFileMode()
    {
        if ($this->mode === self::FILE_MODE_REWRITE) {
            $this->mode = self::FILE_MODE_APPEND;
        }
    }

    protected function flush()
    {
        if (memory_get_usage(true) > $this->getMemoryLimit()) {
            $this->writeToStream();
            $this->storage = [];
        }
    }

    protected function writeToStream()
    {
        if (!$this->initFlag) {
            $this->init();
        }

        fwrite($this->file, implode($this->storage));
        fclose($this->file);
        $this->resetFileMode();
    }

    public function __destruct()
    {
        $this->writeToStream();
    }
}