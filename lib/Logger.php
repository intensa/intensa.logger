<?php

declare(strict_types=1);

namespace Intensa\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Intensa\Logger\Tools\Settings;

/**
 * Class Logger
 * @package Intensa\Logger
 */
class Logger implements LoggerInterface
{

    /**
     * Шаблон записей в лог-файле
     */
    const LOG_TEMPLATE = '{date}{file}{level}: {message} {context}';

    /**
     * @var string
     */
    protected string $dateFormat = '';

    /**
     * @var string
     */
    protected string $additionalDir = '';

    /**
     * @var string
     */
    protected string $loggerCode = '';

    /**
     * @var Settings|null
     */
    protected ?Settings $settings = null;

    /**
     * @var string
     */
    protected string $initLogDir = '';

    /**
     * @var bool|int
     */
    protected $identifier = false;

    /**
     * @var string
     */
    protected string $writeFilePath = '';

    /**
     * @var string
     */
    protected string $execPathFile = '';

    /**
     * @var bool
     */
    protected bool $useBacktrace = false;

    /**
     * @var bool
     */
    protected bool $convertCP1251 = false;

    /**
     * Массив таймеров выполнения
     * @var array
     */
    protected array $timers = [];

    /**
     * @var bool
     */
    protected bool $rewriteLogFile = false;

    /**
     * @var int
     */
    protected int $execLogCount = 0;

    /**
     * @var int
     */
    protected int $filePermission = 0644;

    /**
     * В свойстве храниться объект класса ILogSql
     * @var ?SqlTracker
     */
    protected ?SqlTracker $sqlTracker = null;

    protected bool $canWrite = true;

    protected bool $writeJson = false;

    protected ?Writer $writer = null;

