<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\Psr7\Uri;
use Keboola\Component\BaseComponent;
use Keboola\HttpExtractor\Config\ConfigDefinition;
use Psr\Http\Message\UriInterface;

class HttpExtractorComponent extends BaseComponent
{
    public static function joinPathSegments(string $firstPart, string $secondPart): string
    {
        $separator = '/';

        $baseWithoutTrailing = rtrim($firstPart, $separator);
        $pathWithoutLeading = ltrim($secondPart, $separator);

        return $baseWithoutTrailing . $separator . $pathWithoutLeading;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    public function run(): void
    {
        $httpExtractor = new HttpExtractor(new Client());

        $uri = new Uri($this->getDownloadUrl());
        $httpExtractor->extract($uri, $this->getDestination($uri));
    }

    private function getDestination(UriInterface $uri): string
    {
        return $this->getDataDir() . '/out/files/' . basename($uri->getPath());
    }

    private function getDownloadUrl(): string
    {
        /** @var Config $config */
        $config = $this->getConfig();

        return self::joinPathSegments($config->getBaseUrl(), $config->getPath());
    }
}
