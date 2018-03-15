<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Keboola\HttpExtractor\HttpExtractor;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function sys_get_temp_dir;

class HttpExtractorTest extends TestCase
{
    /** @var mixed[] */
    private $history = [];

    public function testExtractSavesResponseToFile(): void
    {
        $resource = new Uri('http://example.com/result.txt');
        $content = 'File contents';
        $mockedResponse = new Response(200, [], $content);
        $client = $this->getMockedGuzzle([$mockedResponse]);
        $extractor = new HttpExtractor($client);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $extractor->extract($resource, $destination);

        $this->assertSame($content, file_get_contents($destination));
        $historyItem = array_pop($this->history);
        /** @var Request $request */
        $request = $historyItem['request'];
        $this->assertSame('http://example.com/result.txt', (string)$request->getUri());
    }

    /**
     * @dataProvider provideUrlsAndExceptions
     */
    public function testHttpExceptions(
        Response $mockedResponse,
        string $exceptionClass,
        string $exceptionMessagePart
    ): void {
        $client = $this->getMockedGuzzle([$mockedResponse]);
        $extractor = new HttpExtractor($client);
        $temp = new Temp();

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessagePart);

        $extractor->extract(
            new Uri('http://example.com'),
            $temp->createTmpFile()->getPathname()
        );
    }

    private function getMockedGuzzle(array $responses): Client
    {
        $this->history = [];
        $history = Middleware::history($this->history);

        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        return new Client(['handler' => $stack]);
    }

    /**
     * @return mixed[][]
     */
    public function provideUrlsAndExceptions(): array
    {
        return [
            [
                new Response(404, [], ''),
                ClientException::class,
                'Client error: `GET http://example.com` resulted in a `404 Not Found` response',
            ],
            [
                new Response(401, [], ''),
                ClientException::class,
                'Client error: `GET http://example.com` resulted in a `401 Unauthorized` response',
            ],
            [
                new Response(500, [], ''),
                ServerException::class,
                'Server error: `GET http://example.com` resulted in a `500 Internal Server Error` response',
            ],
            [
                new Response(503, [], ''),
                ServerException::class,
                'Server error: `GET http://example.com` resulted in a `503 Service Unavailable` response',
            ],
        ];
    }

    public function testThrowsUserExceptionForNonexistentHost(): void
    {
        $client = new Client();
        $extractor = new HttpExtractor($client);
        $temp = new Temp();

        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage(
            'cURL error 6: Could not resolve host: domain.nonexistent ' .
            '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)'
        );

        $extractor->extract(
            new Uri('http://domain.nonexistent/index.html'),
            $temp->createTmpFile()->getPathname()
        );
    }
}
