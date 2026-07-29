<?php

declare(strict_types=1);

namespace Tests\Services\Mvc;

use Architect\Services\Mvc\Http\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function testCreateResponse(): void
    {
        $response = $this->factory->createResponse(201);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateResponseDefault(): void
    {
        $response = $this->factory->createResponse();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreateStream(): void
    {
        $stream = $this->factory->createStream('hello');
        $this->assertSame('hello', $stream->getContents());
    }

    public function testCreateJsonResponse(): void
    {
        $data = ['status' => 'ok'];
        $response = $this->factory->createJsonResponse($data, 200);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
        $this->assertSame(json_encode($data), $response->getBody()->getContents());
    }

    public function testCreateHtmlResponse(): void
    {
        $response = $this->factory->createHtmlResponse('<h1>Hello</h1>');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        $this->assertSame('<h1>Hello</h1>', $response->getBody()->getContents());
    }

    public function testCreateRedirectResponse(): void
    {
        $response = $this->factory->createRedirectResponse('/login');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['/login'], $response->getHeader('Location'));
    }

    public function testCreateErrorResponse(): void
    {
        $response = $this->factory->createErrorResponse(500, 'Server Error');
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Server Error', $response->getBody()->getContents());
    }

    public function testCreateErrorResponseEmpty(): void
    {
        $response = $this->factory->createErrorResponse(500);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testCreateNotFoundResponse(): void
    {
        $response = $this->factory->createNotFoundResponse();
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getBody()->getContents());
    }

    public function testCreateForbiddenResponse(): void
    {
        $response = $this->factory->createForbiddenResponse();
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getBody()->getContents());
    }

    public function testCreateServerErrorResponse(): void
    {
        $response = $this->factory->createServerErrorResponse();
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getBody()->getContents());
    }
}
