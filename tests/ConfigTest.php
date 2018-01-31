<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use Keboola\HttpExtractor\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testCreateFromFile(): void
    {
        $configFilePath = __DIR__ . '/fixtures/config.json';

        $config = Config::fromFile($configFilePath);

        $this->assertSame([
            'baseUrl' => 'http://google.com/',
            'path' => 'favicon.ico',
            'saveAs' => null,
        ], $config->getData());
    }

    public function testCreateFromArray(): void
    {
        $configArray = [
            'baseUrl' => 'http://google.com/',
            'path' => 'favicon.ico',
        ];
        $config = new Config($configArray);
        $this->assertSame([
            'baseUrl' => 'http://google.com/',
            'path' => 'favicon.ico',
            'saveAs' => null,
        ], $config->getData());
    }

    public function testCustomGetters(): void
    {
        $configArray = [
            'baseUrl' => 'http://google.com/',
            'path' => 'favicon.ico',
            'saveAs' => 'favicon-local.ico',
        ];
        $config = new Config($configArray);

        $this->assertSame('http://google.com/', $config->getBaseUrl());
        $this->assertSame('favicon.ico', $config->getPath());
        $this->assertSame('favicon-local.ico', $config->getSaveAs());
    }
}
