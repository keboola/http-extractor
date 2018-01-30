<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use function basename;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class HttpExtractorApplication
{
    /** @var Config */
    private $config;

    /** @var HttpExtractor */
    private $httpExtractor;

    /** @var string */
    private $dataDir;

    public function __construct(
        Config $config,
        HttpExtractor $httpExtractor,
        string $dataDir
    ) {
        $this->config = $config;
        $this->httpExtractor = $httpExtractor;
        $this->dataDir = $dataDir;
    }

    public function extract(): void
    {
        $uri = new Uri($this->config->getDownloadUrlBase() . $this->config->getDownloadUrlPath());
        $this->httpExtractor->extract($uri, $this->getDestination($uri));
    }

    private function getDestination(UriInterface $uri): string
    {
        $saveAs = $this->config->getSaveAs();
        if ($saveAs === null) {
            $saveAs = basename($uri->getPath());
        }
        return $this->dataDir . 'out/files/' .  $saveAs;
    }
}
