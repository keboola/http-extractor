<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use Keboola\HttpExtractor\HttpExtractorComponent;
use PHPUnit\Framework\TestCase;

class HttpExtractorComponentTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testJoinPathSegments(
        string $expected,
        string $firstSegment,
        string $secondSegment
    ): void {
        $this->assertSame(
            $expected,
            HttpExtractorComponent::joinPathSegments($firstSegment, $secondSegment)
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider(): array
    {
        return [
            'two adjectent slashes' => [
                'http://google.com/favicon.ico',
                'http://google.com/',
                '/favicon.ico',
            ],
            'slash only in first' => [
                'http://google.com/favicon.ico',
                'http://google.com/',
                'favicon.ico',
            ],
            'slash only in second' => [
                'http://google.com/favicon.ico',
                'http://google.com',
                '/favicon.ico',
            ],
            'no slashes' => [
                'http://google.com/favicon.ico',
                'http://google.com',
                'favicon.ico',
            ],
        ];
    }
}
