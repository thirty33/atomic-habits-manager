---
name: mcp-server-development
description: Build MCP (Model Context Protocol) servers and tools with laravel/mcp to expose functionality to external AI clients like Claude Code.
---

# MCP Server Development

## When to use this skill

Use this skill when creating MCP servers and tools that expose your application's functionality to external AI clients (Claude Code, Cursor, etc.) via the Model Context Protocol.

## Required packages

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/mcp": "^0.5.5"
    }
}
```

## Installation

```bash
composer require laravel/mcp
```

The `routes/ai.php` file is automatically registered by the package.

## File structure

```
app/Mcp/
├── Servers/
│   └── VoiceAssistant.php       # Server definitions
└── Tools/
    └── ProcessStoredAudioTool.php # Tool implementations
routes/
└── ai.php                       # Server registration
.mcp.json                        # Claude Code MCP config
```

## Registering a server

In `routes/ai.php`:

```php
<?php

use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\VoiceAssistant;

// Local stdio server (for Claude Code CLI)
Mcp::local('voice-assistant', VoiceAssistant::class);

// Web HTTP streaming server (for browser-based clients)
// Mcp::web('my-server', MyServer::class);
```

- `Mcp::local()`: stdio transport, used by Claude Code CLI via `artisan mcp:start`
- `Mcp::web()`: HTTP streaming transport, used by browser clients

## Creating a Server

```php
<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ProcessStoredAudioTool;
use Laravel\Mcp\Server;

class VoiceAssistant extends Server
{
    protected string $name = 'Voice Assistant';

    protected string $version = '0.0.1';

    protected string $instructions = <<<'MARKDOWN'
    This server provides voice input capabilities.

    Available tools:
    - ProcessStoredAudioTool: Transcribes stored audio
    MARKDOWN;

    /** @var array<int, class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        ProcessStoredAudioTool::class,
    ];
}
```

Key points:
- Extend `Laravel\Mcp\Server`
- `$name`: Display name for the MCP client
- `$version`: Server version string
- `$instructions`: Markdown instructions the AI reads to understand the server's purpose
- `$tools`: Array of Tool class names (resolved via container, supports dependency injection)

## Creating an MCP Tool

```php
<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ProcessStoredAudioTool extends Tool
{
    protected string $description = 'Transcribes stored audio using Whisper.';

    public function __construct(
        private readonly WhisperService $whisperService
    ) {}

    public function handle(Request $request): Response
    {
        // Access parameters via $request['param_name']
        $result = $this->whisperService->transcribe($audioPath);

        // Return text response
        return Response::text("Transcription: {$result}");

        // Or return error
        // return Response::error('Something went wrong.');
    }

    public function schema(JsonSchema $schema): array
    {
        // Return empty array if no parameters
        return [];

        // Or define parameters:
        // return [
        //     'query' => $schema->string()->description('Search query.')->required(),
        //     'limit' => $schema->integer()->description('Max results.'),
        // ];
    }
}
```

Key points:
- Extend `Laravel\Mcp\Server\Tool`
- `#[IsReadOnly]`: Annotation marking the tool as read-only (no side effects)
- `$description`: What the tool does (the external AI reads this)
- Constructor injection is supported (tools are resolved via Laravel container)
- `handle()` returns `Response::text()` or `Response::error()`
- `schema()` uses `Illuminate\JsonSchema\JsonSchema` (NOT `Illuminate\Contracts\...` like laravel/ai)

## Configuring .mcp.json for Claude Code

```json
{
    "mcpServers": {
        "voice-assistant": {
            "command": "./vendor/bin/sail",
            "args": ["artisan", "mcp:start", "voice-assistant"]
        }
    }
}
```

The server name in `"mcp:start"` must match the name in `Mcp::local('voice-assistant', ...)`.

## MCP Tools vs AI Tools

| Aspect | MCP Tool (laravel/mcp) | AI Tool (laravel/ai) |
|--------|----------------------|---------------------|
| **Direction** | Exposed to external clients | Used internally by your app |
| **Who calls it** | Claude Code, Cursor, etc. | Your Agent via LLM provider |
| **Extends/Implements** | `Laravel\Mcp\Server\Tool` | `Laravel\Ai\Contracts\Tool` |
| **Response type** | `Response::text()` / `Response::error()` | `string` / `Stringable` |
| **Schema import** | `Illuminate\JsonSchema\JsonSchema` | `Illuminate\Contracts\JsonSchema\JsonSchema` |
| **Transport** | stdio or HTTP streaming | Direct PHP execution |
| **Use case** | Let AI assistants interact with your app | Let your app use AI with function calling |

They are completely separate systems. You cannot share tool definitions between them.

## Testing

Use `search-docs` tool to find MCP testing patterns. MCP servers are testable using Laravel's testing utilities.

## Important notes

- Do NOT run `php artisan mcp:start` manually in development; it hangs waiting for JSON-RPC input
- The `mcp:start` command is meant to be invoked by MCP clients (Claude Code) via stdio
- For local HTTPS issues with Node-based clients, switch to `http://` during development
