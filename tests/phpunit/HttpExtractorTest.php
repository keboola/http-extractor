<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Keboola\Component\UserException;
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
        $extractor = new HttpExtractor($client, []);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $extractor->extract($resource, $destination);

        $this->assertSame($content, file_get_contents($destination));
        $historyItem = array_pop($this->history);
        /** @var Request $request */
        $request = $historyItem['request'];
        $this->assertSame('http://example.com/result.txt', (string) $request->getUri());
    }

    public function testTooManyRedirects(): void
    {
        $resource = new Uri('http://example.com/result.txt');
        $content = 'File contents';
        $mockedResponses = [];
        for ($i = 0; $i <= 6; $i++) {
            $mockedResponses[] = new Response(301, ['Location' => 'http://other-url'], $content);
        }

        $client = $this->getMockedGuzzle($mockedResponses);
        $extractor = new HttpExtractor($client, ['max_redirects' => 2]);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Too many redirects requesting "http://example.com/result.txt": Will not follow more than 2 redirects'
        );

        $extractor->extract($resource, $destination);
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
        $extractor = new HttpExtractor($client, []);
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
                UserException::class,
                'Server returned HTTP 404 for "http://example.com"',
            ],
            [
                new Response(401, [], ''),
                UserException::class,
                'Server returned HTTP 401 for "http://example.com"',
            ],
            [
                new Response(500, [], ''),
                UserException::class,
                'Server returned HTTP 500 for "http://example.com"',
            ],
            [
                new Response(503, [], ''),
                UserException::class,
                'Server returned HTTP 503 for "http://example.com"',
            ],
        ];
    }

    public function testThrowsUserExceptionForNonexistentHost(): void
    {
        $client = new Client();
        $extractor = new HttpExtractor($client, []);
        $temp = new Temp();

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Error requesting "http://domain.nonexistent/index.html":' .
            ' cURL error 6: Could not resolve host: domain.nonexistent'
        );

        $extractor->extract(
            new Uri('http://domain.nonexistent/index.html'),
            $temp->createTmpFile()->getPathname()
        );
    }
}
