<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor;

use function file_get_contents;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;
use function tmpfile;

class HttpExtractorTest extends TestCase
{
    /** @var mixed[] */
    private $history = [];

    public function testExtract(): void
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
        $this->assertSame('http://example.com/result.txt', (string) $request->getUri());
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
