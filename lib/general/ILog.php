<?php

namespace Intensa\Logger;

/**
 * Class ILog
 * @method void debug(string $message, mixed $context)
 * @method void log(string $message, mixed $context)
 * @method void info(string $message, mixed $context)
 * @method void warning(string $message, mixed $context)
 * @method void error(string $message, mixed $context)
 * @method void fatal(string $message, mixed $context)
 * @package Intensa\Logger
 */
class ILog
{

    /**
     * Шаблон элементов для лог файла
     */
    const LOG_TEMPLATE = '{date}{level}{pid}{file} {message} {context}';
    
    /**
     * Уровни логирования
     */
    const LOG_LEVELS = [
        1 => 'debug',
        2 => 'info',
        3 => 'warning',
        4 => 'error',
        5 => 'fatal',
    ];

    /**
     * @var string
     */
    protected $dateFormat = '';

    /**
     * @var bool
     */
    protected $additionalDir = false;

    /**
     * @var string
     */
    protected $loggerCode = '';

    /**
     * @var Settings|null
     */
    protected $settings = null;

    /**
     * @var bool
     */
    protected $initLogDir = false;

    /**
     * @var bool|int
     */
    protected $identifier = false;

    /**
     * @var bool
     */
    protected $writePathFile = false;

    /**
     * @var bool
     */
    protected $execPathFile = false;

    /**
     * @var bool
     */
    protected $useBacktrace = false;

    /**
     * @var bool
     */
    protected $convertCP1251 = false;

    /**
     * Массив таймеров выполнения
     * @var array
     */
    protected $timers = [];

    /**
     * @var array
     */
    protected $additionalAlertEmails = [];

    /**
     * @var bool
     */
    protected $rewriteLogFile = false;

    /**
     * @var int
     */
    protected $execLogCount = 0;

    /**
     * @var int
     */
    protected $filePermission = 0;

    /**
     * В свойстве храниться объект класса ILogSql
     * @var null
     */
    protected $sqlTracker = null;

    /**
     * ILog constructor.
     * @param string $code
     */
    public function __construct(string $code = '')
    {
        $this->settings = Settings::getInstance();
        $this->dateFormat = $this->settings->DATE_FORMAT();
        $this->loggerCode = (!empty($code)) ? str_replace(['/', '\\'], '_', $code) : 'common';
        $this->identifier = getmypid();

        $settingsFilePermission = $this->settings->LOG_FILE_PERMISSION();
        $this->filePermission = $this->prepareFilePermissionMask($settingsFilePermission);

        if ($this->settings->USE_BACKTRACE() === 'Y') {
            $this->useBacktrace = true;
        }

        if (
            $this->settings->USE_CP1251() === 'Y'
            || (defined('SITE_CHARSET') && SITE_CHARSET === 'windows-1251')
        ) {
            $this->convertCP1251 = true;
        }
    }

    /**
     * Инициализация директории для логов вида logs/{current_date}/.
     * Если директории текущего дня еще не существует, то создает.
     * Созданной папке устанавливаются права заданные в настройках модуля.
     * @return bool|string
     * @throws
     */
    public function initLogDir() : string
    {
        if ($this->initLogDir === false) {
            $path = $this->getLogDirPath();
            $day = date('Y-m-d');
            $currentDayLogDir = $path . $day;

            if (file_exists($currentDayLogDir)) {
                $this->initLogDir = $currentDayLogDir;
            } else {
                $createDir = mkdir($currentDayLogDir, $this->filePermission, true);

                if ($createDir) {
                    chmod($currentDayLogDir, $this->filePermission);
                    chown($currentDayLogDir, 'www-data');
                    $this->initLogDir = $currentDayLogDir;
                } else {
                    throw new \Exception('Failed to create date folder. Check root path logs dif');
                }
            }
        }

        return $this->initLogDir;
    }

    /**
     * Метод возвращает код логгера
     * @return string
     */
    public function getLoggerCode(): string
    {
        return $this->loggerCode;
    }

