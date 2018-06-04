<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Client;

use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use function is_numeric;
use function Keboola\Utils\isValidDateTimeString;
use function strtotime;

class ExponentialDelay
{
    public const MAXIMAL_RETRY_DELAY = 60 * 60 * 24;

    public function __invoke(int $numberOfRetries, ?Response $response = null): int
    {
        $delay = $this->delayFromHeader($response);
        if ($delay === null) {
            $delay = 1000 * (2 ** ($numberOfRetries - 1));
        }
        return $this->filterDelay($delay);
    }

    private function delayFromHeader(?Response $response): ?int
    {
        if ($response === null) {
            return null;
        }
        if ($response->hasHeader('Retry-After')) {
            $retryAfter = $response->getHeader('Retry-After')[0];
            if (is_numeric($retryAfter)) {
                return (int) $retryAfter;
            }
            if (isValidDateTimeString($retryAfter, DATE_RFC1123)) {
                $date = DateTimeImmutable::createFromFormat(DATE_RFC1123, $retryAfter);
                $delay = $date->getTimestamp() - time();
                if ($delay < (time() - strtotime('1 day', 0))) {
                    // suggested delay is less than a day
                    return $delay;
                }

                // we don't want to wait too long
                return null;
            }

            // there was a header, but we couldn't parse it to delay
            return null;
        }

        // there was no Retry-After header
        return null;
    }

    private function filterDelay(int $delay): int
    {
        return min($delay, self::MAXIMAL_RETRY_DELAY);
    }
}
