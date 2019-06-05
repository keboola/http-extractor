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
                        'baseUrl' => 'http://www.google.com',
                    ],
                ],
                InvalidConfigurationException::class,
                'The child node "path" at path "root.parameters" must be configured.',
            ],
            'invalid url protocol' => [
                [
                    'parameters' => [
                        'baseUrl' => 'fake://www.google.com',
                    ],
                ],
                InvalidConfigurationException::class,
                'Protocol is not valid. Only http and https are allowed.',
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
                        'maxRedirects' => '',
                    ],
                ],
                InvalidConfigurationException::class,
                'Invalid configuration for path "root.parameters.maxRedirects": ' .
                'Max redirects must be positive integer',
            ],
            'mangled protocol' => [
                [
                    'parameters' => [
                        'baseUrl' => 'htt-p://www.google.com',
                        'path' => 'path',
                    ],
                ],
                InvalidConfigurationException::class,
                'Invalid configuration for path "root.parameters.baseUrl": ' .
                'Protocol is not valid. Only http and https are allowed.',
            ],
        ];
    }
}
