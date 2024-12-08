<?php
declare(strict_types=1);

namespace Intensa\Logger;


class Timer
{
    protected string $timerCode = '';

    protected float|int $timeStart = 0;

    protected float|int $timeEnd = 0;

    protected float|int $execTime = 0;

    protected bool $die = false;

    protected string $startPoint = '';

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
    public function setStartPoint(string $startPoint): void
    {
        $this->startPoint = $startPoint;
    }

    /**
     * @param string $endPoint
     * @return void
     */
    public function setEndPoint(string $endPoint): void
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
            'START_TIME' => date('Y-m-d H:i:s.u', (int)$this->timeStart),
            'STOP_TIME' => date('Y-m-d H:i:s.u', (int)$this->timeEnd),
            'EXEC_TIME' => number_format($this->execTime, 9),
        ];

        if (!empty($this->startPoint) || !empty($this->endPoint)) {
            $data['START_POINT'] = $this->startPoint;
            $data['STOP_POINT'] = $this->endPoint;
        }

        return $data;
    }
}