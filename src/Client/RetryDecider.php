<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Client;

use DateTimeImmutable;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use function in_array;
use function Keboola\Utils\isValidDateTimeString;

class RetryDecider
{
    private const MAX_RETRIES = 5;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(
        int $retries,
        ?Request $request,
        ?Response $response = null,
        ?RequestException $exception = null
    ): bool {
        if ($retries >= self::MAX_RETRIES) {
            $this->logger->info('Aborting retry, max retries exceeded');
            return false;
        }

        if ($this->shouldAbortBasedOnRetryAfterHeader($response)) {
            $this->logger->info('Aborting retry due to Retry-After header value');
            return false;
        }

        if ($this->isRecoverableHttpStatus($response)) {
            if ($response) {
                $this->logger->info(sprintf(
                    'Retrying based on "%s" HTTP status',
                    $response->getStatusCode()
                ));
            }
            return true;
        }

        if ($this->isRecoverableException($exception)) {
            if ($exception instanceof RequestException) {
                $this->logger->info(sprintf(
                    'Retrying based on CURL error code "%s"',
                    $exception->getHandlerContext()['errno']
                ));
            }
            return true;
        }

        return false;
    }

    private function isRecoverableHttpStatus(?Response $response = null): bool
    {
        return $response && in_array($response->getStatusCode(), [500, 502, 503, 504, 408, 420, 429]);
    }

    private function isRecoverableException(?RequestException $exception = null): bool
    {
        if (!$exception instanceof RequestException) {
            return false;
        }

        if (!isset($exception->getHandlerContext()['errno'])) {
            return false;
        }

        $curlErrorNumber = $exception->getHandlerContext()['errno'];
        if (!$this->isCurlRetryCode($curlErrorNumber)) {
            return false;
        }

        return true;
    }

    private function isCurlRetryCode(int $curlErrorNumber): bool
    {
        // https://github.com/curl/curl/blob/571280678594c4ccfbfcad854c76e02d0e350809/src/tool_operate.c#L1541
        return in_array($curlErrorNumber, [
            CURLE_COULDNT_CONNECT,
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_RESOLVE_PROXY,
            CURLE_GOT_NOTHING,
            CURLE_OPERATION_TIMEOUTED,
            CURLE_PARTIAL_FILE,
            CURLE_RECV_ERROR,
            CURLE_SSL_CONNECT_ERROR,
        ]);
    }

    private function shouldAbortBasedOnRetryAfterHeader(?Response $response): bool
    {
        if ($response === null) {
            // can't tell → don't abort
            return false;
        }

        if ($response->hasHeader('Retry-After')) {
            $retryAfter = $response->getHeader('Retry-After')[0];
            if (is_numeric($retryAfter)) {
                // abort if delay is too long
                return (1000 * $retryAfter) > ExponentialDelay::MAXIMAL_RETRY_DELAY;
            }
            if (isValidDateTimeString($retryAfter, DATE_RFC1123)) {
                $date = DateTimeImmutable::createFromFormat(DATE_RFC1123, $retryAfter);
                $delay = $date->getTimestamp() - time();

                // abort if delay is too long
                return $delay > ExponentialDelay::MAXIMAL_RETRY_DELAY;
            }

            // couldn't parse from header
            // can't tell → don't abort
            return false;
        }

        // can't tell → don't abort
        return false;
    }
}
