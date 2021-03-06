<?php
namespace Intensa\Logger;

/**
 * Class ILog
 * @method void debug(string $message, array|object $context)
 * @method void log(string $message, array|object $context)
 * @method void info(string $message, array|object $context)
 * @method void warning(string $message, array|object $context)
 * @method void error(string $message, array|object $context)
 * @method void fatal(string $message, array|object $context)
 * @package Intensa\Logger
 */
class ILog
{
    /**
     * @var string
     */
    protected $logTemplate = "{date} {level} {file} {message} {context}";

    /**
     * @var array
     */
    protected $logLevel = [
        1 => 'debug',
        2 => 'info',
        3 => 'warning',
        4 => 'error',
        5 => 'fatal',
        6 => 'timer',
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
     * @var bool
     */
    protected $enableConstantDir = false;

    /**
     * @var bool|mixed|string
     */
    protected $loggerCode = false;
    /**
     * @var Settings|null
     */
    protected $settings = null;

    /**
     * @var bool
     */
    protected $initLogDir = false;
    /**
     * @var array
     */
    protected $logData = [
        'ITEMS' => [],
        'ADDITIONAL_DIR' => []
    ];

    /**
     * @var bool|int
     */
    protected $identifier = false;
    /**
     * @var bool
     */
    protected $useDelimiter = false;

    /**
     * @var bool
     */
    protected $writePathFile = false;
    protected $execPathFile = false;
    /**
     * @var bool
     */
    protected $sendAlert = false;
    /**
     * @var bool
     */
    protected $useBacktrace = false;

    /**
     * @var bool
     */
    protected $convertCP1251 = false;

    /**
     * @var array
     */
    protected $timers = [];
    /**
     * ILog constructor.
     * @param bool $code
     */

    protected $additionalAlertEmails = [];

    public function __construct($code = false)
    {
        $this->settings = Settings::getInstance();
        $this->dateFormat = $this->settings->DATE_FORMAT();
        $this->loggerCode = (!empty($code)) ? str_replace(['/', '\\'], '_', $code) : 'common';
        $this->identifier = getmypid();

        if ($this->settings->USE_DELIMITER() === true)
            $this->useDelimiter = true;


        if ($this->settings->USE_BACKTRACE() === true)
            $this->useBacktrace = true;

        if (defined('SITE_CHARSET'))
        {
            if (SITE_CHARSET === 'windows-1251')
                $this->convertCP1251 = true;
        }
    }

    /**
     * Инициализация директории для логов вида logs/{current_date}/.
     * Если директории текущего дня еще не существует, то создает.
     * Созданной папке устанавливаются права 0777.
     * @throws
     * @return bool|string
     */
    public function initLogDir()
    {
        if ($this->initLogDir === false)
        {
            $path = $this->getLogDirPath();
            //var_dump($path);
            $day = date('Y-m-d');
            $currentDayLogDir = $path . $day;

            if (file_exists($currentDayLogDir))
            {
                $this->initLogDir = $currentDayLogDir;
            }
            else
            {
                $createDir = mkdir($currentDayLogDir, 0777, true);

                if ($createDir)
                {
                    chmod($currentDayLogDir, 0777);
                    chown($currentDayLogDir, 'www-data');
                    $this->initLogDir = $currentDayLogDir;
                }
                else
                {
                    throw new \Exception('Failed to create date folder. Check root path logs dif');
                }
            }
        }

        return $this->initLogDir;
    }

    /**
     * @return bool|mixed|string
     */
    public function getLoggerCode()
    {
        return $this->loggerCode;
    }

    /**
     * Возвращает полный путь к основной папке логов.
     * @todo: Возможно стоит сделать возможность самостоятельно устанавливать полный путь.
     * @return string
     */
    protected function getLogDirPath()
    {
        $logDirPath =  $_SERVER['DOCUMENT_ROOT'] . $this->settings->LOG_DIR();

        if (!empty($this->settings->OWN_LOG_DIR()))
        {
            if (file_exists($this->settings->OWN_LOG_DIR()) && is_writable($this->settings->OWN_LOG_DIR()))
                $logDirPath = $this->settings->OWN_LOG_DIR();
        }

        return $logDirPath;
    }

    /**
     * Метод возвращает имя файла основываясь на свойство $this->code, которое устанавливается в конструкторе
     * и расширения (задается в файле настроек).
     * @return string
     */
    protected function getLogFileName()
    {
        if ($this->settings->LOG_FILE_EXTENSION())
        {
            $name = $this->loggerCode . $this->settings->LOG_FILE_EXTENSION();
        }
        else
        {
            $name = $this->loggerCode . '.txt';
        }

        return $name;
    }

    /**
     * Возвращает текстовый код уровня лога
     * @param int $code
     * @return mixed
     */
    public function getLogLevel($code = 1)
    {
        $level = (array_key_exists($code, $this->logLevel)) ? $this->logLevel[$code] : $this->logLevel[1];
        return $level;
    }

    /**
     * Возвращает путь к директории, в которую нужно положить файл.
     * Если нужно положиться текущий лог в отдельную папку -
     * передаем название доп. директории через аргумент $additionalDir
     * @param bool $additionalDir
     * @return mixed|string
     */
    public function getLogDir($additionalDir = false)
    {
        $path = $this->initLogDir() . '{space}' ;

        if (!empty($additionalDir) && $additionalDir !== false)
        {
            $path = str_replace('{space}', '/' . $additionalDir . '/', $path);

            if (!file_exists($path))
            {
                $mkdir = mkdir($path, 0777, true);
                if ($mkdir)
                    chmod($path, 0777);
            }
        }
        else
        {
            $path = str_replace('{space}', '/', $path);
        }

        return $path;
    }

    /**
     * @uses пока не используется
     * @param $dirName
     */
    public function setAdditionalDir($dirName)
    {
        if (!empty($dirName) && $this->enableConstantDir === false)
        {
            $this->additionalDir = $dirName;
        }
    }

    /**
     * @uses пока не используется
     */
    public function unsetAdditionalDir()
    {
        if ($this->enableConstantDir === false)
            $this->additionalDir = false;
    }

    /**
     * @uses пока не используется
     * @param $dirName
     */
    public function dir($dirName)
    {
        $this->enableConstantDir = true;
        $this->additionalDir = $dirName;
    }


    /**
     * Метод формирует сообщение лога согласно шаблону и добавляет сообщение в свойство $this->logData
     * по средствам метода $this->setLogItemAdditionalDir()
     * @param int $level уровень лога
     * @param bool $msg сообщение
     * @param bool $context доп. информация
     * @param bool|string $additionalDir имя доп. директории
     */
    public function write($level, $msg = false, $context = false, $additionalDir = false)
    {
        $date = '[' . date($this->dateFormat) . ']';
        $level = '[:' . $this->getLogLevel($level) . ']';

        if ($this->useDelimiter === false)
        {
            $level .= ' [ pid:' . $this->identifier . ']';
        }

        $file = '';
        $message = (!empty($msg)) ? $msg : '';
        $logContext = '';

        if ($this->useBacktrace)
        {
            $execLogMethodFileData = $this->backtrace();
            $this->execPathFile = $execLogMethodFileData['file'];

            if (!empty($execLogMethodFileData))
            {
                $strBacktraceData = implode(':', $execLogMethodFileData);

                // @crunch: маленький костыль для таймера выполнения. чтобы лишний раз не вызывать backtrace();
                if (is_array($context) && array_key_exists('STOP_POINT', $context) && empty($context['STOP_POINT']))
                    $context['STOP_POINT'] = $strBacktraceData;

                $file = '[' . $strBacktraceData . ']';
            }
        }

        if (!empty($context))
        {
            $logContext = (is_array($context) || is_object($context)) ? print_r($context, 1) : $context;
        }

        $logData = [
            $date,
            $level,
            $file,
            $message,
            $logContext
        ];

        $logString = str_replace(['{date}', '{level}', '{file}', '{message}', '{context}'], $logData, $this->logTemplate) . PHP_EOL;

        $this->setLogItem($logString);

        if ($additionalDir !== false)
        {
            $this->setLogItemAdditionalDir($additionalDir);
        }
        //$this->unsetAdditionalDir();
    }

    /**
     * @return mixed
     */
    public function getLogDataItems()
    {
        return $this->logData['ITEMS'];
    }

    /**
     * @return bool
     */
    public function getWritePathFile()
    {
        return $this->writePathFile;
    }

    public function getExecPathFile()
    {
        return $this->execPathFile;
    }

    /**
     * Записывает сообщения лога в файл.
     */
    protected function writeFile()
    {
        if (!empty($this->logData['ITEMS']))
        {
            $strLogData = implode('', $this->logData['ITEMS']);

            if ($this->useDelimiter)
            {
                $dl = $this->getLogDelimiter();
                $strLogData = $dl['start'] . $strLogData . $dl['end'];
            }

            $additionalDir = (!empty($this->logData['ADDITIONAL_DIR'])) ? $this->logData['ADDITIONAL_DIR'] : false;
            $canWrite = true;

            try
            {
                $pathFile = $this->getLogDir($additionalDir) . $this->getLogFileName();
                $this->writePathFile = $pathFile;

            }
            catch (\Exception $e)
            {
                //если есть проблема с инициализацией папки, не пишем. Письма отправим.
                $canWrite = false;
            }

            if ($canWrite)
            {
                if ($this->convertCP1251)
                    $strLogData = iconv('windows-1251', 'utf-8', $strLogData);

                file_put_contents($pathFile, $strLogData, FILE_APPEND);
                chmod($pathFile, 0777);
            }

            if ($this->sendAlert) {
                $objILogAlert = new ILogAlert($this);
                if (!empty($this->additionalAlertEmails)) {
                    $objILogAlert->setAdditionalEmail($this->additionalAlertEmails);
                }
                $objILogAlert->send();
            }
        }
    }

    public function setAlertEmail($email)
    {
        if (!is_array($email)) {
            $this->additionalAlertEmails = array_merge($this->additionalAlertEmails, $email);
        }
        else {
            $this->additionalAlertEmails[] = $email;
        }

        return $this;
    }

    /**
     * Указываем флаг о том, что нужно отправлять оповещения на почту
     */
    public function sendAlert()
    {
        $this->sendAlert = true;
    }

    /**
     * @param $item
     */
    public function setLogItem($item)
    {
        $this->logData['ITEMS'][] = $item;
    }

    /**
     * @param $dirName
     */
    public function setLogItemAdditionalDir($dirName)
    {
        $this->logData['ADDITIONAL_DIR'] = $dirName;
    }

    /**
     * Заставляет при записи в файл использовать разделители между пачками сообщений в логе
     */
    public function useLogDelimiter()
    {
        $this->useDelimiter = true;
    }

    /**
     *
     */
    public function notUseLogDelimiter()
    {
        $this->useDelimiter = false;
    }

    /**
     *
     */
    public function useBacktrace()
    {
        $this->useBacktrace = true;
    }

    /**
     *
     */
    public function notUseBacktrace()
    {
        $this->useBacktrace = false;
    }

    /**
     * Получаем данные о вызове методов логирования в проекте
     * @return array
     */
    public function backtrace()
    {
        $return = [];
        $backtraceData = debug_backtrace(FALSE, 3);
        $execMethodRecord = array_pop($backtraceData);

        if (!empty($execMethodRecord) && is_array($execMethodRecord))
        {
            $return = ['file' => $execMethodRecord['file'], 'line' => $execMethodRecord['line']];
        }

        return $return;
    }

    /**
     * Формирует разделитель
     * @return array
     */
    public function getLogDelimiter()
    {
        $delimiterChar = str_repeat('=', 30);
        return [
            'start' => $delimiterChar . '[START: ' . $this->identifier . ']' . $delimiterChar . PHP_EOL,
            'end' => $delimiterChar . '[END: ' . $this->identifier . ']' . $delimiterChar . PHP_EOL,
        ];
    }

    /**
     * @param $timerCode
     */
    public function startTimer($timerCode)
    {
        $objLoggerTimer = new ILogTimer($timerCode);

        if ($this->useBacktrace)
        {
            $startPoint = implode(':', $this->backtrace());
            $objLoggerTimer->setStartPoint($startPoint);
        }

        $this->timers[$timerCode] = $objLoggerTimer;

    }

    /**
     * @param bool $timerCode
     * @param bool $autoStop
     * @return mixed
     */
    public function stopTimer($timerCode = false, $autoStop = false)
    {
        if (array_key_exists($timerCode, $this->timers))
        {
            $currentTimer = $this->timers[$timerCode];
            $timerData = $currentTimer->stop()->getTimerData();

            if ($autoStop)
                $timerData['STOP_POINT'] = '__destruct';

            $this->write(6, 'Lead time:', $timerData);
        }
    }

    /**
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        if ($name === 'log')
            $name = 'info';

        $logLevelCode = array_search($name, $this->logLevel);

        if ($logLevelCode !== false)
        {
            $additionalFolder = false;

            if ($name === 'fatal')
            {
                $additionalFolder = 'error';
                $this->sendAlert();
            }

            $this->write($logLevelCode, $arguments[0], $arguments[1], $additionalFolder);
            return true;
        }

        return false;
    }

    /**
     *
     */
    function __destruct()
    {
        if (!empty($this->timers))
        {
            foreach ($this->timers as $timerCode => $objTimer)
            {
                if ($objTimer instanceof ILogTimer && !$objTimer->isDie())
                {
                    $this->stopTimer($timerCode, true);
                }
            }
        }

        $this->writeFile();
    }

}