    /**
     * Возвращает полный путь к основной папке логов.
     * @return string
     * @todo: Возможно стоит сделать возможность самостоятельно устанавливать полный путь.
     */
    protected function getLogDirPath(): string
    {
        return $this->settings->LOG_DIR();
    }

    /**
     * Метод возвращает имя файла основываясь на свойство $this->code, которое устанавливается в конструкторе
     * и расширения (задается в файле настроек).
     * @return string
     */
    protected function getLogFileName(): string
    {
        if ($this->settings->WRITE_JSON()) {
            $name = $this->loggerCode . '.json.log';
        } elseif ($this->settings->LOG_FILE_EXTENSION()) {
            $name = $this->loggerCode . $this->settings->LOG_FILE_EXTENSION();
        } else {
            $name = $this->loggerCode . '.log';
        }

        return $name;
    }

    /**
     * Метод получает 8-ое представления уровня доступа по переданной строке
     * @crunch метод является костылем, требует рефакторинга
     * @param $permission
     * @return int
     */
    protected function prepareFilePermissionMask($permission): int
    {
        $dictionary = [
            '0644' => 0644,
            '0755' => 0755,
            '0775' => 0775,
            '0777' => 0777,
        ];

        return (array_key_exists($permission, $dictionary)) ? $dictionary[$permission] : 0777;
    }

    /**
     * Возвращает текстовый код уровня лога
     * @param int $code
     * @return mixed
     */
    public function getLogLevel(int $code = 1): string
    {
        return (array_key_exists($code, self::LOG_LEVELS)) ? self::LOG_LEVELS[$code] : self::LOG_LEVELS[1];
    }

    /**
     * Возвращает путь к директории, в которую нужно положить файл.
     * Если нужно положиться текущий лог в отдельную папку -
     * передаем название доп. директории через аргумент $additionalDir
     * @param string $additionalDir
     * @return mixed|string
     * @throws \Exception
     */
    public function getLogDir(string $additionalDir = ''): string
    {

        $path = $this->initLogDir() . '{space}';

        if (!empty($additionalDir)) {
            $path = str_replace('{space}', '/' . $additionalDir . '/', $path);

            if (!file_exists($path)) {
                $mkdir = mkdir($path, $this->filePermission, true);

                if ($mkdir) {
                    chmod($path, $this->filePermission);
                }
            }
        } else {
            $path = str_replace('{space}', '/', $path);
        }

        return $path;
    }

    /**
     * Метод позволяет задать дополнительную директорию для логгера
     * @param string $dirName
     */
    public function setAdditionalDir(string $dirName)
    {
        if (!empty($dirName)) {
            $this->additionalDir = $dirName;
        }
    }

    /**
     * Метод заставляет логгер перезаписывать файл при каждом новом вызове логирующего метода
     * @return $this
     */
    public function rewrite(): ILog
    {
        $this->rewriteLogFile = true;
        return $this;
    }


    /**
     * @param $level
     * @param $msg
     * @param $context
     * @return string
     */
    protected function prepareRecordHumanFormat($level, $msg, $context): string
    {
        $date = '[' . date($this->dateFormat) . ']';
        $level = '[:' . $level . ']';
        $pid = '[pid:' . $this->identifier . ']';

        $file = '';
        $message = (!empty($msg)) ? $msg : '';

        if ($this->useBacktrace) {
            $execLogMethodFileData = $this->backtrace();
            $this->execPathFile = $execLogMethodFileData['file'];

            if (!empty($execLogMethodFileData)) {
                $strBacktraceData = implode(':', $execLogMethodFileData);

                // @crunch: маленький костыль для таймера выполнения. чтобы лишний раз не вызывать backtrace();
                if (is_array($context) && array_key_exists('STOP_POINT', $context) && empty($context['STOP_POINT'])) {
                    $context['STOP_POINT'] = $strBacktraceData;
                }

                $file = '[' . $strBacktraceData . ']';
            }
        }

        if (is_array($context) || is_object($context)) {
            $logContext = print_r($context, 1);
        } elseif (is_bool($context)) {
            $logContext = ($context) ? 'true' : 'false';
        } elseif (is_null($context)) {
            $logContext = 'null';
        } else {
            $logContext = $context;
        }

        $logData = [
            $date,
            $level,
            $pid,
            $file,
            $message,
            $logContext
        ];

        return str_replace(['{date}', '{level}', '{pid}', '{file}', '{message}', '{context}'], $logData,
                self::LOG_TEMPLATE) . PHP_EOL;
    }

