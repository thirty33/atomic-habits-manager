---
name: software-architecture
description: Architecture patterns for this project — Actions (create/update/delete), Repositories (queries), Services with Contracts for multi-client module operations, AI Agents and Tools, and Backoffice (Controllers + ViewModels + Vue bridge).
---

## When to use this skill

Activate this skill whenever:
- Creating or modifying an Action (create, update, or delete operation on a model)
- Creating or modifying a Repository (read queries)
- Building a Service that must be callable from multiple clients (Controller, Command, Job, MCP Tool)
- Creating or modifying a Backoffice Controller, ViewModel, or route
- Creating or modifying an AI Agent or Tool

---

## 1. Actions — Write Operations

Actions are single-responsibility classes that perform **one write operation** (create, update, or delete) on the database.

### Location
```
app/Actions/{Module}/CreateSomethingAction.php
app/Actions/{Module}/UpdateSomethingAction.php
app/Actions/{Module}/DeleteSomethingAction.php
```

### Contracts
All actions implement one of these interfaces:

```
app/Actions/Contracts/CreateAction.php  → public static function execute(array $data = []): Model;
app/Actions/Contracts/UpdateAction.php  → public static function execute(int $id, array $data = []): void;
app/Actions/Contracts/DeleteAction.php  → public static function execute(int $id): void;
```

### Rules
- The class is **`final`**
- It implements `CreateAction`, `UpdateAction`, or `DeleteAction`
- **Always use `data_get($data, 'key')`** to extract parameters — never direct array access `$data['key']`
- For `CreateAction`, the return type is the concrete Model (not the `Model` base class)
- When the operation spans multiple models, wrap in `DB::transaction()`
- No constructor dependencies — all data comes through `$data`
- AI-specific Actions (e.g., `CreateAssistantMessageAction`) that are closely tied to a single flow may forgo the contracts when their signature doesn't fit `array $data` (e.g., strongly-typed parameters). Keep the `final` class rule regardless.

### Example — Create
```php
// app/Actions/Categories/CreateCategoryAction.php
final class CreateCategoryAction implements CreateAction
{
    public static function execute(array $data = []): Category
    {
        return Category::create([
            'name'        => data_get($data, 'name'),
            'description' => data_get($data, 'description'),
            'is_active'   => data_get($data, 'is_active', false),
            'created_at'  => data_get($data, 'created_at', now()),
        ]);
    }
}
```

### Example — Create with transaction (multiple models)
```php
final class CreateHabitWithScheduleAction implements CreateAction
{
    public static function execute(array $data = []): Habit
    {
        return DB::transaction(function () use ($data) {
            $habit = Habit::create([
                'name'       => data_get($data, 'name'),
                'user_id'    => data_get($data, 'user_id'),
                'is_active'  => data_get($data, 'is_active', true),
            ]);

            if ($scheduleData = data_get($data, 'schedule')) {
                $habit->schedules()->create($scheduleData);
            }

            return $habit;
        });
    }
}
```

### Example — Update
```php
// app/Actions/Habits/UpdateHabitAction.php
final class UpdateHabitAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        Habit::findOrFail($id)->update([
            'name'        => data_get($data, 'name'),
            'description' => data_get($data, 'description'),
            'is_active'   => data_get($data, 'is_active'),
        ]);
    }
}
```

### Example — Delete
```php
// app/Actions/Habits/DeleteHabitAction.php
final class DeleteHabitAction implements DeleteAction
{
    public static function execute(int $id): void
    {
        Habit::findOrFail($id)->delete();
    }
}
```

### How to call an Action
```php
// From anywhere: Controller, Command, Job, MCP Tool
$habit = CreateHabitAction::execute($request->validated());

UpdateHabitAction::execute($id, $request->validated());

DeleteHabitAction::execute($id);
```

---

## 2. Repositories — Read Queries

Repositories contain **only read operations** (SELECT queries). They have no create/update/delete logic — that belongs to Actions.

### Location
```
app/Repositories/{Model}Repository.php
```

