---
name: laravel-sail-mcp-setup
description: Configure Laravel Boost and MCP servers to work correctly with Docker and Laravel Sail, including the stdio/TTY fix for Claude Code.
---

# Laravel Sail MCP Setup

## When to use this skill

Use this skill when configuring Laravel Boost or custom MCP servers in a Docker/Sail environment for use with Claude Code or other MCP clients.

## Required packages

```json
{
    "require": {
        "laravel/mcp": "^0.5.5",
        "laravel/boost": "^1.8.10"
    }
}
```

Minimum versions are critical. Older versions have MCP protocol incompatibilities with current Claude Code.

## The Sail stdio problem

Laravel Sail's script (`vendor/laravel/sail/bin/sail`) detects when stdin is not a TTY and adds the `-T` flag to `docker compose exec`. When Claude Code spawns an MCP server, stdin is a pipe (not a TTY), so Sail adds `-T` which breaks the persistent bidirectional stdin/stdout connection that MCP stdio protocol requires.

Additionally, Sail performs slow initialization checks (~4 seconds) that can cause Claude Code to timeout during the MCP handshake.

## Installing Boost

```bash
composer require laravel/boost --dev
php artisan boost:install
```

**Problem**: `boost:install` registers the MCP server in `~/.claude.json` with `"command": "php"`, which does NOT work when PHP runs inside Docker.

## Fix: Register Boost MCP with Docker

First, find your container name:

```bash
docker ps --format '{{.Names}}' | grep laravel
# Example: my-project-laravel.test-1
```

Then register the MCP server using `docker exec -i` directly:

```bash
claude mcp remove laravel-boost -s local
claude mcp add -s local -t stdio laravel-boost -- docker exec -i YOUR_CONTAINER_NAME php artisan boost:mcp
```

This writes to `~/.claude.json` (project-level config). The `-i` flag keeps stdin open without allocating a TTY, which is exactly what MCP stdio needs.

## Configuring custom MCP servers in .mcp.json

For your own MCP servers, `.mcp.json` at project root works with Sail:

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

Sail works for `mcp:start` in some environments. If it fails, use `docker exec -i` instead:

```json
{
    "mcpServers": {
        "voice-assistant": {
            "command": "docker",
            "args": [
                "exec", "-i",
                "YOUR_CONTAINER_NAME",
                "php", "artisan", "mcp:start", "voice-assistant"
            ]
        }
    }
}
```

## Claude Code MCP config hierarchy

Claude Code reads MCP servers from two places:

| File | Location | Registered by |
|------|----------|---------------|
| `.mcp.json` | Project root | Manual / boost:install |
| `~/.claude.json` | User home (per-project section) | `claude mcp add` / boost:install |

Both are loaded. If the same server name exists in both, `~/.claude.json` takes precedence. The `boost:install` command registers `laravel-boost` in `~/.claude.json` under the project path key.

## Updating packages

When updating, relax version constraints to allow minor jumps:

```bash
# This won't upgrade 0.2 -> 0.5 because ^0.2.1 blocks it:
composer update laravel/boost laravel/mcp

# This will:
composer require laravel/mcp:^0 laravel/boost:^1 --with-all-dependencies
```

In semver, `^0.x.y` only allows patch updates (0.x.y -> 0.x.z). To jump minor versions in 0.x, use `^0`.

## Troubleshooting

### "Failed to reconnect" in Claude Code
1. Check container is running: `docker ps | grep laravel`
2. Test MCP handshake manually:
   ```bash
   echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' | docker exec -i YOUR_CONTAINER_NAME php artisan boost:mcp
   ```
3. If no response or error, update packages
4. If valid JSON response, check `~/.claude.json` for wrong `"command": "php"` entries

### "Unsupported protocol version"
Update `laravel/mcp` to >= 0.5.5:
```bash
composer require laravel/mcp:^0 --with-all-dependencies
```

### Sail adds extra output to stdio
Use `docker exec -i` directly instead of Sail wrapper to get a clean stdio channel.

## Quick setup checklist

1. `composer require laravel/boost --dev laravel/mcp:^0`
2. `php artisan boost:install`
3. Find container: `docker ps --format '{{.Names}}' | grep laravel`
4. Fix Boost MCP: `claude mcp remove laravel-boost -s local && claude mcp add -s local -t stdio laravel-boost -- docker exec -i CONTAINER php artisan boost:mcp`
5. Restart Claude Code
6. Verify: `/mcp` should show `laravel-boost` connected
