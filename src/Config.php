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

    public static function fromFile(string $configPath): self
    {
        $contents = file_get_contents($configPath);
        $decoder = new JsonDecode(true);
        $config = $decoder->decode($contents, JsonEncoder::FORMAT);

        return self::fromArray($config['parameters']);
    }

    public static function fromArray(array $config): self
    {
        $definition = new ConfigDefinition();
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration($definition, [$config]);
        return new self($processedConfig);
    }

    public function getData(): array
    {
        return $this->config;
    }

    public function getDownloadUrlBase(): string
    {
        return $this->config['downloadUrlBase'];
    }
}
