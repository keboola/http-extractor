<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests\Config;

use Keboola\HttpExtractor\Config\ConfigDefinition;
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
                    'parameters' => [
                        'baseUrl' => 'http://www.google.com',
                        'path' => 'path',
                    ],
                ],
                [
                    'parameters' => [
                        'baseUrl' => 'http://www.google.com',
                        'path' => 'path',
                    ],
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
                [
                    'parameters' => [],
                ],
                InvalidConfigurationException::class,
                'The child node "baseUrl" at path "root.parameters" must be configured.',
            ],
            'missing url base' => [
                [
                    'parameters' => [
                        'path' => 'path',
                    ],
                ],
                InvalidConfigurationException::class,
                'The child node "baseUrl" at path "root.parameters" must be configured.',
            ],
            'missing url path' => [
                [
                    'parameters' => [
                        'baseUrl' => 'path',
                    ],
                ],
                InvalidConfigurationException::class,
                'The child node "path" at path "root.parameters" must be configured.',
            ],
            'unknown option' => [
                [
                    'parameters' => [
                        'baseUrl' => 'http://www.google.com',
                        'path' => 'path',
                        'other' => false,
                    ],
                ],
                InvalidConfigurationException::class,
                'Unrecognized option "other" under "root.parameters"',
            ],
            'invalid max redirects' => [
                [
                    'parameters' => [
                        'baseUrl' => 'http://www.google.com',
                        'path' => 'path',
                        'client_options' => [
                            'max_redirects' => '',
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Invalid configuration for path "root.parameters.client_options.max_redirects": ' .
                'Max redirects must be positive integer',
            ],
        ];
    }
}
