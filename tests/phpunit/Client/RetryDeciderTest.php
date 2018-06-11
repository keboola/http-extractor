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
        ?RequestException $exception
    ): void {
        $decider = new RetryDecider();
        $this->assertSame(
            $expected,
            $decider($retries, $request, $response, $exception)
        );
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
            ],
            'no retry for http 500 and more than max retries' => [
                false,
                6,
                null,
                new Response(500),
                null,
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
            ],
            'retry for connect exception with incorrect code and less than max retries' => [
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

            ],
            'retry with header 10 minutes in future' => [
                true,
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('10 minutes'))->format(DATE_RFC1123),
                ]),
                null,
            ],
            'retry without header' => [
                true,
                1,
                null,
                new Response(429),
                null,
            ],
            'don\'t retry with header 2 days in future' => [
                false,
                1,
                null,
                new Response(429, [
                    'Retry-after' => (new DateTimeImmutable('2 days'))->format(DATE_RFC1123),
                ]),
                null,
            ],
        ];
    }
}
