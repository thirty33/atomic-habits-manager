---
name: laravel-ai-development
description: Build AI agents and tools with laravel/ai SDK, including tool creation, agent configuration, and provider setup (DeepSeek, OpenAI, etc.).
---

# Laravel AI Development

## When to use this skill

Use this skill when creating AI agents, tools, or any feature that communicates with LLM providers (DeepSeek, OpenAI, Anthropic, etc.) using the `laravel/ai` SDK.

## Required packages

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.50",
        "laravel/ai": "^0.1.3"
    }
}
```

`prism-php/prism` is installed automatically as a dependency of `laravel/ai`.

## Installation

```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

This publishes `config/ai.php` where you configure providers and API keys.

## Configuration

### config/ai.php

Set the default provider:

```php
'default' => env('AI_PROVIDER', 'deepseek'),
```

Each provider reads its API key from `.env`:

```env
DEEPSEEK_API_KEY=sk-your-key
OPENAI_API_KEY=sk-your-key
ANTHROPIC_API_KEY=sk-your-key
```

## File structure

```
app/Ai/
├── Agents/
│   └── ChatAgent.php      # Agent classes

└── Tools/
    └── GreetTool.php       # Tool classes

```

Create these directories manually or use artisan:

```bash
php artisan make:tool ToolName
php artisan make:agent AgentName
```

## Creating a Tool

A Tool implements `Laravel\Ai\Contracts\Tool` with 3 methods:

```php
<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GreetTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Generates a personalized greeting for a given name.';
    }

    public function handle(Request $request): Stringable|string
    {
        $name = $request['name'];

        return "Hello {$name}! Greetings from Laravel AI.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the person to greet.')->required(),
        ];
    }
}
```

Key points:
- `description()`: What the tool does (the LLM reads this to decide when to use it)
- `schema()`: Input parameters using `JsonSchema` builder (`->string()`, `->integer()`, `->boolean()`, `->required()`)
- `handle()`: Receives a `Request` (array-accessible), returns a string response
- The `JsonSchema` import MUST be `Illuminate\Contracts\JsonSchema\JsonSchema` (requires Laravel >= 12.50)

## Creating an Agent

An Agent implements `Agent` and optionally `HasTools`:

```php
<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GreetTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class ChatAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a friendly assistant. Use the available tools when the user asks.';
    }

    public function tools(): iterable
    {
        return [
            new GreetTool,
        ];
    }
}
```

Key points:
- `Promptable` trait provides the `->prompt()` method
- `instructions()`: System prompt for the LLM
- `tools()`: Returns an array of Tool instances
- `HasTools` interface is required only if the agent uses tools

## Using an Agent

### From a Command

```php
<?php

namespace App\Console\Commands;

use App\Ai\Agents\ChatAgent;
use Illuminate\Console\Command;

class GreetCommand extends Command
{
    protected $signature = 'greet {name}';
    protected $description = 'Greet someone using ChatAgent';

    public function handle(): void
    {
        $name = $this->argument('name');

        $response = (new ChatAgent)->prompt(
            "Greet {$name}",
            provider: 'deepseek',
            model: 'deepseek-chat',
        );

        $this->info("Response: {$response}");
    }
}
```

### Using the agent() helper (without a class)

```php
use function Laravel\Ai\agent;

$response = agent('You are a helpful assistant.')
    ->tools([new GreetTool])
    ->prompt('Greet Joel', provider: 'deepseek', model: 'deepseek-chat');
```

## Provider models

| Provider | Model |
|----------|-------|
| deepseek | deepseek-chat |
| openai | gpt-4o |
| anthropic | claude-sonnet-4-5-20250929 |

## Important notes

- `laravel/ai` is the CLIENT side: your app calls an external LLM API
- Tools defined here are INTERNAL: the LLM decides when to call them, Laravel executes them locally
- This is different from `laravel/mcp` tools which are EXPOSED to external AI clients
- Laravel >= 12.50 is required for the `JsonSchema` contract interface