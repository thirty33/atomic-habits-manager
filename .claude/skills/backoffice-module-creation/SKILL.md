---
name: backoffice-module-creation
description: Create new backoffice modules following the established architecture — jsonGroup routes, ViewModel pattern, Blade + Vue bridge, sidebar entry, and global component registration.
---

# Backoffice Module Creation

## When to use this skill

Use this skill when creating a new module/page in the backoffice. Every new module must follow the established architecture to maintain consistency.

## Architecture overview

```
Route (jsonGroup)
    → Controller (index + json)
        → ViewModel (extends ViewModel base)
            → toArray() auto-collects public methods as snake_case keys
    → Blade view (<x-backoffice-layout>)
        → Vue page component (registered globally in App.vue)
            → receives :json-url prop
```

## Step-by-step checklist

### 1. Route — `routes/backoffice.php`

All backoffice routes use the `Route::jsonGroup()` macro defined in `AppServiceProvider`. This macro registers routes based on the methods array:

```php
Route::jsonGroup('module-name', \App\Http\Controllers\Backoffice\ModuleController::class, [
    'index', 'json',          // Required: page + data endpoint
    // 'store',               // Optional: POST /
    // 'update',              // Optional: PUT /{id}
    // 'destroy',             // Optional: DELETE /{id}
    // 'export',              // Optional: export routes
]);
```

The macro creates routes with prefix `module-name` and names like `backoffice.module-name.index`, `backoffice.module-name.json`, etc.

Routes are automatically wrapped in the `backoffice` middleware group (auth + session + CSRF + `HandleBackofficeRequests`) via `bootstrap/app.php`.

### 2. Controller — `app/Http/Controllers/Backoffice/`

Follow the `DashboardController` pattern for non-CRUD modules, or `HabitController` for CRUD modules:

```php
<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\ViewModels\Backoffice\ModuleName\GetModuleNameViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ModuleNameController extends Controller
{
    public function index(): View
    {
        return view('backoffice.module-name.index', [
            'json_url' => route('backoffice.module-name.json'),
        ]);
    }

    public function json(GetModuleNameViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }
}
```

Key points:
- `index()` returns a Blade view with `json_url` for the JSON endpoint
- `json()` injects the ViewModel via dependency injection and returns `$viewModel->toArray()`
- For CRUD: inject `ToastNotificationService` and use Action classes for business logic

### 3. ViewModel — `app/ViewModels/Backoffice/`

ViewModels extend the abstract `ViewModel` base class. The base `toArray()` method uses reflection to auto-collect all public methods (except `__construct` and `toArray`) as snake_case keys.

```php
<?php

namespace App\ViewModels\Backoffice\ModuleName;

use App\ViewModels\ViewModel;

class GetModuleNameViewModel extends ViewModel
{
    // Each public method becomes a key in the JSON response.
    // Method name is converted to snake_case automatically.
    // Example: pageTitle() → "page_title" in JSON

    public function pageTitle(): string
    {
        return 'My Module';
    }
}
```

Important:
- Extend `App\ViewModels\ViewModel` (uses `WithSkeleton` trait automatically)
- For CRUD/datatable modules: implement `App\ViewModels\Contracts\Datatable` interface (requires: `title()`, `textModel()`, `tableColumns()`, `tableData()`, `tableButtons()`, `modals()`, `filterFields()`)
- For non-datatable modules (dashboard, chat, etc.): just extend `ViewModel`
- Directory structure: `app/ViewModels/Backoffice/ModuleName/GetModuleNameViewModel.php`

### 4. Blade view — `resources/views/backoffice/`

```blade
<x-backoffice-layout>
    <module-name-page
        :json-url="'{{ $json_url }}'"
    />
</x-backoffice-layout>
```

Key points:
- Always use `<x-backoffice-layout>` which renders `layouts/backoffice.blade.php`
- The layout provides: HTML shell, Vite assets, `<backoffice-layout>` Vue component with sidebar
- The Vue component name in Blade uses **kebab-case** (e.g., `<dashboard-page>`, `<datatable-page>`)
- Pass `json_url` as a Vue prop using `:json-url="'{{ $json_url }}'"`

### 5. Vue page component — `resources/js/pages/backoffice/`

Create the Vue component and **register it globally** in `resources/js/components/App.vue`:

```js
// In App.vue - add async import + register in components
const ModuleNamePage = defineAsyncComponent(() => import('@/pages/backoffice/ModuleNamePage.vue'));

export default {
    components: {
        // ...existing components,
        ModuleNamePage,
    },
}
```

The Vue component receives `jsonUrl` as a prop (auto-converted from `:json-url` kebab-case):

```vue
<script setup>
defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
});
</script>
```

### 6. Sidebar entry — `app/Http/Middleware/HandleBackofficeRequests.php`

Add a `SidebarLink` to the sidebar generator chain:

```php
->addSidebarItem(
    new SidebarLink(
        text: 'Module Name',
        href: route('backoffice.module-name.index'),
        iconComponent: Heroicons::ICON_CONSTANT,
        current: request()->routeIs('backoffice.module-name.index'),
    )
)
```

The sidebar is built in `HandleBackofficeRequests::handle()` using `SidebarGenerator`. Items are rendered in order. Available item types:
- `SidebarLink` — navigation link with icon
- `SidebarSeparator` — visual divider
- `SidebarHelloUser` — greeting with user name

### 7. Heroicons constant — `app/Constants/Heroicons.php`

Add the icon constant if it doesn't exist:

```php
public const ICON_NAME = 'HeroiconComponentName';
```

Icons are rendered as Vue components from the Heroicons library (Outline style).

## Existing modules for reference

| Module | Controller | ViewModel | Datatable? |
|--------|-----------|-----------|------------|
| Dashboard | `DashboardController` | `GetDashboardViewModel` | No |
| Habits | `HabitController` | `GetHabitsViewModel` | Yes (`Datatable` interface) |

## File naming conventions

- Controllers: `PascalCaseController.php`
- ViewModels: `GetPascalCaseViewModel.php` in subdirectory `Backoffice/PascalCase/`
- Blade views: `backoffice/kebab-case/index.blade.php`
- Vue pages: `pages/backoffice/PascalCasePage.vue`
- Routes prefix: `kebab-case`

## Important notes

- All commands must be run through `./vendor/bin/sail` (Docker environment)
- The `jsonGroup` macro is defined in `App\Providers\AppServiceProvider::registerJsonGroupMacro()`
- The backoffice middleware group is configured in `bootstrap/app.php`
- Vue components in Blade templates use kebab-case naming (e.g., `<my-component>`)
- Vue components must be registered in `App.vue` using `defineAsyncComponent` for code splitting