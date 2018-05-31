<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Keboola\Component\UserException;
use Psr\Http\Message\UriInterface;
use function in_array;
use const CURLE_COULDNT_RESOLVE_HOST;

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
                (string) $httpSource
            ), 0, $e);
        } catch (ConnectException $e) {
            $userErrors = [
                CURLE_COULDNT_RESOLVE_HOST,
                CURLE_COULDNT_CONNECT,
                CURLE_OPERATION_TIMEOUTED,
                CURLE_SSL_CONNECT_ERROR,
                CURLE_GOT_NOTHING,
                CURLE_RECV_ERROR,
            ];
            $curlErrorNumber = $e->getHandlerContext()['errno'];
            if (!in_array($curlErrorNumber, $userErrors)) {
                throw $e;
            }
            $curlErrorMessage = $e->getHandlerContext()['error'];
            throw new UserException(sprintf(
                'Error requesting "%s": cURL error %s: %s',
                (string) $httpSource,
                $curlErrorNumber,
                $curlErrorMessage
            ), 0, $e);
        }
        // will throw exception for HTTP errors, no need to signal back
    }
}