### Rules
- Regular class — no interface, no `final`
- **Only read queries** — no `create()`, `update()`, `delete()`, or `save()` calls
- Method names describe **what** they return: `findActiveByUserId()`, `getPendingByRole()`, `getScheduledToday()`
- Return types are always typed: `?Model`, `Collection`, `bool`, etc.
- Use `Model::query()` as the starting point for queries
- Eager load relationships inside the repository to prevent N+1
- Injected via constructor in Services — never instantiated directly with `new`

### Example
```php
// app/Repositories/ConversationRepository.php
class ConversationRepository
{
    public function findActiveByUserId(int $userId): ?Conversation
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->where('status', ConversationStatus::Active)
            ->latest('conversation_id')
            ->first();
    }

    public function getPendingAssistantMessages(): Collection
    {
        return Message::query()
            ->where('role', MessageRole::Assistant)
            ->where('status', MessageStatus::Pending)
            ->with('conversation')
            ->get();
    }
}
```

---

## 3. Services with Contracts — Multi-Client Module Operations

When a module's logic must be callable from multiple clients (Controller, Artisan Command, MCP Tool, Job, etc.), the entry point is a **Service** backed by a **Contract (Interface)**.

### Directory structure
```
app/Services/{Module}/
    Contracts/
        {Module}ServiceInterface.php    ← contract the service implements
    {Module}Service.php                 ← main orchestrating service
```

### Rules for the Service
- Injected with Repositories and Actions via **constructor property promotion**
- Orchestrates the flow: calls repositories to read, calls actions to write
- Never contains raw Eloquent queries — delegates to Repositories
- Never contains direct `Model::create()` calls — delegates to Actions
- Registered in a ServiceProvider when the interface must be bound in the container

### Example — Service structure
```php
// app/Services/AtomicIA/AtomicIAService.php
class AtomicIAService
{
    public function __construct(
        private string $provider,
        private string $model,
    ) {}

    public function reply(Conversation $conversation, string $message): string
    {
        return (new AtomicIAAgent($conversation))->prompt(
            $message,
            provider: $this->provider,
            model: $this->model,
        );
    }
}
```

### Registration in ServiceProvider
```php
// app/Providers/AtomicIAServiceProvider.php
class AtomicIAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AtomicIAService::class, function () {
            return new AtomicIAService(
                provider: config('ai.default'),
                model: config('ai.model'),
            );
        });
    }

    public function boot(): void
    {
        Message::observe(MessageObserver::class);
    }
}
```

### How multiple clients call the same service
```php
// From an Artisan Command
class ProcessPendingMessagesCommand extends Command
{
    public function __construct(private AtomicIAService $service) { parent::__construct(); }

    public function handle(): int
    {
        // ...
        return self::SUCCESS;
    }
}

// From a Job
class ProcessConversationJob implements ShouldQueue
{
    public function handle(AtomicIAService $service): void
    {
        $response = $service->reply($this->conversation, $lastMessage->body);
        CreateAssistantMessageAction::execute($this->conversation, $response);
    }
}

// From an MCP Tool
class ReplyToConversationTool
{
    public function __construct(private AtomicIAService $service) {}

    public function handle(Request $request): string
    {
        return $this->service->reply(
            Conversation::findOrFail($request['conversation_id']),
            $request['message'],
        );
    }
}
```

---

## 4. Enums — Never Hardcode Status or State Values

**Rule**: Any field representing a state, status, category, type, or fixed set of values **must be backed by an enum**. Hardcoded strings like `'active'`, `'sent'`, `'daily'` scattered in code are forbidden.

### Location
```
app/Enums/{ConceptName}.php
```

### Rules
- Always use **backed enums** (`string` or `int`) so the value can be stored in the database
- Case names in **TitleCase** (e.g., `Active`, `Archived`, `Banned`, `EveryNDays`)
- Always add a `label(): string` method for human-readable display (this project uses `label()`, not `getLabel()`)
- When persisting to the database, always use `->value`: `$model->status = MyEnum::Active->value`
- When reading from the database with a cast, compare using the enum case directly: `$model->status === MyEnum::Active`
- Add the cast in the Model so the field is automatically hydrated to the enum

