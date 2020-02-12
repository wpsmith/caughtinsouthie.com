<?php
namespace Libsyn\Service;
/*
	This class is designed to handle the debug logger logging.
*/
use DateTime;
use RuntimeException;
use Libsyn\Service\Psr\Log\AbstractLogger;
use LIbsyn\Service\Psr\Log\LogLevel;

class Logger {

	/**
     * File name and path of log file.
	 * @since 1.0.1.6
     * @var string
     */
    private $log_file;
    /**
     * Log channel--namespace for log lines.
     * Used to identify and correlate groups of similar log lines.
	 * @since 1.0.1.6
     * @var string
     */
    private $channel;
    /**
     * Lowest log level to log.
	 * @since 1.0.1.6
     * @var int
     */
    private $log_level;
    /**
     * Whether to log to standard out.
	 * @since 1.0.1.6
     * @var bool
     */
    private $stdout;
    /**
     * Log level hierachy
     */
    private $LEVELS;
    /**
     * Log fields separated by tabs to form a TSV (CSV with tabs).
     */
    const TAB = "\t";
    /**
     * Log line break.
     */
    const EOL = "\r\n";
    /**
     * Special minimum log level which will not log any log levels.
     */
    const LOG_LEVEL_NONE = 'none';


    /**
     * Logger constructor
     *
	 * @since 1.0.1.6
     * @param string $log_file  File name and path of log file.
     * @param string $channel   Logger channel associated with this logger.
     * @param string $log_level (optional) Lowest log level to log.
     */
    public function __construct($log_file, $channel, $log_level = null) {
        $this->log_file  = $log_file;
        $this->channel   = $channel;
        $this->stdout    = false;
		$this->LEVELS = array(
			self::LOG_LEVEL_NONE => -1,
			LogLevel::DEBUG      => 0,
			LogLevel::INFO       => 1,
			LogLevel::NOTICE     => 2,
			LogLevel::WARNING    => 3,
			LogLevel::ERROR      => 4,
			LogLevel::CRITICAL   => 5,
			LogLevel::ALERT      => 6,
			LogLevel::EMERGENCY  => 7,
		);
		if ( empty($log_level) ) {
			$log_level = LogLevel::DEBUG;
		}
        $this->setLogLevel($log_level);
    }
    /**
     * Set the lowest log level to log.
     *
	 * @since 1.0.1.6
     * @param string $log_level
     */
    public function setLogLevel($log_level) {
        if (!array_key_exists($log_level, $this->LEVELS)) {
            throw new \DomainException("Log level $log_level is not a valid log level. Must be one of (" . implode(', ', array_keys($this->LEVELS)) . ')');
        }
        $this->log_level = $this->LEVELS[$log_level];
    }
    /**
     * Set the log channel which identifies the log line.
     *
	 * @since 1.0.1.6
     * @param string $channel
     */
    public function setChannel($channel) {
        $this->channel = $channel;
    }
    /**
     * Set the standard out option on or off.
     * If set to true, log lines will also be printed to standard out.
     *
	 * @since 1.0.1.6
     * @param bool $stdout
     */
    public function setOutput($stdout) {
        $this->stdout = $stdout;
    }
    /**
     * Log a debug message.
     * Fine-grained informational events that are most useful to debug an application.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array $data Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function debug($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::DEBUG)) {
            $this->log(LogLevel::DEBUG, $message, $data);
        }
    }
    /**
     * Log an info message.
     * Interesting events and informational messages that highlight the progress of the application at coarse-grained level.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function info($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::INFO)) {
            $this->log(LogLevel::INFO, $message, $data);
        }
    }
    /**
     * Log an notice message.
     * Normal but significant events.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function notice($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::NOTICE)) {
            $this->log(LogLevel::NOTICE, $message, $data);
        }
    }
    /**
     * Log a warning message.
     * Exceptional occurrences that are not errors--undesirable things that are not necessarily wrong.
     * Potentially harmful situations which still allow the application to continue running.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function warning($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::WARNING)) {
            $this->log(LogLevel::WARNING, $message, $data);
        }
    }
    /**
     * Log an error message.
     * Error events that might still allow the application to continue running.
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function error($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::ERROR)) {
            $this->log(LogLevel::ERROR, $message, $data);
        }
    }
    /**
     * Log a critical condition.
     * Application components being unavailable, unexpected exceptions, etc.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function critical($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::CRITICAL)) {
            $this->log(LogLevel::CRITICAL, $message, $data);
        }
    }
    /**
     * Log an alert.
     * This should trigger an email or SMS alert and wake you up.
     * Example: Entire site down, database unavailable, etc.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function alert($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::ALERT)) {
            $this->log(LogLevel::ALERT, $message, $data);
        }
    }
    /**
     * Log an emergency.
     * System is unsable.
     * This should trigger an email or SMS alert and wake you up.
     *
	 * @since 1.0.1.6
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws \RuntimeException
     */
    public function emergency($message = '', $data = null) {
        if ($this->logAtThisLevel(LogLevel::EMERGENCY)) {
            $this->log(LogLevel::EMERGENCY, $message, $data);
        }
    }
    /**
     * Log a message.
     * Generic log routine that all severity levels use to log an event.
     *
	 * @since 1.0.1.6
     * @param string $level   Log level
     * @param string $message Content of log event.
     * @param array  $data    Potentially multidimensional associative array of support data that goes with the log event.
     *
     * @throws \RuntimeException when log file cannot be opened for writing.
     */
    public function log($level, $message = '', $data = null) {
        // Build log line
        list($exception, $data) = $this->handleException($data);
        $data                   = $data ? json_encode($data, \JSON_UNESCAPED_SLASHES) : '';
        $data                   = $data ?: ''; // Fail-safe incase json_encode fails.
        $log_line               = $this->formatLogLine($level, $message, $data, $exception);
        // Log to file
        try {
            $fh = fopen($this->log_file, 'a');
            fwrite($fh, $log_line);
            fclose($fh);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Could not open log file {$this->log_file} for writing to Logger channel {$this->channel}!", 0, $e);
        }
        // Log to stdout if option set to do so.
        if ($this->stdout) {
            print($log_line);
        }
    }
    /**
     * Determine if the logger should log at a certain log level.
     *
	 * @since 1.0.1.6
     * @param  string $level
     *
     * @return bool True if we log at this level; false otherwise.
     */
    private function logAtThisLevel($level) {
        return $this->LEVELS[$level] >= $this->log_level;
    }
    /**
     * Handle an exception in the data context array.
     * If an exception is included in the data context array, extract it.
     *
	 * @since 1.0.1.6
     * @param  array  $data
     *
     * @return array  [exception, data (without exception)]
     */
    private function handleException($data = null) {
        if (isset($data['exception']) && $data['exception'] instanceof \Throwable) {
            $exception      = $data['exception'];
            $exception_data = $this->buildExceptionData($exception);
            unset($data['exception']);
        } else {
            $exception_data = '';
        }
        return array($exception_data, $data);
    }
    /**
     * Build the exception log data.
     *
	 * @since 1.0.1.6
     * @param  \Throwable $e
     *
     * @return string JSON {message, code, file, line, trace}
     */
    private function buildExceptionData(\Throwable $e) {
        $exceptionData = json_encode(
            array(
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace()
            ),
            \JSON_UNESCAPED_SLASHES
        );
        // Fail-safe in case json_encode failed
        return $exceptionData ?: '{"message":"' . $e->getMessage() . '"}';
    }
    /**
     * Format the log line.
     * YYYY-mm-dd HH:ii:ss.uuuuuu  [loglevel]  [channel] Log message content  {"Optional":"JSON Contextual Support Data"}  {"Optional":"Exception Data"}
     *
	 * @since 1.0.1.6
     * @param  string $level
     * @param  string $message
     * @param  string $data
     * @param  string $exception_data
     *
     * @return string
     */
    private function formatLogLine($level, $message, $data, $exception_data) {
        return
            $this->getTime()                              . self::TAB .
            "[$level]"                                    . self::TAB .
            "[{$this->channel}]"                          . self::TAB .
            str_replace(self::EOL, '   ', trim($message))  . self::TAB .
            str_replace(self::EOL, '   ', $data)           . self::TAB .
            str_replace(self::EOL, '   ', $exception_data) . self::EOL;
    }
    /**
     * Get current date time.
     * Format: YYYY-mm-dd HH:ii:ss.uuuuuu
     * Microsecond precision for PHP 7.1 and greater
     *
	 * @since 1.0.1.6
     * @return string Date time
     */
    private function getTime() {
        return (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');
    }

}
