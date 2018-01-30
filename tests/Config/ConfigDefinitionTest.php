<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideValidConfigs
     */
    public function testValidConfigDefinition(array $inputConfig, array $expectedConfig): void
    {
        $definition = new ConfigDefinition();
        $processor = new Processor();

        $processedConfig = $processor->processConfiguration($definition, [$inputConfig]);

        $this->assertSame($expectedConfig, $processedConfig);
    }

    /**
     * @return mixed[][]
     */
    public function provideValidConfigs(): array
    {
        return [
            'minimal config' => [
                [
                    'downloadUrlBase' => 'http://www.google.com',
                    'downloadUrlPath' => 'path',
                ],
                [
                    'downloadUrlBase' => 'http://www.google.com',
                    'downloadUrlPath' => 'path',
                ],
            ],
            'minimal config with saveAs' => [
                [
                    'downloadUrlBase' => 'http://www.google.com',
                    'downloadUrlPath' => 'path',
                    'saveAs' => 'newFilename',
                ],
                [
                    'downloadUrlBase' => 'http://www.google.com',
                    'downloadUrlPath' => 'path',
                    'saveAs' => 'newFilename',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfigDefinition(
        array $inputConfig,
        string $expectedExceptionClass,
        string $expectedExceptionMessage
    ): void {
        $definition = new ConfigDefinition();
        $processor = new Processor();

        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $processor->processConfiguration($definition, [$inputConfig]);
    }

    /**
     * @return mixed[][]
     */
    public function provideInvalidConfigs(): array
    {
        return [
            'empty parameters' => [
                [],
                InvalidConfigurationException::class,
                'The child node "downloadUrlBase" at path "parameters" must be configured.',
            ],
            'missing url base' => [
                [
                    'downloadUrlPath' => 'path',
                ],
                InvalidConfigurationException::class,
                'The child node "downloadUrlBase" at path "parameters" must be configured.',
            ],
            'missing url path' => [
                [
                    'downloadUrlBase' => 'path',
                ],
                InvalidConfigurationException::class,
                'The child node "downloadUrlPath" at path "parameters" must be configured.',
            ],
            'unknown option' => [
                [
                    'downloadUrlBase' => 'http://www.google.com',
                    'downloadUrlPath' => 'path',
                    'other' => false,
                ],
                InvalidConfigurationException::class,
                'Unrecognized option "other" under "parameters"',
            ],
        ];
    }
}
