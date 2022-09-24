<?php

namespace Intensa\Logger;

/**
 * Class Timer
 * @package Intensa\Logger
 */
class Timer
{
    /**
     * @var string
     */
    protected string $timerCode = '';
    /**
     * @var int
     */
    protected int $timeStart = 0;
    /**
     * @var int
     */
    protected int $timeEnd = 0;
    /**
     * @var int
     */
    protected int $execTime = 0;
    /**
     * @var bool
     */
    protected bool $die = false;
    /**
     * @var string
     */
    protected string $startPoint = '';
    /**
     * @var string
     */
    protected string $endPoint = '';

    /**
     * @param string $code
     */
    public function __construct(string $code = 'nameless')
    {
        $code = trim($code);
        $this->timerCode = $code;
        $this->timeStart = microtime(true);
    }

    /**
     * @param string $startPoint
     * @return void
     */
    public function setStartPoint(string $startPoint)
    {
        $this->startPoint = $startPoint;
    }

    /**
     * @param string $endPoint
     * @return void
     */
    public function setEndPoint(string $endPoint)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * @return bool
     */
    public function isDie(): bool
    {
        return $this->die;
    }

    /**
     * @return $this
     */
    public function stop(): Timer
    {
        $this->timeEnd = microtime(true);
        $this->execTime = $this->timeEnd - $this->timeStart;
        $this->die = true;

        return $this;
    }

    /**
     * @return array
     */
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