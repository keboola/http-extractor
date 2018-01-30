<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Config
{
    /** @var array */
    private $config;

    public function __construct(
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

    public function getData()
    {
        return $this->config;
    }
}
