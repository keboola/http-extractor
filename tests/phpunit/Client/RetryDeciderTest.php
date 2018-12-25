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
     * @dataProvider provideDecisionData
     */
    public function testDecide(
        bool $expected,
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
        $this->assertSame(
            $expected,
            $decider($retries, $request, $response, $exception),
            'Failed asserting whether to retry'
        );
        $errorMessages = array_map(
            function ($item) {
                return $item['message'];
            },
            $testHandler->getRecords()
        );
        $this->assertCount($message === null ? 0 : 1, $errorMessages);

        if ($message !== null) {
            $testHandler->hasInfoThatContains($message);
        }
    }

    /**
     * @return mixed[][]
     */
    public function provideDecisionData(): array
    {
        return [
            'retry for http 500 and less than max retries' => [
                true,
                3,
                null,
                new Response(500),
                null,
                'Retrying based on "500" HTTP status',
            ],
            'no retry for http 500 and more than max retries' => [
                false,
                6,
                null,
                new Response(500),
                null,
                'Max retries exceeded',
            ],
            'retry for connect exception with correct code and less than max retries' => [
                true,
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
                true,
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
                true,
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
            'retry for connect exception with not retryable code and less than max retries' => [
                false,
                3,
                null,
                null,
                new ConnectException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    ['errno' => \CURLE_BAD_DOWNLOAD_RESUME]
                ),
                null,
            ],
            'no retry for connect exception and more than max retries' => [
                false,
                6,
                null,
                null,
                new ConnectException(
                    'Err',
                    new Request('get', '/'),
                    null,
                    ['errno' => \CURLE_COULDNT_CONNECT]
                ),
                'Max retries exceeded',
            ],
            'retry with header 10 minutes in future' => [
                true,
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('10 minutes'))->format(DATE_RFC1123),
                ]),
                null,
                'Retrying based on "429" HTTP status',
            ],
            'retry without header' => [
                true,
                1,
                null,
                new Response(429),
                null,
                'Retrying based on "429" HTTP status',
            ],
            'don\'t retry with header 2 days in future' => [
                false,
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('2 days'))->format(DATE_RFC1123),
                ]),
                null,
                'Aborting due to Retry-After header value',
            ],
        ];
    }
}
