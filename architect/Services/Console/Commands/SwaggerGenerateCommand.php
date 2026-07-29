<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Generate OpenAPI specification from annotations.
 */
class SwaggerGenerateCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'swagger:generate';
    protected string $description = 'Generate OpenAPI specification from PHPDoc annotations';

    public function getArguments(): array
    {
        return [
            ['output', 'Output file path (default: docs/openapi.json)', false],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--scan', 'Comma-separated paths to scan (default: app/modules/api)', 's'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $outputPath = $arguments['output'] ?? getcwd() . '/htdocs/docs/openapi.json';
        $scanPaths = $options['scan'] ?? 'app/modules/api';

        $paths = array_map('trim', explode(',', $scanPaths));

        // Try using swagger-php library if installed
        if (class_exists('OpenApi\Generator')) {
            return $this->generateWithLibrary($paths, $outputPath);
        }

        // Fallback: generate from static annotations manually
        return $this->generateStatic($paths, $outputPath);
    }

    private function generateWithLibrary(array $paths, string $outputPath): int
    {
        $this->output->write('<info>Using swagger-php library...</info>');

        $openapi = \OpenApi\Generator::scan($paths);
        $json = $openapi->toJson();

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($outputPath, $json);

        $this->output->write("<info>OpenAPI spec generated: {$outputPath}</info>");
        return 0;
    }

    private function generateStatic(array $paths, string $outputPath): int
    {
        $this->output->write('<comment>swagger-php not installed. Generating from known endpoints...</comment>');

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Architect Framework API',
                'version' => '2.0.0',
                'description' => 'REST API for Architect RED 2',
            ],
            'servers' => [
                ['url' => '/', 'description' => 'Основной сервер'],
            ],
            'paths' => [
                '/api' => [
                    'get' => [
                        'summary' => 'Статус API',
                        'tags' => ['System'],
                        'responses' => [
                            '200' => [
                                'description' => 'Успешный ответ',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string', 'example' => 'ok'],
                                                'message' => ['type' => 'string'],
                                                'endpoints' => ['type' => 'object'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/users' => [
                    'get' => [
                        'summary' => 'Список пользователей',
                        'tags' => ['Users'],
                        'responses' => [
                            '200' => [
                                'description' => 'Массив пользователей',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'users' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/User'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/user/{id}' => [
                    'get' => [
                        'summary' => 'Пользователь по ID',
                        'tags' => ['Users'],
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'ID пользователя',
                                'schema' => ['type' => 'integer', 'example' => 1],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Данные пользователя',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/User'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'Пользователь не найден'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'Иван'],
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'ivan@example.com'],
                        ],
                    ],
                ],
            ],
        ];

        $json = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($outputPath, $json);

        $this->output->write("<info>OpenAPI spec generated: {$outputPath}</info>");
        return 0;
    }
}
