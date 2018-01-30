<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testCreateFromFile(): void
    {
        $configFilePath = __DIR__ . '/fixtures/config.json';

        $config = Config::fromFile($configFilePath);

        $this->assertSame([
            'downloadUrlBase' => 'http://google.com/',
            'downloadUrlPath' => 'favicon.ico',
        ], $config->getData());
    }

    public function testCreateFromArray(): void
    {
        $configArray = [
            'parameters' => [
                'downloadUrlBase' => 'http://google.com/',
                'downloadUrlPath' => 'favicon.ico',
            ],
            'processors' => [
                'before' => [],
                'after' => [],
            ],
            'image_parameters' => [],
            'action' => 'run',
        ];
        $config = Config::fromArray($configArray);
        $this->assertSame([
            'downloadUrlBase' => 'http://google.com/',
            'downloadUrlPath' => 'favicon.ico',
        ], $config->getData());
    }

    public function testCustomGetters(): void
    {
        $configArray = [
            'parameters' => [
                'downloadUrlBase' => 'http://google.com/',
                'downloadUrlPath' => 'favicon.ico',
            ],
            'processors' => [
                'before' => [],
                'after' => [],
            ],
            'image_parameters' => [],
            'action' => 'run',
        ];
        $config = Config::fromArray($configArray);

        $this->assertSame('http://google.com/', $config->getDownloadUrlBase());
    }
}
