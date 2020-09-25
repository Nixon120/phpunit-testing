<?php

namespace Factories;

use FluentHandler\FluentHandler;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Logger;

class LoggerFactory
{
    /**
     * @var Logger
     */
    private static $logger;

    public static function getInstance()
    {
        if (self::$logger === null) {
            $logger = new \Monolog\Logger('Rewardstack');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler(
                'php://stdout',
                \Monolog\Logger::INFO
            ));

            $logging = new LoggingClient([
                'projectId' => 'green-talent-129607',
                'keyFile' => json_decode(getenv('STACKDRIVER_KEYFILE'), true)
            ]);
            $logger = $logging->psrLogger(getenv('ENVIRONMENT') . '_Rewardstack');

            self::$logger = $logger;
        }
        return self::$logger;
    }
}
