---
name: cloudflare-tunnel
description: Create temporary Cloudflare quick tunnels to expose the local application via a public HTTPS URL, without authentication or Cloudflare account.
---

# Cloudflare Quick Tunnel

## When to use this skill

Use this skill when the user asks to expose the local application via a public URL, create a tunnel, or share their dev environment externally.

## Prerequisites

- `cloudflared` must be installed (check with `which cloudflared`)
- No Cloudflare account or authentication needed for quick tunnels

## Creating a quick tunnel

```bash
nohup cloudflared tunnel --url http://ORIGIN_URL/ > /tmp/cloudflared.log 2>&1 &
sleep 10
cat /tmp/cloudflared.log | grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com'
```

- `ORIGIN_URL` is the local URL the app runs on (e.g., `dev.atomic-habits-manager.ai`, `localhost:80`)
- The tunnel generates a random `*.trycloudflare.com` HTTPS URL
- The tunnel runs in background via `nohup`
- Logs are written to `/tmp/cloudflared.log`
- Wait ~10 seconds for the tunnel to register before reading the URL

## Common origin URLs for this project

| URL | Description |
|-----|-------------|
| `http://dev.atomic-habits-manager.ai/` | Local dev domain (configured via hosts/DNS) |
| `http://localhost:80` | Direct Docker port |

## Managing tunnels

```bash

# Check if tunnel is running

pgrep cloudflared

# View tunnel logs

cat /tmp/cloudflared.log

# Stop the tunnel

pkill cloudflared
```

## Important notes

- Quick tunnels are temporary — the URL changes every time you restart
- No uptime guarantee (subject to Cloudflare terms)
- No authentication required (`cloudflared login` is NOT needed)
- For persistent custom domains, a Cloudflare account + named tunnel is required
- The ICMP proxy warning about ping_group_range is harmless and can be ignored