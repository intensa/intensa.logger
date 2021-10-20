<?php


namespace Intensa\Logger;


class Writer
{
    protected $filePath = '';
    protected $mode = 'a';
    protected $initFlag = false;
    protected $file;
    protected $storage = [];

    public function __construct($path, $mode = 'a')
    {
        //$openFile = fopen($this->writeFilePath, ($this->rewriteLogFile && $this->execLogCount === 0) ? 'w' : 'a');
        //fwrite($openFile, $strLogData);
        //fclose($openFile);

        $this->filePath = $path;
        $this->mode = $mode;
    }

    public function init()
    {
        $this->file = fopen($this->filePath, $this->mode);
        $this->initFlag = true;
    }

    public function write($data)
    {
        $this->storage[] = $data;
        //$this->flushStorage();
    }

    public function flushStorage()
    {
        if (count($this->storage) > 10000) {
            $this->finish();
            $this->storage = [];
        }
    }

    public function finish()
    {
        if (!$this->initFlag) {
            $this->init();
        }

        fwrite($this->file, implode($this->storage));
    }

    public function __destruct()
    {
        $this->finish();
        fclose($this->file);
    }
}