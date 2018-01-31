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

    public function __construct(
        array $config
    ) {
        $definition = new ConfigDefinition();
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration($definition, [$config]);
        $this->config = $processedConfig;
    }

    public static function fromFile(string $configFilePath): self
    {
        $contents = file_get_contents($configFilePath);
        $decoder = new JsonDecode(true);
        $config = $decoder->decode($contents, JsonEncoder::FORMAT);

        return new self($config['parameters']);
    }

    public function getData(): array
    {
        return $this->config;
    }

    public function getBaseUrl(): string
    {
        return $this->config['baseUrl'];
    }

    public function getPath(): string
    {
        return $this->config['path'];
    }

    public function getSaveAs(): ?string
    {
        return $this->config['saveAs'];
    }
}
