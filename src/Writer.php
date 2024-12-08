<?php

namespace Intensa\Logger;

use Intensa\Logger\Tools\Helper;

/**
 * Class Writer
 * @package Intensa\Logger
 */
class Writer
{
    const DEFAULT_MEMORY_LIMIT = 10 * 1024 * 1024;

    const FILE_MODE_APPEND = 'a';

    const FILE_MODE_REWRITE = 'w';

    /**
     * @var string
     */
    protected string $filePath = '';

    /**
     * @var string
     */
    protected string $mode;

    /**
     * @var bool
     */
    protected bool $initFlag = false;

    /**
     * @var
     */
    protected $file;

    /**
     * @var array
     */
    protected array $storage = [];

    /**
     * @var int
     */
    protected int $memoryLimitValue = 0;

    /**
     * @var bool
     */
    protected bool $enableFlush = true;

    /**
     * Writer constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->filePath = $path;
        $this->mode = self::FILE_MODE_APPEND;
    }

    /**
     * Устанавливает режим w.
     * Необходим для корректной работы логгера в режиме перезаписи файла
     */
    public function setFileModeRewrite(): void
    {
        $this->mode = self::FILE_MODE_REWRITE;
    }

    public function disableFlush(): void
    {
        $this->enableFlush = false;
    }

    public function write(string $data): void
    {
        $this->storage[] = $data;

        if ($this->enableFlush) {
            $this->flush();
        }
    }

    protected function init(): void
    {
        $this->file = fopen($this->filePath, $this->mode);
        $this->initFlag = true;
    }

    protected function getMemoryLimit(): float|int
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

    protected function resetFileMode(): void
    {
        if ($this->mode === self::FILE_MODE_REWRITE) {
            $this->mode = self::FILE_MODE_APPEND;
        }
    }

    protected function flush(): void
    {
        if (memory_get_usage(true) > $this->getMemoryLimit()) {
            $this->writeToStream();
            $this->storage = [];
        }
    }

    protected function writeToStream(): void
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