<?php


namespace Intensa\Logger;


/**
 * Class ILogSql
 * @package Intensa\Logger
 */
class SqlTracker
{
    protected $connection = null;
    protected $trackersData = [];
    protected $trackerPull = [];

    public function __construct()
    {
        $this->connection = \Bitrix\Main\Application::getConnection();
    }

    /**
     * Запускает отслеживание запросов для переданного кода.
     * @param $code
     */
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

    /**
     * Добавляет трекер в пулл трекеров
     * @param $code
     * @param $tracker
     */
    protected function setTracker($code, $tracker)
    {
        $this->trackerPull[$code] = [
            'code' => $code,
            'trackerObject' => $tracker,
            'finish' => false,
            'data' => []
        ];
    }

    /**
     * Получение запросов трекера
     * @param $tracker
     * @param $code
     * @return array
     */
    protected function getTrackerQuery($tracker, $code): array
    {
        $result = [];

        foreach ($tracker->getQueries() as $query) {
            $resultData = [
                'query' => $query->getSql(),
                'time' => $query->getTime(),
                'code' => $code
            ];

            $result[md5(serialize($resultData))] = $resultData;
        }

        return $result;
    }

    /**
     * Получение трекера по коду
     * @param $code
     * @return mixed
     */
    public function getTracker($code)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            return $this->trackerPull[$code];
        }
    }

    /**
     * Устанавливает флаг завершенности трекера
     * @param $code
     */
    protected function finishTracker($code)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $this->trackerPull[$code]['finish'] = true;
        }
    }

    /**
     * Устанавливает данные запросов для конкретного трекера
     * @param $code
     * @param $data
     */
    protected function setTrackerData($code, $data)
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $this->trackerPull[$code]['data'] = $data;
        }
    }

    /**
     * Возвращает массив кодов всех не завершенных трекеров
     * @return array
     */
    protected function getNotFinishTrackersCodes(): array
    {
        $return = [];

        foreach ($this->trackerPull as $item) {
            if (!$item['finish']) {
                $return[$item['code']] = $item['code'];
            }
        }

        return $return;
    }

    /**
     * Останавливает трекер с переданым в качестве аргумента кодом.
     * Возвращает массив запросов и время их выполнения
     * @param $code
     * @return array
     */
    public function stop($code): array
    {
        $return = [];

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

            $return = $this->getPrepareResultDataForSelectTracker($code, $this->trackersData);
        }

        return $return;
    }

    /**
     * Возвращает данные для конкретного трекера.
     * @param $trackerCode
     * @param $data
     * @return array
     */
    protected function getPrepareResultDataForSelectTracker($trackerCode, $data): array
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

    /**
     * Останавливает все активные трекеры
     * @return array
     */
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