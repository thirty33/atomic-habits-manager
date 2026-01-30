# Atomic Habits Manager

Web app for tracking and managing habits based on James Clear's **Atomic Habits** methodology. Built with Laravel 12, Vue 3, and Tailwind CSS.

Aplicacion web para gestionar habitos basada en la metodologia de **Atomic Habits** de James Clear. Construida con Laravel 12, Vue 3 y Tailwind CSS.

## Purpose / Proposito

**EN:** Provides a backoffice where users can create habits with psychology-based strategy fields (implementation intention, cue, reframe, temptation bundling) and schedule them with flexible recurrence rules (daily, weekly, every N days, one-time). The goal is to apply the four laws of behavior change from the book into a practical tool.

**ES:** Ofrece un backoffice donde los usuarios pueden crear habitos con campos de estrategia basados en psicologia (intencion de implementacion, senal, reencuadre, acumulacion de tentaciones) y programarlos con reglas de recurrencia flexibles (diario, semanal, cada N dias, puntual). El objetivo es aplicar las cuatro leyes del cambio de comportamiento del libro en una herramienta practica.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3 (Options + Composition API), Tailwind CSS, Flowbite
- **Database:** MySQL
- **Build:** Vite

## Architecture / Arquitectura

### Actions Pattern

Business logic is encapsulated in single-responsibility Action classes (`app/Actions/`). Each action implements a contract interface (`CreateAction`, `UpdateAction`, `DeleteAction`, `ExportAction`) and exposes a static `execute()` method.

La logica de negocio esta encapsulada en clases Action de responsabilidad unica (`app/Actions/`). Cada action implementa una interfaz de contrato y expone un metodo estatico `execute()`.

```
app/Actions/
    Contracts/          # Action, CreateAction, UpdateAction, DeleteAction, ExportAction
    Habits/             # CreateHabitAction, UpdateHabitAction, DeleteHabitAction
    Categories/         # CreateCategoryAction, UpdateCategoryAction, ...
```

### ViewModels

Each backoffice page has a ViewModel (`app/ViewModels/`) that builds all the UI configuration: table columns, form fields, filter fields, modals, and resource detail lines. ViewModels implement the `Datatable` contract and use generator services to construct the frontend structure.

Cada pagina del backoffice tiene un ViewModel (`app/ViewModels/`) que construye toda la configuracion de UI: columnas de tabla, campos de formulario, filtros, modales y lineas de detalle. Los ViewModels implementan el contrato `Datatable` y usan servicios generadores para construir la estructura del frontend.

```
app/ViewModels/
    Contracts/Datatable.php
    Backoffice/
        Habits/GetHabitsViewModel.php
        GetDashboardViewModel.php
```

### Frontend UI Generator Services

The backend defines the entire UI structure through PHP generator services (`app/Services/Frontend/`). These services produce arrays that Vue components consume dynamically, enabling a configuration-driven UI without hardcoding forms or tables in the frontend.

El backend define toda la estructura de UI mediante servicios generadores PHP (`app/Services/Frontend/`). Estos servicios producen arrays que los componentes Vue consumen dinamicamente, permitiendo una UI dirigida por configuracion sin hardcodear formularios o tablas en el frontend.

```
app/Services/Frontend/
    TableGenerator.php          # Table columns and sorting
    FormFieldsGenerator.php     # Form fields (text, select, textarea, checkbox, date, image)
    ModalGenerator.php          # CRUD modals
    ButtonGenerator.php         # Action buttons
    ResourceDetailGenerator.php # Detail view lines
    SidebarGenerator.php        # Sidebar navigation items
    StatsGenerator.php          # Dashboard stat cards
```

### Enums

PHP 8.1 backed enums (`app/Enums/`) encapsulate domain values with labels and associated data (e.g., `HabitNature` maps build/break to user-friendly labels and auto-assigned colors).

Enums respaldados de PHP 8.1 (`app/Enums/`) encapsulan valores de dominio con labels y datos asociados (ej: `HabitNature` mapea build/break a labels amigables y colores auto-asignados).

### Pipeline Filters

Table filtering uses Laravel's `Pipeline` pattern. Each filter is a class (`app/Filters/`) that receives the query builder and applies conditions based on request parameters.

El filtrado de tablas usa el patron `Pipeline` de Laravel. Cada filtro es una clase (`app/Filters/`) que recibe el query builder y aplica condiciones segun los parametros del request.

### Vue Component Structure

The frontend follows a layered component architecture:

El frontend sigue una arquitectura de componentes por capas:

```
resources/js/
    layouts/            # BackofficeLayout (sidebar + content area)
    pages/              # Page-level components (DatatablePage, DashboardPage)
    components/
        templates/      # Reusable page templates (TableListTemplate, FormTemplate)
        common/         # CRUD resource components (AppCreateResource, AppEditResource, ...)
        ui/             # Base UI components
            datatable/  # Table, rows, columns, pagination
            forms/      # Input fields, buttons, errors
            sidebars/   # Sidebar and navigation items
            modals/     # Modal dialogs
            toasts/     # Toast notifications
    composables/        # Shared logic (useForm, useDatatable, useModal, useAxios, ...)
    providers/          # App-level providers (Toast, DataProvider)
```

## Database Schema

```
habits (1) ── (N) habit_schedules (1) ── (N) habit_occurrences
                                                     │
                                                     ▼
                                              daily_report_entries
```

- **habits**: Habit catalog with strategy fields (nature, desire type, cue, reframe, implementation intention)
- **habit_schedules**: Recurrence rules (daily, weekly, every N days, one-time) with habit stacking support
- **habit_occurrences**: Concrete calendar instances generated from schedules
- **daily_report_entries**: End-of-day reports linking to occurrences or free-form activities