    /**
     * @param $level
     * @param $msg
     * @param $context
     * @return string
     */
    public function prepareRecordJsonFormat($level, $msg, $context) : string
    {
        $logItems = [
            'time' => date($this->dateFormat),
            'level' => $level,
            'pid' => $this->identifier,
            'msg' => $msg,
            'context' => $context
        ];

        if ($this->useBacktrace) {
            $execLogMethodFileData = $this->backtrace();
            $this->execPathFile = $execLogMethodFileData['file'];

            if (!empty($execLogMethodFileData)) {
                $strBacktraceData = implode(':', $execLogMethodFileData);
                $logItems['file'] = $strBacktraceData;
            }
        }

        $jsonEncodeRecord = \json_encode($logItems);

        if (!empty($jsonEncodeRecord)) {
            $jsonEncodeRecord .= PHP_EOL;
        }

        return $jsonEncodeRecord;
    }

    /**
     * Метод формирует сообщение лога согласно шаблону и добавляет сообщение в свойство $this->logData
     * по средствам метода $this->setLogItemAdditionalDir()
     * @param int $level уровень лога
     * @param string $msg сообщение
     * @param $context доп. информация
     */
    public function write(int $level, string $msg = '', $context = false)
    {
        $levelSting = $this->getLogLevel($level);

        if ($this->settings->WRITE_JSON()) {
            $logString = $this->prepareRecordJsonFormat($levelSting, $msg, $context);
        } else {
            $logString = $this->prepareRecordHumanFormat($levelSting, $msg, $context);
        }

        $this->instantWriteFile($logString);

        if ($levelSting === 'fatal') {
            $objILogAlert = new ILogAlert($this);

            if (!empty($this->additionalAlertEmails)) {
                $objILogAlert->setAdditionalEmail($this->additionalAlertEmails);
            }

            if ($this->settings->WRITE_JSON()) {
                $logString = $this->prepareRecordHumanFormat($levelSting, $msg, $context);
            }

            $objILogAlert->send($logString);
        }
    }

    /**
     * @return bool
     */
    public function getWritePathFile(): bool
    {
        return $this->writePathFile;
    }

    /**
     * @return bool
     */
    public function getExecPathFile(): bool
    {
        return $this->execPathFile;
    }

    /**
     * Записывает в файл
     * @param string $strLogData
     */
    protected function instantWriteFile(string $strLogData)
    {
        $canWrite = true;

        try {
            $pathFile = $this->getLogDir($this->additionalDir) . $this->getLogFileName();
            $this->writePathFile = $pathFile;
        } catch (\Exception $e) {
            // Если есть проблема с инициализацией папки, не пишем. Письма отправим.
            $canWrite = false;
        }

        if ($canWrite) {
            if ($this->convertCP1251) {
                $strLogData = iconv('windows-1251', 'utf-8', $strLogData);
            }

            $openFile = fopen($pathFile, ($this->rewriteLogFile && $this->execLogCount === 0) ? 'w' : 'a');
            fwrite($openFile, $strLogData);
            fclose($openFile);
        }
    }


    /**
     * Через этот метод можно установить дополнительный email для получения алертов
     * @param string $email
     * @return $this
     */
    public function setAlertEmail(string $email): ILog
    {
        if (!is_array($email)) {
            $this->additionalAlertEmails = array_merge($this->additionalAlertEmails, $email);
        } else {
            $this->additionalAlertEmails[] = $email;
        }

        return $this;
    }

