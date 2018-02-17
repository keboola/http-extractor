<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use Keboola\HttpExtractor\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testCustomGetters(): void
    {
        $configArray = [
            'parameters' => [
                'baseUrl' => 'http://google.com/',
                'path' => 'favicon.ico',
                'saveAs' => 'favicon-local.ico',
            ],
        ];
        $config = new Config($configArray);

        $this->assertSame('http://google.com/', $config->getBaseUrl());
        $this->assertSame('favicon.ico', $config->getPath());
        $this->assertSame('favicon-local.ico', $config->getSaveAs());
    }

    /**
     * @dataProvider provideConfigAndExpectedForSaveAs
     */
    public function testGetSaveAsBehavior(?string $expected, array $configArray): void
    {
        $config = new Config($configArray);

        $this->assertSame($expected, $config->getSaveAs());
    }

    /**
     * @return mixed[][]
     */
    public function provideConfigAndExpectedForSaveAs(): array
    {
        return [
            'empty string ' => [
                null,
                [
                    'parameters' => [
                        'baseUrl' => 'http://google.com/',
                        'path' => 'favicon.ico',
                        'saveAs' => '',
                    ],
                ],
            ],
            'null' => [
                null,
                [
                    'parameters' => [
                        'baseUrl' => 'http://google.com/',
                        'path' => 'favicon.ico',
                        'saveAs' => null,
                    ],
                ],
            ],
            'string' => [
                'file.txt',
                [
                    'parameters' => [
                        'baseUrl' => 'http://google.com/',
                        'path' => 'favicon.ico',
                        'saveAs' => 'file.txt',
                    ],
                ],
            ],

        ];
    }
}
