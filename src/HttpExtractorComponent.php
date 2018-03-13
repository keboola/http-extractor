<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Keboola\Component\BaseComponent;
use Keboola\HttpExtractor\Config\ConfigDefinition;
use Psr\Http\Message\UriInterface;

class HttpExtractorComponent extends BaseComponent
{
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
        $client = new Client();
        $httpExtractor = new HttpExtractor($client);

        /** @var Config $config */
        $config = $this->getConfig();
        $uri = new Uri($config->getBaseUrl() . $config->getPath());
        $httpExtractor->extract($uri, $this->getDestination($uri));
    }

    private function getDestination(UriInterface $uri): string
    {
        return $this->getDataDir() . '/out/files/' . basename($uri->getPath());
    }
}
