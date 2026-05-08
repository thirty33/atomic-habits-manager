---
name: queue-worker-zombie
description: Diagnose and kill stale Laravel queue workers that hold an in-memory snapshot of pre-refactor code. Triggers when jobs run against the same queue produce inconsistent results, when fresh code changes don't take effect for queued jobs, or when log lines that should appear (Logger calls inside pipes) are missing for a subset of jobs.
activators:
  - worker zombie
  - queue zombie
  - queue:work stale
  - cola atascada
  - workers desactualizados
  - jobs con codigo viejo
  - listener viejo corriendo
  - queue worker memory cache
  - pid worker zombie
---

# Queue Worker Zombie — Diagnose & Fix

## Symptom

A subset of queue jobs behaves as if the code was reverted to a previous state:

- Pipeline pipes don't emit logs they're supposed to emit (the new constructor-injected `Logger` never fires).
- Listeners that were unsubscribed in the service provider still execute (event handler X runs even though `$subscriptions->register(Event::class, X::class)` is commented out).
- Errors mention method names that **don't exist in the current source** but existed in a previous version (e.g. `Call to undefined method RecurrenceType::label() at HabitListStrategy.php:67` when line 67 of the current file is unrelated to `label()`).
- The same job, retried, sometimes succeeds and sometimes fails — depending on which worker picked it up.

## Root Cause

`php artisan queue:work` (the long-running variant, **without** `--once`) loads the application bootstrap **once at startup** and reuses it for every job. PHP doesn't re-read source files between jobs. If the worker started before a refactor (renamed a service, changed a service provider binding, edited a strategy), it keeps executing the **pre-refactor code** in memory until the process is killed.

If two workers consume the same queue — one started fresh, one stale — jobs are randomly assigned. The stale worker re-introduces deleted bugs and skips new behavior.

`queue:listen` is immune: it spawns a fresh `queue:work --once` per job, so each job sees current source.

## Diagnose

```bash
./vendor/bin/sail exec -T laravel.test ps -ef | grep -E "queue:work|queue:listen" | grep -v grep
```

Look for:

- **Long-running `queue:work` (no `--once`)** with a START time **earlier** than your most recent code change → suspect.
- A second worker on the same queue (`--queue=name` flag identical) → confirms parallel consumption.
- Multiple `queue:work --once` from `queue:listen` → fine, those refresh per job.

Cross-check the worker's start time against `git log` of the suspected file. If the worker started before the file's last edit, it has stale code.

## Fix

```bash
./vendor/bin/sail exec -T laravel.test bash -c "kill -9 <PID>"
```

Use `-9` (SIGKILL) — the worker may not respond to SIGTERM if it's mid-job. Verify:

```bash
./vendor/bin/sail exec -T laravel.test ps -ef | grep "queue:work" | grep -v "queue:work --once" | grep -v grep
```

Should be empty (only `--once` workers remain — those are spawned by `queue:listen` and refresh per job).

## Prevent

- **Production:** use Supervisor with `autorestart=true` and trigger restart on every deploy:
  ```bash
  php artisan queue:restart       # signals all workers to exit gracefully after current job
  sudo supervisorctl restart all  # then Supervisor relaunches them with fresh code
  ```
- **Development:** prefer `queue:listen` (spawns `--once` per job) over `queue:work` for active development. If you must use `queue:work`, kill and restart it after every refactor that touches queued code paths (jobs, listeners, services injected into them).
- **`composer run dev`:** uses `queue:listen` already (good). But if you ever ran `queue:work` manually in another terminal and forgot to kill it before restarting `composer run dev`, it survives — the `--kill-others` flag of concurrently only kills processes inside the same `concurrently` invocation.

## Related signals

- `Log::info` from inside a pipe doesn't reach `storage/logs/laravel.log` for some jobs but does for others → suspect the silent jobs are landing on a stale worker that doesn't have the `Log::info` line yet (you added it after worker boot).
- A removed file path appears in a stack trace → its class is still loaded in the worker's autoloader cache.
- `domain_event_outbox` shows `completed_at` set, but the listener side-effect (DB write, broadcast) didn't happen → stale worker's listener subscription map fired the wrong handler (or no handler).

## Quick health check

After killing the suspect, re-run the failing flow once and confirm:

1. Logs you expect now appear in `storage/logs/laravel.log`.
2. Errors that mentioned non-existent methods or removed classes are gone.
3. Behavior matches code on disk.

If any of those still fail, there's another stale worker somewhere — re-run the `ps -ef | grep queue:work` diagnose step.