### Example
```php
// app/Enums/ConversationStatus.php
enum ConversationStatus: string
{
    case Active   = 'active';
    case Archived = 'archived';
    case Banned   = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Activa',
            self::Archived => 'Archivada',
            self::Banned   => 'Baneada',
        };
    }
}
```

```php
// app/Enums/MessageStatus.php
enum MessageStatus: string
{
    case Sent     = 'sent';
    case Pending  = 'pending';
    case Approved = 'approved';
    case Banned   = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Sent     => 'Enviado',
            self::Pending  => 'Pendiente',
            self::Approved => 'Aprobado',
            self::Banned   => 'Baneado',
        };
    }
}
```

### Model cast — always cast enum columns
```php
// app/Models/Conversation.php
protected function casts(): array
{
    return [
        'status' => ConversationStatus::class,
    ];
}
```

### Using enums in queries and actions
```php
// CORRECT — writing to DB via Action
Message::create([
    'status' => MessageStatus::Pending->value,
    'role'   => MessageRole::Assistant->value,
]);

// CORRECT — comparing with cast model attribute
if ($message->status === MessageStatus::Approved) { ... }

// CORRECT — filtering in a Repository
Message::query()
    ->where('status', MessageStatus::Pending->value)
    ->where('role', MessageRole::Assistant->value);

// WRONG — never hardcode strings
Message::create(['status' => 'pending']);                  // ❌
if ($message->status === 'approved') { ... }              // ❌
->where('status', '!=', 'banned')                         // ❌
```

### What always requires an enum
- Conversation/message **statuses** (`status` columns)
- **Types** and **categories** (`type`, `category` columns)
- **Roles** (`role` columns — e.g., `MessageRole`)
- **Recurrence types**, **notification types**, any fixed-value column

---

## 5. Backoffice — Controllers, ViewModels, and Vue Bridge

The backoffice follows the `jsonGroup` pattern: a Blade view bootstraps a Vue component, and a separate JSON endpoint delivers the ViewModel data.

### Directory structure
```
app/Http/Controllers/Backoffice/{ModuleController}.php
app/ViewModels/Backoffice/{Module}/Get{Module}ViewModel.php
resources/views/backoffice/{module}/index.blade.php
resources/js/pages/backoffice/{ModulePage}.vue
routes/backoffice.php  (jsonGroup macro)
```

### Controller rules
- `index()` returns a Blade view with `json_url` and any other props needed by Vue
- `json()` accepts the ViewModel via injection and returns `response()->json($viewModel->toArray())`
- Write operations (`store`, `update`, `destroy`) call Actions directly, then use `ToastNotificationService` to respond
- Never perform Eloquent queries inline in a controller — delegate to Actions or the ViewModel

```php
class HabitController extends Controller
{
    public function __construct(private readonly ToastNotificationService $toastNotification) {}

    public function index(): View
    {
        return view('backoffice.habits.index', [
            'json_url' => route('backoffice.habits.json'),
        ]);
    }

    public function json(GetHabitsViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function store(HabitRequest $request): JsonResponse
    {
        $habit = CreateHabitAction::execute($request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito creado'),
            message: __('El habito :name ha sido creado', ['name' => $habit->name]),
            timeout: 5000,
            extra: ['habit_id' => $habit->habit_id],
        );
    }

    public function update(HabitRequest $request, int $id): JsonResponse
    {
        UpdateHabitAction::execute($id, $request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito actualizado'),
            message: __('El habito ha sido actualizado con exito'),
            timeout: 5000,
        );
    }

    public function destroy(int $id): JsonResponse
    {
        DeleteHabitAction::execute($id);

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito eliminado'),
            message: __('El habito ha sido eliminado con exito'),
            timeout: 5000,
        );
    }
}
```

### ViewModel rules
- Extends `ViewModel`, optionally implements `Datatable`
- Class is `final`, named `Get{Module}ViewModel`
- Dependencies injected via constructor (TableGenerator, FilterService, Pipeline, etc.)
- Provides `tableColumns()`, `tableData()`, `tableButtons()`, `modals()`, `filterFields()`
- `tableData()` runs queries through the Pipeline and returns a `ResourceCollection` or `LengthAwarePaginator`
- Never calls Actions — only reads data

