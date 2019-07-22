<?php

namespace MVF\Servicer\Services;

use Carbon\Carbon;
use Monolog\ErrorHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;

class LogCapsule
{
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    private static $instance;
    private $extras;

    /**
     * LogCapsule constructor.
     *
     * @param array $extras The extra attributes to be added to the log
     */
    public function __construct(array $extras = [])
    {
        $this->extras = $extras;
    }

    /**
     * Create a custom Monolog instance for Laravel.
     *
     * @param array $config Configuration
     *
     * @return Logger
     */
    public function __invoke(array $config)
    {
        return self::$instance;
    }

    /**
     * Instantiates a new logger.
     *
     * @param Application $application The application in which the logger is being used
     */
    public static function setup(Application $application)
    {
        if (empty(self::$instance) === true) {
            $logger = new Logger('custom');

            $logger->pushProcessor(self::datadogProcessor());

            $errorLogHandler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, getenv('APP_LOG_LEVEL'));
            $jsonFormatter = new JsonFormatter();
            $errorLogHandler->setFormatter($jsonFormatter);
            $logger->pushHandler($errorLogHandler);

            self::$instance = $logger;

            $application->setCatchExceptions(false);

            ErrorHandler::register($logger);
        }
    }

    /**
     * Logs an info message to standard out.
     *
     * @param mixed $message Message to be logged
     *
     * @return \Exception|null
     */
    public function info($message)
    {
        return $this->write(static::INFO, $message);
    }

    /**
     * Logs an warning message to standard out.
     *
     * @param mixed $message Message to be logged
     *
     * @return \Exception|null
     */
    public function warning($message)
    {
        return $this->write(static::WARNING, $message);
    }

    /**
     * Logs an error message to standard out.
     *
     * @param mixed $message Message to be logged
     *
     * @return \Exception|null
     */
    public function error($message)
    {
        return $this->write(static::ERROR, $message);
    }

    /**
     * Logs the message for the provided severity.
     *
     * @param string       $severity The severity of the message
     * @param array|string $message  The message
     *
     * @return \Exception|null
     */
    private function write(string $severity, $message): ?\Exception
    {
        $exception = null;
        $combined = array_merge($message, $this->getTraces());
        self::$instance->$severity(\GuzzleHttp\json_encode($combined), $this->extras);

        return $exception;
    }

    /**
     * Adds the attributes to the logs.
     *
     * @return callable
     */
    private static function datadogProcessor(): callable
    {
        return function ($entry) {
            $payload = [
                'message' => $entry['message'],
                'level' => $entry['level'],
                'severity' => $entry['level_name'],
                'timestamp' => self::dateToString($entry['datetime']),
            ];

            if (empty($entry['context']) === false) {
                $payload = array_merge($payload, $entry['context']);
            }

            return array_merge($payload, self::getTraces());
        };
    }

    /**
     * Converts datetime object to string.
     *
     * @param Carbon|\DateTime $datetime The datetime object
     *
     * @return string
     */
    private static function dateToString($datetime): string
    {
        $dateTimeUTC = new \DateTimeZone('UTC');

        return $datetime->getTimestamp() . $datetime->setTimezone($dateTimeUTC)->format('v');
    }

    /**
     * Gets the trace and span id.
     *
     * @return array
     */
    private static function getTraces(): array
    {
        $json = TracerCapsule::injectCarrier();
        $carrier = TracerCapsule::decodeCarrier($json);
        if (TracerCapsule::isValidCarrier($carrier) === true) {
            return [
                'trace' => $carrier['x-datadog-trace-id'],
                'span' => $carrier['x-datadog-parent-id'],
            ];
        }

        return [];
    }
}
