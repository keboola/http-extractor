<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests\Client;

use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use Keboola\HttpExtractor\Client\ExponentialDelay;

use PHPUnit\Framework\TestCase;

class ExponentialDelayTest extends TestCase
{
    /**
     * @dataProvider provideDecisionData
     */
    public function testDecide(
        int $expected,
        int $retries,
        ?Response $response = null
    ): void {
        $delay = new ExponentialDelay();
        $this->assertSame(
            $expected,
            $delay($retries, $response)
        );
    }

    /**
     * @return mixed[][]
     */
    public function provideDecisionData(): array
    {
        return [
            '1' => [
                1000,
                1,
            ],
            '2' => [
                2000,
                2,
            ],
            '3' => [
                4000,
                3,
            ],
            '3 + retry 100k seconds = more than max' => [
                86400,
                3,
                new Response(500, ['Retry-After' => '100000']),
            ],
            '3 + retry 3k seconds = less than max' => [
                3000,
                3,
                new Response(500, ['Retry-After' => '3000']),
            ],
            '3 + retry next week = more than max' => [
                86400,
                3,
                new Response(500, ['Retry-After' => (new DateTimeImmutable('1 week'))->format(\DATE_RFC1123)]),
            ],
        ];
    }
}
