<?php

declare(strict_types=1);

namespace Tests\Services\Api;

use PHPUnit\Framework\TestCase;

class SwaggerGenerateTest extends TestCase
{
    private string $docsDir;
    private string $openapiPath;

    protected function setUp(): void
    {
        $this->docsDir = getcwd() . '/htdocs/docs';
        $this->openapiPath = $this->docsDir . '/openapi.json';
    }

    public function testOpenapiJsonExists(): void
    {
        $this->assertFileExists($this->openapiPath);
    }

    public function testOpenapiJsonIsValidJson(): void
    {
        $content = file_get_contents($this->openapiPath);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testOpenapiJsonHasRequiredFields(): void
    {
        $data = json_decode(file_get_contents($this->openapiPath), true);
        $this->assertArrayHasKey('openapi', $data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('paths', $data);
        $this->assertSame('3.0.3', $data['openapi']);
    }

    public function testOpenapiJsonInfoSection(): void
    {
        $data = json_decode(file_get_contents($this->openapiPath), true);
        $this->assertSame('Architect Framework API', $data['info']['title']);
        $this->assertSame('2.0.0', $data['info']['version']);
    }

    public function testOpenapiJsonHasApiEndpoints(): void
    {
        $data = json_decode(file_get_contents($this->openapiPath), true);
        $this->assertArrayHasKey('/api', $data['paths']);
        $this->assertArrayHasKey('/api/users', $data['paths']);
        $this->assertArrayHasKey('/api/user/{id}', $data['paths']);
    }

    public function testOpenapiJsonHasComponents(): void
    {
        $data = json_decode(file_get_contents($this->openapiPath), true);
        $this->assertArrayHasKey('components', $data);
        $this->assertArrayHasKey('schemas', $data['components']);
        $this->assertArrayHasKey('User', $data['components']['schemas']);
    }

    public function testSwaggerUiPageExists(): void
    {
        $swaggerUiPath = getcwd() . '/htdocs/swagger/index.php';
        $this->assertFileExists($swaggerUiPath);
    }

    public function testSwaggerUiLoadsSwaggerBundle(): void
    {
        $content = file_get_contents(getcwd() . '/htdocs/swagger/index.php');
        $this->assertStringContainsString('swagger-ui', $content);
        $this->assertStringContainsString('SwaggerUIBundle', $content);
        $this->assertStringContainsString('openapi.json', $content);
    }

    public function testApiControllerHasOpenApiAnnotations(): void
    {
        $content = file_get_contents(getcwd() . '/app/modules/api/controller.php');
        $this->assertStringContainsString('@OA\Info', $content);
        $this->assertStringContainsString('@OA\Get', $content);
        $this->assertStringContainsString('@OA\Schema', $content);
    }

    public function testSwaggerGenerateCommandExists(): void
    {
        $commandPath = getcwd() . '/architect/Services/Console/Commands/SwaggerGenerateCommand.php';
        $this->assertFileExists($commandPath);
        $content = file_get_contents($commandPath);
        $this->assertStringContainsString('swagger:generate', $content);
        $this->assertStringContainsString('OpenAPI specification', $content);
    }
}