### Routes — jsonGroup macro
```php
// routes/backoffice.php
Route::jsonGroup('habits', HabitController::class, function () {
    Route::post('/', [HabitController::class, 'store'])->name('backoffice.habits.store');
    Route::put('/{id}', [HabitController::class, 'update'])->name('backoffice.habits.update');
    Route::delete('/{id}', [HabitController::class, 'destroy'])->name('backoffice.habits.destroy');
});
```

---

## 6. AI Agents and Tools

AI agents and tools live under `app/Ai/` and are registered (if needed) in `AtomicIAServiceProvider`.

### Directory structure
```
app/Ai/
    Agents/
        AtomicIAAgent.php       ← main conversational agent
        ModeratorAgent.php      ← security moderation agent
    Tools/
        GreetTool.php
        ModerateMessageTool.php
```

### Agent rules
- Implements `Agent` from `laravel/ai`
- Add `Conversational` when the agent needs conversation history
- Add `HasMiddleware` for prompt transformation (sandwich defense, boundary wrapping)
- Add `HasTools` when the agent uses tools
- Use the `Promptable` trait — it provides the `prompt()` method
- Constructor accepts only domain objects (e.g., `Conversation`, `Message`) — never config values
- Config values (provider, model) are passed to `prompt()` at call time, not in the constructor

```php
// app/Ai/Agents/ModeratorAgent.php
class ModeratorAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(private Message $message) {}

    public function instructions(): Stringable|string
    {
        return 'Eres un moderador de seguridad...';
    }

    public function tools(): iterable
    {
        return [
            new ModerateMessageTool($this->message),
        ];
    }
}
```

### Tool rules
- Implements `Tool` from `laravel/ai`
- Constructor accepts the domain object it operates on
- `description()` explains the tool to the LLM in plain language
- `schema()` defines the parameters using `JsonSchema`
- `handle()` performs the actual write operation (delegates to Actions when appropriate)

```php
// app/Ai/Tools/ModerateMessageTool.php
class ModerateMessageTool implements Tool
{
    public function __construct(private Message $message) {}

    public function description(): Stringable|string
    {
        return 'Registra la decisión de moderación del mensaje.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'approved' => $schema->boolean()->description('true si el mensaje es seguro')->required(),
            'reason'   => $schema->string()->description('Razón del rechazo si approved es false'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $approved = (bool) $request['approved'];

        $this->message->update([
            'status' => $approved ? MessageStatus::Approved : MessageStatus::Banned,
        ]);

        if (! $approved) {
            $this->message->conversation->update(['status' => ConversationStatus::Banned]);
        }

        return $approved ? 'Mensaje aprobado.' : 'Mensaje baneado.';
    }
}
```

### Calling an Agent
```php
// Always pass provider and model from config — never hardcode
(new ModeratorAgent($message))->prompt(
    $prompt,
    provider: config('ai.default'),
    model: config('ai.model'),
);
```

---

## Summary — Responsibility Map

| Layer | Responsibility | Location |
|---|---|---|
| **Action** | One write operation on DB (create, update, or delete) | `app/Actions/{Module}/` |
| **Repository** | Read queries, eager loading, filtering | `app/Repositories/` |
| **Service** | Orchestrates Repositories + Actions for a feature | `app/Services/{Module}/` |
| **Contract/Interface** | Defines the public API of a Service | `app/Services/{Module}/Contracts/` |
| **Enum** | Backed type for any status, type, role, or fixed-value field | `app/Enums/` |
| **AI Agent** | LLM conversation or task agent | `app/Ai/Agents/` |
| **AI Tool** | Write operation exposed to an Agent | `app/Ai/Tools/` |
| **Backoffice Controller** | Route handler: returns View (index), JSON (json), or toast (store/update/destroy) | `app/Http/Controllers/Backoffice/` |
| **ViewModel** | Builds frontend config (table, modals, filters, buttons) from read queries | `app/ViewModels/Backoffice/{Module}/` |
| **Command / Job / MCP Tool** | Client — calls the Service, never calls Repositories or Actions directly | `app/Console/Commands/`, `app/Jobs/`, `app/Mcp/` |