<?php

declare(strict_types=1);

namespace Tests\Services\Mvc;

use Architect\Services\Mvc\Http\Response;
use Architect\Services\Mvc\Http\Stream;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultResponse(): void
    {
        $response = new Response();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame('OK', $response->getReasonPhrase());
    }

    public function testCustomStatus(): void
    {
        $response = new Response(404);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testWithStatus(): void
    {
        $response = new Response();
        $new = $response->withStatus(301);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(301, $new->getStatusCode());
        $this->assertSame('Moved Permanently', $new->getReasonPhrase());
    }

    public function testWithStatusCustomReason(): void
    {
        $response = new Response();
        $new = $response->withStatus(200, 'Custom');
        $this->assertSame('Custom', $new->getReasonPhrase());
    }

    public function testWithProtocolVersion(): void
    {
        $response = new Response();
        $new = $response->withProtocolVersion('2.0');
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame('2.0', $new->getProtocolVersion());
    }

    public function testWithHeader(): void
    {
        $response = new Response();
        $new = $response->withHeader('X-Custom', 'value');
        $this->assertFalse($response->hasHeader('X-Custom'));
        $this->assertTrue($new->hasHeader('X-Custom'));
        $this->assertSame(['value'], $new->getHeader('X-Custom'));
    }

    public function testWithHeaderCaseInsensitive(): void
    {
        $response = new Response();
        $new = $response->withHeader('Content-Type', 'text/html');
        $this->assertTrue($new->hasHeader('content-type'));
        $this->assertSame(['text/html'], $new->getHeader('CONTENT-TYPE'));
    }

    public function testWithAddedHeader(): void
    {
        $response = new Response(200, ['X-Custom' => ['first']]);
        $new = $response->withAddedHeader('X-Custom', 'second');
        $this->assertSame(['first', 'second'], $new->getHeader('X-Custom'));
    }

    public function testWithoutHeader(): void
    {
        $response = new Response(200, ['X-Remove' => ['yes']]);
        $new = $response->withoutHeader('X-Remove');
        $this->assertTrue($response->hasHeader('X-Remove'));
        $this->assertFalse($new->hasHeader('X-Remove'));
    }

    public function testGetHeaderLine(): void
    {
        $response = new Response(200, ['Accept' => ['text/html', 'application/json']]);
        $this->assertSame('text/html, application/json', $response->getHeaderLine('Accept'));
    }

    public function testWithBody(): void
    {
        $body = new Stream('content');
        $response = new Response();
        $new = $response->withBody($body);
        $this->assertSame('content', $new->getBody()->getContents());
    }

    public function testGetBody(): void
    {
        $response = new Response(200, [], new Stream('hello'));
        $this->assertSame('hello', $response->getBody()->getContents());
    }

    public function testWithContent(): void
    {
        $response = new Response();
        $new = $response->withContent('<h1>Hello</h1>');
        $this->assertSame('<h1>Hello</h1>', $new->getContent());
    }

    public function testWithJson(): void
    {
        $data = ['key' => 'value', 'number' => 42];
        $response = new Response();
        $new = $response->withJson($data);

        $this->assertSame('json', $new->getType());
        $this->assertSame($data, $new->getJsonData());
        $this->assertSame(['application/json'], $new->getHeader('Content-Type'));
        $this->assertSame(json_encode($data), $new->getBody()->getContents());
    }

    public function testWithType(): void
    {
        $response = new Response();
        $this->assertSame('html', $response->getType());

        $new = $response->withType('json');
        $this->assertSame('html', $response->getType());
        $this->assertSame('json', $new->getType());
    }

    public function testHtmlFactory(): void
    {
        $response = Response::html('<p>Hello</p>');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<p>Hello</p>', $response->getContent());
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    public function testLocationFactory(): void
    {
        $response = Response::location('/login');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['/login'], $response->getHeader('Location'));
        $this->assertSame('redirect', $response->getType());
    }

    public function testLocationFactoryCustomStatus(): void
    {
        $response = Response::location('/new', 301);
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testImmutability(): void
    {
        $response = new Response();
        $withHeader = $response->withHeader('X-Test', 'value');
        $withStatus = $response->withStatus(404);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('X-Test'));
        $this->assertSame(404, $withStatus->getStatusCode());
        $this->assertTrue($withHeader->hasHeader('X-Test'));
    }

    public function testGetHeaders(): void
    {
        $response = new Response(200, [
            'Content-Type' => ['text/html'],
            'X-Custom' => ['value'],
        ]);
        $headers = $response->getHeaders();
        $this->assertCount(2, $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Custom', $headers);
    }

    public function testReasonPhraseStatuses(): void
    {
        $this->assertSame('Created', (new Response(201))->getReasonPhrase());
        $this->assertSame('No Content', (new Response(204))->getReasonPhrase());
        $this->assertSame('Bad Request', (new Response(400))->getReasonPhrase());
        $this->assertSame('Unauthorized', (new Response(401))->getReasonPhrase());
        $this->assertSame('Forbidden', (new Response(403))->getReasonPhrase());
        $this->assertSame('Internal Server Error', (new Response(500))->getReasonPhrase());
        $this->assertSame('Service Unavailable', (new Response(503))->getReasonPhrase());
    }
}
