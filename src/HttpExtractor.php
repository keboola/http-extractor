<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Keboola\Component\UserException;
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
        try {
            $this->client->get($httpSource, ['sink' => $filesystemDestination]);
        } catch (ClientException|ServerException $e) {
            throw new UserException(sprintf(
                'Server returned HTTP %s for "%s"',
                $e->getCode(),
                (string)$httpSource
            ), 0, $e);
        }
        // will throw exception for HTTP errors, no need to signal back
    }
}
