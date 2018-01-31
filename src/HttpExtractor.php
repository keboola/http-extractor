<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use function dirname;
use GuzzleHttp\Client;
use Psr\Http\Message\UriInterface;

class HttpExtractor
{
    /** @var Client */
    private $client;

    public function __construct(
        Client $client
    ) {
        $this->client = $client;
    }

    public function extract(UriInterface $httpSource, string $filesystemDestination): void
    {
        $this->client->get($httpSource, ['sink' => $filesystemDestination]);
        // will throw exception for HTTP errors, no need to signal back
    }
}
