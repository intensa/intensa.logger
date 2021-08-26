<?php

namespace Intensa\Logger;


class ILogTimer
{
    protected $timerCode = '';
    protected $timeStart = 0;
    protected $timeEnd = 0;
    protected $execTime = 0;
    protected $die = false;
    protected $startPoint = null;
    protected $endPoint = null;

    public function __construct($code = 'nameless')
    {
        $code = trim($code);
        $this->timerCode = $code;
        $this->timeStart = microtime(true);
    }

    public function setStartPoint($startPoint)
    {
        $this->startPoint = $startPoint;
    }

    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    public function isDie(): bool
    {
        return $this->die;
    }

    public function stop(): ILogTimer
    {
        $this->timeEnd = microtime(true);
        $this->execTime = $this->timeEnd - $this->timeStart;
        $this->die = true;

        return $this;
    }

    public function getTimerData(): array
    {
        $data = [
            'CODE' => $this->timerCode,
            'START_TIME' => date('Y-m-d H:i:s.u', $this->timeStart),
            'STOP_TIME' => date('Y-m-d H:i:s.u', $this->timeEnd),
            'EXEC_TIME' => number_format($this->execTime, 9),
        ];

        if (!empty($this->startPoint) || !empty($this->endPoint)) {
            $data['START_POINT'] = $this->startPoint;
            $data['STOP_POINT'] = $this->endPoint;
        }

        return $data;
    }


}