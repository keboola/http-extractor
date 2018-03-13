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
            ],
        ];
        $config = new Config($configArray);

        $this->assertSame('http://google.com/', $config->getBaseUrl());
        $this->assertSame('favicon.ico', $config->getPath());
    }
}
