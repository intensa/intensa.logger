<?php

declare(strict_types=1);

namespace Intensa\Logger;

class SqlTracker
{
    protected \Bitrix\Main\DB\Connection|\Bitrix\Main\Data\Connection|null $connection = null;
    protected array $trackersData = [];
    protected array $trackerPull = [];

    public function __construct()
    {
        $this->connection = \Bitrix\Main\Application::getConnection();
    }

    /**
     * Запускает отслеживание запросов для переданного кода.
     * @param string $code
     */
    public function start(string $code): void
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
     * @param string $code
     * @param \Bitrix\Main\Diag\SqlTracker $tracker
     */
    protected function setTracker(string $code, \Bitrix\Main\Diag\SqlTracker $tracker): void
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
     * @param string $code
     * @return array
     */
    public function getTracker(string $code): array
    {
        $result = [];

        if (array_key_exists($code, $this->trackerPull)) {
            $result = $this->trackerPull[$code];
        }

        return $result;
    }

    /**
     * Устанавливает флаг завершенности трекера
     * @param string $code
     */
    protected function finishTracker(string $code): void
    {
        if (array_key_exists($code, $this->trackerPull)) {
            $this->trackerPull[$code]['finish'] = true;
        }
    }

    /**
     * Устанавливает данные запросов для конкретного трекера
     * @param string $code
     * @param array $data
     */
    protected function setTrackerData(string $code, array $data): void
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
     * Останавливает трекер с переданным в качестве аргумента кодом.
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