    /**
     * ILog constructor.
     * @param string $code код логгера
     * @param string $additionalLogDir дополнительная директория для хранения логов
     * @throws \Exception
     */
    public function __construct(string $code = 'common', string $additionalLogDir = '')
    {
        $this->settings = Settings::getInstance();
        $this->dateFormat = $this->settings->DATE_FORMAT();

        $code = str_replace(['/', '\\'], '_', $code);
        $code = str_replace('.', '', $code);
        $this->loggerCode = $code;

        $this->filePermission = $this->prepareFilePermissionMask($this->settings->LOG_FILE_PERMISSION());

        if (!empty($additionalLogDir)) {
            $this->setAdditionalDir($additionalLogDir);
        }

        try {
            $filePath = $this->initWriteFilePath();
            $this->writer = new Writer($filePath);
        } catch (\Exception $e) {
            $this->canWrite = false;
        }

        if ($this->settings->USE_BACKTRACE() === 'Y') {
            $this->useBacktrace = true;
        }

        if ($this->settings->WRITE_JSON() === 'Y') {
            $this->writeJson = true;
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
    public function initLogDir(): string
    {
        if (empty($this->initLogDir)) {
            $path = $this->getLogDirPath();
            $day = date('Y-m-d');
            $currentDayLogDir = $path . $day;

            if (file_exists($currentDayLogDir)) {
                $this->initLogDir = $currentDayLogDir;
            } else {
                $createDir = mkdir($currentDayLogDir, $this->filePermission, true);

                if ($createDir) {
                    chmod($currentDayLogDir, $this->filePermission);
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
        if ($this->writeJson) {
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
    public function setAdditionalDir(string $dirName): void
    {
        $dirName = str_replace(['/', '\\'], '', $dirName);

        if (!empty($dirName)) {
            $this->additionalDir = $dirName;
        }
    }

    /**
     * Метод заставляет логгер перезаписывать файл при каждом новом вызове логирующего метода
     * @return $this
     */
    public function rewrite(): Logger
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
        $date = sprintf('[%s] ', \date($this->dateFormat));

        $file = '';
        $message = (!empty($msg)) ? $msg : '';

        if ($this->useBacktrace) {
            $execLogMethodFileData = $this->backtrace();
            $this->execPathFile = $execLogMethodFileData['file'];

            if (!empty($execLogMethodFileData)) {
                $strBacktraceData = implode(':', $execLogMethodFileData);

                if (is_array($context) && array_key_exists('STOP_POINT', $context) && empty($context['STOP_POINT'])) {
                    $context['STOP_POINT'] = $strBacktraceData;
                }

                $file = sprintf('[%s] ', $strBacktraceData);
            }
        }

        if (array_key_exists('exception', $context) && $context['exception'] instanceof \Throwable) {
            $context['exception'] = $this->normalizeException($context['exception']);
        }

        $logContext = var_export($context, true);

        $logData = [
            $date,
            $level,
            $file,
            $message,
            $logContext
        ];

        return str_replace(
                ['{date}', '{level}', '{file}', '{message}', '{context}'],
                $logData,
                self::LOG_TEMPLATE
            );
    }

    protected function normalizeException(\Throwable $exception): string
    {
        return sprintf(
            "%s(code: %s): %s at %s:%s" . PHP_EOL . "[stacktrace] %s",
            get_class($exception),
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }

    /**
     * @param $level
     * @param $msg
     * @param $context
     * @return string
     */
    public function prepareRecordJsonFormat($level, $msg, $context): string
    {
        $logItems = [
            'time' => date($this->dateFormat),
            'level' => $level,
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
     * @param string $level
     * @param string $msg
     * @param array $context
     */
    public function write(string $level, string $msg = '', array $context = []): void
    {
        $logString = ($this->writeJson) ? $this->prepareRecordJsonFormat($level, $msg, $context) : $this->prepareRecordHumanFormat($level, $msg, $context);

        if ($this->canWrite) {

            if ($this->convertCP1251) {
                $logString = \iconv('windows-1251', 'utf-8', $logString);
            }

            if ($this->rewriteLogFile && $this->execLogCount === 0) {
                $this->writer->setFileModeRewrite();
            }

            $logString .= PHP_EOL;

            $this->writer->write($logString);
        }
    }

    /**
     * @return bool
     */
    public function getWriteFilePath(): string
    {
        return $this->writeFilePath;
    }

    /**
     * @return bool
     */
    public function getExecPathFile(): string
    {
        return $this->execPathFile;
    }

    /**
     * @throws \Exception
     */
    protected function initWriteFilePath(): string
    {
        $this->writeFilePath = $this->getLogDir($this->additionalDir) . $this->getLogFileName();

        return $this->writeFilePath;
    }

    /**
     * Метод позволяет включить отладку
     */
    public function useBacktrace(): void
    {
        $this->useBacktrace = true;
    }

    /**
     * Метод позволяет отключить отладку
     */
    public function notUseBacktrace(): void
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
        $backtraceData = debug_backtrace(limit: 4);

        if (!$backtraceData) {
            return $return;
        }

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
    public function startTimer(string $timerCode): void
    {
        $objLoggerTimer = new Timer($timerCode);

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
    public function stopTimer(string $timerCode = '', bool $autoStop = false): void
    {
        if (array_key_exists($timerCode, $this->timers)) {
            $currentTimer = $this->timers[$timerCode];
            $timerData = $currentTimer->stop()->getTimerData();

            if ($autoStop) {
                $timerData['STOP_POINT'] = '__destruct';
            }

            $this->write(LogLevel::INFO, "Timer {$timerData['CODE']}", $timerData);
        }
    }

    /**
     * Метод создает трекер sql запросов
     * @param string $code
     */
    public function startSqlTracker(string $code = 'common'): void
    {
        if (is_null($this->sqlTracker)) {
            $this->sqlTracker = new SqlTracker();
        }

        $this->sqlTracker->start($code);
    }

    /**
     * Метод останавливает трекер sql запросов и записывает результат в лог файл
     * @param string $code
     */
    public function stopSqlTracker(string $code = 'common'): void
    {
        if ($this->sqlTracker instanceof SqlTracker) {
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
    protected function logSqlTracker($code, $data): void
    {
        $this->write(LogLevel::INFO, "SqlTracker {$code}:", $data);
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->write($level, $message, $context);

        $this->execLogCount++;
    }

    /**
     * Деструктор останавливает все незавершенные таймеры и sql-трекеры
     */
    function __destruct()
    {
        if (!empty($this->timers)) {
            foreach ($this->timers as $timerCode => $objTimer) {
                if ($objTimer instanceof Timer && !$objTimer->isDie()) {
                    $this->stopTimer($timerCode, true);
                }
            }
        }

        if ($this->sqlTracker instanceof SqlTracker) {
            $autoStopTrackersData = $this->sqlTracker->stopAll();

            foreach ($autoStopTrackersData as $trackerCode => $trackerItem) {
                $this->logSqlTracker($trackerCode, $trackerItem);
            }
        }
    }
}