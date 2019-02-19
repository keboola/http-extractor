<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests\Client;

use DateTimeImmutable;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\HttpExtractor\Client\RetryDecider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class RetryDeciderTest extends TestCase
{
    /**
     * @dataProvider provideRetryableData
     */
    public function testWillRetry(
        int $retries,
        ?Request $request,
        ?Response $response,
        ?RequestException $exception,
        ?string $message
    ): void {
        $logger = new Logger('test');
        $testHandler = new TestHandler();
        $logger->setHandlers([$testHandler]);
        $decider = new RetryDecider($logger);

        $this->assertTrue(
            $decider($retries, $request, $response, $exception)
        );
        $this->assertCount(1, $testHandler->getRecords());
        $testHandler->hasInfoThatContains($message);
    }

    /**
     * @return mixed[][]
     */
    public function provideRetryableData(): array
    {
        return [
            'retry for http 500 and less than max retries' => [
                3,
                null,
                new Response(500),
                null,
                'Retrying based on "500" HTTP status',
            ],
            'retry for connect exception with correct code and less than max retries' => [
                3,
                null,
                null,
                new ConnectException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    ['errno' => \CURLE_COULDNT_CONNECT]
                ),
                'Retrying based on CURL error code "7"',
            ],
            'retry for connect exception with CURLE 56 code and less than max retries' => [
                3,
                null,
                null,
                new RequestException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    new Exception(),
                    ['errno' => \CURLE_RECV_ERROR]
                ),
                'Retrying based on CURL error code "56"',
            ],
            'retry for request exception with CURLE_PARTIAL_FILE code' => [
                3,
                null,
                null,
                new RequestException(
                    'cURL error 18: transfer closed with 93525720 bytes remaining to read',
                    new Request('get', '/'),
                    null,
                    null,
                    ['errno' => \CURLE_PARTIAL_FILE]
                ),
                'Retrying based on CURL error code "18"',
            ],
            'retry with header 10 minutes in future' => [
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('10 minutes'))->format(DATE_RFC1123),
                ]),
                null,
                'Retrying based on "429" HTTP status',
            ],
            'retry without header' => [
                1,
                null,
                new Response(429),
                null,
                'Retrying based on "429" HTTP status',
            ],
        ];
    }

    /**
     * @dataProvider provideNotRetryableData
     */
    public function testWillNotRetry(
        int $retries,
        ?Request $request,
        ?Response $response,
        ?RequestException $exception,
        array $messages
    ): void {
        $logger = new Logger('test');
        $testHandler = new TestHandler();
        $logger->setHandlers([$testHandler]);
        $decider = new RetryDecider($logger);

        $this->assertFalse(
            $decider($retries, $request, $response, $exception)
        );
        $this->assertCount(count($messages), $testHandler->getRecords(), var_export($testHandler->getRecords(), true));
        foreach ($messages as $message) {
            $this->assertTrue(
                $testHandler->hasInfoThatContains($message),
                var_export($testHandler->getRecords(), true)
            );
        }
    }

    /**
     * @return mixed[][]
     */
    public function provideNotRetryableData(): array
    {
        return [
            'no retry for http 500 and more than max retries' => [
                6,
                null,
                new Response(500),
                null,
                ['Aborting retry, max retries exceeded'],
            ],
            'retry for connect exception with not retryable code and less than max retries' => [
                3,
                null,
                null,
                new ConnectException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    ['errno' => \CURLE_BAD_DOWNLOAD_RESUME]
                ),
                [],
            ],
            'no retry for connect exception and more than max retries' => [
                6,
                null,
                null,
                new ConnectException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    ['errno' => \CURLE_COULDNT_CONNECT]
                ),
                ['Aborting retry, max retries exceeded'],
            ],
            'don\'t retry with header 2 days in future' => [
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('2 days'))->format(DATE_RFC1123),
                ]),
                null,
                ['Aborting retry due to Retry-After header value'],
            ],
        ];
    }
}
