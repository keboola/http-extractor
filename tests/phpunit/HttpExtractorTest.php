<?php

declare(strict_types=1);

namespace Keboola\HttpExtractor\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\HttpExtractor\Client;
use Keboola\HttpExtractor\HttpExtractor;
use Keboola\Temp\Temp;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function sys_get_temp_dir;

class HttpExtractorTest extends TestCase
{
    /** @var mixed[] */
    private $history = [];

    /** @var TestHandler */
    private $testHandler;

    public function testExtractSavesResponseToFile(): void
    {
        $resource = new Uri('http://example.com/result.txt');
        $content = 'File contents';
        $mockedResponse = new Response(200, [], $content);
        $client = $this->getMockedExtractorClient([$mockedResponse]);
        $extractor = new HttpExtractor($client, []);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $extractor->extract($resource, $destination);

        $this->assertSame($content, file_get_contents($destination));
        $this->assertSame([], $this->testHandler->getRecords());
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

        $client = $this->getMockedExtractorClient($mockedResponses);
        $extractor = new HttpExtractor($client, ['maxRedirects' => 2]);
        $destination = tempnam(sys_get_temp_dir(), 'http_extractor');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Too many redirects requesting "http://example.com/result.txt": Will not follow more than 2 redirects'
        );

        $extractor->extract($resource, $destination);
    }

    private function getTestLogger(): Logger
    {
        $this->testHandler = new TestHandler();
        $logger = new Logger();
        $logger->setHandlers([$this->testHandler]);
        return $logger;
    }

    /**
     * @dataProvider provideUrlsAndExceptions
     * @param Response[] $mockedResponses
     */
    public function testHttpExceptions(
        array $mockedResponses,
        string $exceptionClass,
        string $exceptionMessagePart
    ): void {
        $client = $this->getMockedExtractorClient($mockedResponses);
        $extractor = new HttpExtractor($client, []);
        $temp = new Temp();

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessagePart);

        $extractor->extract(
            new Uri('http://example.com'),
            $temp->createTmpFile()->getPathname()
        );
    }

    private function getMockedExtractorClient(array $responses): Client
    {
        $this->history = [];
        $history = Middleware::history($this->history);

        $this->testHandler = new TestHandler();

        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        return new Client($this->getTestLogger(), ['handler' => $stack]);
    }

    /**
     * @return mixed[][]
     */
    public function provideUrlsAndExceptions(): array
    {
        return [
            'HTTP 404' => [
                array_fill(0, 5, new Response(404, [], '')),
                UserException::class,
                'Server returned HTTP 404 for "http://example.com"',
            ],
            'HTTP 401' => [
                array_fill(0, 5, new Response(401, [], '')),
                UserException::class,
                'Server returned HTTP 401 for "http://example.com"',
            ],
            'HTTP 500' => [
                array_fill(0, 10, new Response(500, [], '')),
                UserException::class,
                'Server returned HTTP 500 for "http://example.com"',
            ],
            'HTTP 503' => [
                array_fill(0, 10, new Response(503, [], '')),
                UserException::class,
                'Server returned HTTP 503 for "http://example.com"',
            ],
        ];
    }

    public function testThrowsUserExceptionForNonexistentHost(): void
    {
        // real client is used to test real behaviour
        $client = new \GuzzleHttp\Client();

        $extractor = new HttpExtractor($client, []);
        $temp = new Temp();

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Error requesting "http://domain.nonexistent/index.html": ' .
            'Guzzle error: cURL error 6: Could not resolve host: domain.nonexistent'
        );

        $extractor->extract(
            new Uri('http://domain.nonexistent/index.html'),
            $temp->createTmpFile()->getPathname()
        );
    }

    public function testThrowsUserExceptionForNonValidCert(): void
    {
        // real client is used to test real behaviour
        $client = new \GuzzleHttp\Client();

        $invalidHostResolve = [
            CURLOPT_RESOLVE => ['keboola.com:443:142.251.36.68'], // 142.251.36.68 = https://www.google.com
        ];
        $extractor = new HttpExtractor($client, ['curl' => $invalidHostResolve]);
        $temp = new Temp();

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Error requesting "https://keboola.com": ' .
            'cURL error 60: SSL: no alternative certificate subject name matches target host name \'keboola.com\''
        );

        $extractor->extract(
            new Uri('https://keboola.com'),
            $temp->createTmpFile()->getPathname()
        );
    }
}
