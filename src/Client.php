<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Keboola\HttpExtractor\Client\ExponentialDelay;
use Keboola\HttpExtractor\Client\RetryDecider;
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
        $stack->push(\GuzzleHttp\Middleware::retry(new RetryDecider($logger), new ExponentialDelay()));

        $config[RequestOptions::VERIFY] = CaBundle::getBundledCaBundlePath();
        parent::__construct($config);
    }
}
