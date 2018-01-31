<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Keboola\HttpExtractor\HttpExtractor;
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

    public function testExtractThrowsExceptionFor404Response(): void
    {
        $resource = new Uri('http://example.com/result.txt');
        $content = 'File contents';
        $mockedResponse = new Response(404, [], $content);
        $client = $this->getMockedGuzzle([$mockedResponse]);
        $extractor = new HttpExtractor($client);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            'Client error: `GET http://example.com/result.txt` resulted in a `404 Not Found` response'
        );

        $extractor->extract($resource, $destination);
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
}
