<?php


namespace Intensa\Logger;


class ILogSql
{
    protected $connection = null;
    protected $trackersData = [];
    protected $trackerPull = [];

    public function __construct()
    {
        $this->connection = \Bitrix\Main\Application::getConnection();
    }

    public function start($code)
    {
        if (!empty($this->trackerPull)) {

            $this->connection->stopTracker();
            $notFinishTrackers = $this->getNotFinishTrackersCodes();

            foreach ($this->trackerPull as $trackerItem) {
                if (!$trackerItem['finish']) {
                    $getTrackerData = $this->getTrackerQuery($trackerItem['trackerObject'], $notFinishTrackers);
                    $this->trackersData = array_merge($this->trackersData, $getTrackerData);
                }
            }
        }

        $tracker = $this->connection->startTracker(true);
        $this->setTracker($code, $tracker);
    }

    protected function setTracker($code, $tracker)
    {
        $this->trackerPull[$code] = [
            'code' => $code,
            'trackerObject' => $tracker,
            'finish' => false,
            'data' => []
        ];
    }

    protected function getTrackerQuery($tracker, $code): array
    {
        $result = [];

        foreach ($tracker->getQueries() as $query) {
            $result[] = [
                'query' => $query->getSql(),
                'time' => $query->getTime(),
                'code' => $code
            ];
        }

        return $result;
    }

    public function getTracker($code)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            return $this->trackerPull[$code];
        }
    }

    public function getTrackersData()
    {
        return $this->trackersData;
    }

    protected function finishTracker($code)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $this->trackerPull[$code]['finish'] = true;
        }
    }

    protected function setTrackerData($code, $data)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $this->trackerPull[$code]['data'] = $data;
        }
    }

    protected function getNotFinishTrackersCodes()
    {
        $return = [];

        foreach ($this->trackerPull as $item) {
            if (!$item['finish']) {
                $return[$item['code']] = $item['code'];
            }
        }

        return $return;
    }

    public function stop($code): array
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $trackerItem = $this->trackerPull[$code];
            $this->connection->stopTracker();

            $notFinishTrackers = $this->getNotFinishTrackersCodes();
            $notFinishTrackers[$code] = $code;

            $getCurrentTrackerData = $this->getTrackerQuery($trackerItem['trackerObject'], $notFinishTrackers);
            $this->trackersData = array_merge($this->trackersData, $getCurrentTrackerData);

            $this->finishTracker($code);
            $this->setTrackerData($code, $this->trackersData);

            foreach ($this->trackerPull as &$item) {
                if (!$item['finish']) {
                    $item['trackerObject'] = $this->connection->startTracker(true);
                }
            }

            return $this->getPrepareResultDataForSelectTracker($code, $this->trackersData);
        }
    }

    protected function getPrepareResultDataForSelectTracker($trackerCode, $data)
    {
        $result = [];

        foreach ($data as $trackersDataItem) {
            if (array_key_exists($trackerCode, $trackersDataItem['code'])) {
                unset($trackersDataItem['code']);
                $result[] = $trackersDataItem;
            }
        }

        return $result;
    }

    public function stopAll(): array
    {
        $return = [];

        foreach ($this->trackerPull as $tracker) {
            if (!$tracker['finish']) {
                $return[$tracker['code']] = $this->stop($tracker['code']);
            }
        }

        return $return;
    }
}