<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Keboola\HttpExtractor\Client\ExponentialDelay;
use Keboola\HttpExtractor\Client\RetryDecider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Client extends \GuzzleHttp\Client
{
    public function __construct(
        LoggerInterface $logger,
        array $config = []
    ) {
        if (!isset($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        }
        $stack = $config['handler'];

        // Timeouts
        $config['connect_timeout'] = 60; // 60 seconds
        $config['timeout'] = 15 * 60 * 60; // 15 minutes

        // Retry
        $stack->push(Middleware::retry(new RetryDecider($logger), new ExponentialDelay()));

        // Logger
        $logger = new Logger('logger');
        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $handler->setFormatter(new LineFormatter("%message%\n"));
        $logger->pushHandler($handler);
        $messageFormatter = new MessageFormatter(
            '{code} {phrase}: {method} "{target}"'
        );
        $stack->push(Middleware::log($logger, $messageFormatter));

        parent::__construct($config);
    }
}
