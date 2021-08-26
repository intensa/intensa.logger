<?php


namespace Intensa\Logger;


class ILogSql
{
    protected $connection = null;
    protected $tracker = null;
    protected $queriesData = [];
    protected $trackerPull = [];

    public function __construct()
    {
        $this->connection = \Bitrix\Main\Application::getConnection();
    }

    public function start()
    {
        $this->tracker = $this->connection->startTracker(true);
    }

    public function stop()
    {
        $this->connection->stopTracker();
        foreach ($this->tracker->getQueries() as $query) {
            $this->queriesData[] = [
                'QUERY' => $query->getSql(),
                'TIME' => $query->getTime(),
            ];
        }
    }

    public function getData()
    {
        return $this->queriesData;
    }
}