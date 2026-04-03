<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * MCP Server Command
 *
 * Starts an MCP (Model Context Protocol) server for Gemini CLI integration.
 * This allows Gemini CLI to interact with your Laravel project context.
 */
class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start MCP server for Gemini CLI integration';

    public function handle(): int
    {
        // MCP uses stdin/stdout for communication
        $this->info('MCP Server started. Waiting for requests...');

        while (true) {
            $line = fgets(STDIN);
            if ($line === false) {
                break;
            }

            $request = json_decode(trim($line), true);
            if (!$request) {
                continue;
            }

            $response = $this->processRequest($request);
            echo json_encode($response) . "\n";
            flush();
        }

        return self::SUCCESS;
    }

    private function processRequest(array $request): array
    {
        $method = $request['method'] ?? '';

        return match ($method) {
            'initialize' => [
                'jsonrpc' => '2.0',
                'id' => $request['id'],
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'resources' => ['listChanged' => true],
                        'tools' => ['listChanged' => true],
                    ],
                    'serverInfo' => [
                        'name' => 'hushstack-laravel-mcp',
                        'version' => '1.0.0',
                    ],
                ],
            ],
            'resources/list' => [
                'jsonrpc' => '2.0',
                'id' => $request['id'],
                'result' => [
                    'resources' => [
                        [
                            'uri' => 'file://config/app.php',
                            'mimeType' => 'text/x-php',
                            'name' => 'App Configuration',
                        ],
                        [
                            'uri' => 'file://routes/api.php',
                            'mimeType' => 'text/x-php',
                            'name' => 'API Routes',
                        ],
                        [
                            'uri' => 'file://database/migrations',
                            'mimeType' => 'text/x-php',
                            'name' => 'Database Migrations',
                        ],
                    ],
                ],
            ],
            'tools/list' => [
                'jsonrpc' => '2.0',
                'id' => $request['id'],
                'result' => [
                    'tools' => [
                        [
                            'name' => 'run_artisan_command',
                            'description' => 'Run any Laravel Artisan command',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'command' => [
                                        'type' => 'string',
                                        'description' => 'The artisan command to run',
                                    ],
                                ],
                                'required' => ['command'],
                            ],
                        ],
                        [
                            'name' => 'get_project_structure',
                            'description' => 'Get the Laravel project directory structure',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [],
                            ],
                        ],
                        [
                            'name' => 'read_file',
                            'description' => 'Read a file from the project',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'path' => [
                                        'type' => 'string',
                                        'description' => 'Relative path to the file',
                                    ],
                                ],
                                'required' => ['path'],
                            ],
                        ],
                    ],
                ],
            ],
            'tools/call' => $this->handleToolCall($request),
            default => [
                'jsonrpc' => '2.0',
                'id' => $request['id'],
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found',
                ],
            ],
        };
    }

    private function handleToolCall(array $request): array
    {
        $params = $request['params'] ?? [];
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        $result = match ($name) {
            'run_artisan_command' => $this->runArtisanCommand($args['command'] ?? ''),
            'get_project_structure' => $this->getProjectStructure(),
            'read_file' => $this->readFile($args['path'] ?? ''),
            default => ['error' => 'Unknown tool'],
        };

        return [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT),
                    ],
                ],
            ],
        ];
    }

    private function runArtisanCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;

        exec('cd ' . base_path() . ' && php artisan ' . $command . ' 2>&1', $output, $returnCode);

        return [
            'command' => $command,
            'output' => implode("\n", $output),
            'status' => $returnCode === 0 ? 'success' : 'error',
        ];
    }

    private function getProjectStructure(): array
    {
        $structure = [];
        $dirs = ['app', 'routes', 'database', 'config', 'resources', 'tests'];

        foreach ($dirs as $dir) {
            $path = base_path($dir);
            if (is_dir($path)) {
                $structure[$dir] = $this->scanDirectory($path, 2);
            }
        }

        return $structure;
    }

    private function scanDirectory(string $path, int $depth): array
    {
        if ($depth <= 0) {
            return ['...'];
        }

        $result = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $result[$item . '/'] = $this->scanDirectory($fullPath, $depth - 1);
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function readFile(string $path): array
    {
        $fullPath = base_path($path);

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return ['error' => 'File not found or not readable'];
        }

        // Security: Limit file size
        if (filesize($fullPath) > 1024 * 1024) {
            return ['error' => 'File too large (>1MB)'];
        }

        $content = file_get_contents($fullPath);

        return [
            'path' => $path,
            'size' => strlen($content),
            'content' => $content,
        ];
    }
}