    /**
     * Метод позволяет включить отладку
     */
    public function useBacktrace()
    {
        $this->useBacktrace = true;
    }

    /**
     * Метод позволяет отключить отладку
     */
    public function notUseBacktrace()
    {
        $this->useBacktrace = false;
    }

    /**
     * Получаем данные о вызове методов логирования в проекте
     * @return array
     */
    public function backtrace(): array
    {
        $return = [];
        $backtraceData = debug_backtrace(false, 4);
        $execMethodRecord = array_pop($backtraceData);

        if (!empty($execMethodRecord) && is_array($execMethodRecord)) {
            $return = ['file' => $execMethodRecord['file'], 'line' => $execMethodRecord['line']];
        }

        return $return;
    }


    /**
     * Метод создает таймер выполнения с заданым кодом
     * @param string $timerCode
     */
    public function startTimer(string $timerCode)
    {
        $objLoggerTimer = new ILogTimer($timerCode);

        if ($this->useBacktrace) {
            $startPoint = implode(':', $this->backtrace());
            $objLoggerTimer->setStartPoint($startPoint);
        }

        $this->timers[$timerCode] = $objLoggerTimer;
    }

    /**
     * Метод останавливает таймер выполнения с заданым кодом
     * @param string $timerCode
     * @param bool $autoStop
     * @return void
     */
    public function stopTimer(string $timerCode = '', bool $autoStop = false)
    {
        if (array_key_exists($timerCode, $this->timers)) {
            $currentTimer = $this->timers[$timerCode];
            $timerData = $currentTimer->stop()->getTimerData();

            if ($autoStop) {
                $timerData['STOP_POINT'] = '__destruct';
            }

            $this->write(2, "Timer {$timerData['CODE']}", $timerData);
        }
    }

    /**
     * Метод создает трекер sql запросов
     * @param string $code
     */
    public function startSqlTracker(string $code = 'common')
    {
        if (is_null($this->sqlTracker)) {
            $this->sqlTracker = new ILogSql();
        }

        $this->sqlTracker->start($code);
    }

    /**
     * Метод останавливает трекер sql запросов и записывает результат в лог файл
     * @param string $code
     */
    public function stopSqlTracker(string $code = 'common')
    {
        if ($this->sqlTracker instanceof ILogSql) {
            $trackerResult = $this->sqlTracker->stop($code);
            $this->logSqlTracker($code, $trackerResult);
        }
    }

    /**
     * Отдельный метод для записи результата sql трекера в лог файл
     * Запись попадает в лог файл с уровнем info
     * @param $code
     * @param $data
     */
    protected function logSqlTracker($code, $data)
    {
        $this->write(2, "SqlTracker {$code}:", $data);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    public function __call(string $name, array $arguments): bool
    {
        if ($name === 'log') {
            $name = 'info';
        }

        if ($name === 'debug' && $this->settings->DEV_MODE() !== 'Y') {
            return false;
        }

        $logLevelCode = array_search($name, self::LOG_LEVELS);

        if ($logLevelCode !== false) {
            $this->write(
                $logLevelCode,
                $arguments[0],
                $arguments[1]
            );
            $this->execLogCount++;

            return true;
        }

        return false;
    }

    /**
     * В деструкторе останавливает все незавершенные таймеры и sql трекеры
     */
    function __destruct()
    {
        if (!empty($this->timers)) {
            foreach ($this->timers as $timerCode => $objTimer) {
                if ($objTimer instanceof ILogTimer && !$objTimer->isDie()) {
                    $this->stopTimer($timerCode, true);
                }
            }
        }

        if (!is_null($this->sqlTracker) && $this->sqlTracker instanceof ILogSql) {
            $autoStopTrackersData = $this->sqlTracker->stopAll();

            foreach ($autoStopTrackersData as $trackerCode => $trackerItem) {
                $this->logSqlTracker($trackerCode, $trackerItem);
            }
        }
    }
}