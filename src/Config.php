<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use Keboola\HttpExtractor\Config\ConfigDefinition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Config
{
    /** @var array */
    private $config;

    private function __construct(
        array $config
    ) {
        $this->config = $config;
    }

    public static function fromFile(string $configPath)
    {
        $contents = file_get_contents($configPath);
        $decoder = new JsonDecode(true);
        $config = $decoder->decode($contents, JsonEncoder::FORMAT);
        return new self($config);
    }

    public static function fromArray(array $config)
    {
        $definition = new ConfigDefinition();
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration($definition, [$config['parameters']]);
        return new self($processedConfig);
    }

    public function getData()
    {
        return $this->config;
    }

    public function getHttpSource()
    {
        $missingOutputFileException = new Exception('Extractor needs output file mapping to work');
        if (!array_key_exists('output', $this->config)) {
            throw $missingOutputFileException;
        }
        if (!array_key_exists('files', $this->config['output'])) {
            throw $missingOutputFileException;
        }
        return $this->config['output']['files'][0];
    }
}
