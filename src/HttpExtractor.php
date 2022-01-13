<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Keboola\Component\UserException;
use Keboola\HttpExtractor\Exception\EncodingException;
use Psr\Http\Message\UriInterface;
use function in_array;

class HttpExtractor
{
    /** @var Client */
    private $client;

    /** @var array */
    private $clientOptions;

    public function __construct(
        Client $client,
        array $clientOptions
    ) {
        $this->client = $client;
        $this->clientOptions = $clientOptions;
    }

    public function extract(UriInterface $httpSource, string $filesystemDestination): void
    {
        $options = $this->getRequestOptions();
        $options['sink'] = $filesystemDestination;
        try {
            $this->sendRequest($httpSource, $options);
        } catch (EncodingException $e) {
            // Try to download without content encoding.
            // Server send invalid Content-Encoding, eg. UTF-8, valid are: gzip, deflate, ...
            $options['decode_content'] = false;
            $this->sendRequest($httpSource, $options);
        }
    }

    private function sendRequest(UriInterface $httpSource, array $options): void
    {
        try {
            $this->client->get($httpSource, $options);
        } catch (ClientException|ServerException $e) {
            throw new UserException(sprintf(
                'Server returned HTTP %s for "%s"',
                $e->getCode(),
                (string) $httpSource
            ), 0, $e);
        } catch (TooManyRedirectsException $e) {
            throw new UserException(sprintf(
                'Too many redirects requesting "%s": %s',
                (string) $httpSource,
                $e->getMessage()
            ), 0, $e);
        } catch (RequestException $e) {
            if (strpos($e->getMessage(), 'Unrecognized content encoding type.') !== false) {
                throw new EncodingException($e->getMessage(), $e->getCode(), $e);
            }

            $userErrors = [
                CURLE_COULDNT_RESOLVE_HOST,
                CURLE_COULDNT_RESOLVE_PROXY,
                CURLE_COULDNT_CONNECT,
                CURLE_OPERATION_TIMEOUTED,
                CURLE_SSL_CONNECT_ERROR,
                CURLE_GOT_NOTHING,
                CURLE_RECV_ERROR,
                CURLE_SSL_CACERT,
            ];
            $context = $e->getHandlerContext();
            if (!isset($context['errno'])) {
                throw $e;
            }

            $curlErrorNumber = $context['errno'];
            if (!in_array($curlErrorNumber, $userErrors)) {
                throw $e;
            }
            $curlErrorMessage = $context['error'];
            throw new UserException(sprintf(
                'Error requesting "%s": cURL error %s: %s',
                (string) $httpSource,
                $curlErrorNumber,
                $curlErrorMessage
            ), 0, $e);
        } catch (GuzzleException $e) {
            throw new UserException(sprintf(
                'Error requesting "%s": Guzzle error: %s',
                (string) $httpSource,
                $e->getMessage()
            ), 0, $e);
        }
        // will throw exception for HTTP errors, no need to signal back
    }

    /**
     * @return mixed[]
     */
    private function getRequestOptions(): array
    {
        $requestOptions = [];
        if (isset($this->clientOptions['maxRedirects'])) {
            $requestOptions['allow_redirects'] = [
                'max' => $this->clientOptions['maxRedirects'],
            ];
        }
        return $requestOptions;
    }
}
