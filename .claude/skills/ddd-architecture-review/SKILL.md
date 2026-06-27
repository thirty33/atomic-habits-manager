---
name: ddd-architecture-review
description: Curated multi-source DDD review before designing or coding any bounded context, aggregate, value object, port, use case, repository, ViewModel, or frontend-DDD layer. Activates whenever you start planning/implementing DDD architecture, a new bounded context (e.g. Access, Subscriptions), porting a BC from another project, modeling aggregates/VOs/domain policies, defining ports/use cases/readers, or building the hexagonal frontend layer. Use it to ground decisions in the project's established patterns AND the external reference corpus instead of improvising.
---

# DDD Architecture Review (multi-source)

This project is built with **tactical + strategic DDD** (bounded contexts under `src/BoundedContext/*`, `Core\Shared` kernel, hexagonal frontend under `resources/js/<module>/{domain,application,infrastructure}`). Before you design or write any new DDD code, **review the curated sources below and extract the patterns that already work** — do not invent new conventions when an established one exists here or in the references.

## When to use

Activate at the **start** of any of these — not when you're already stuck:
- Designing or planning a **bounded context** (new or ported), an aggregate, value object, domain policy/specification, or domain event.
- Defining **ports** (repository/reader interfaces), **use cases / application actions**, **readers (CQRS)**, **ReadModels/snapshots**.
- Wiring the **backoffice** (Controller → Service → use cases, `jsonGroup` routes, **ViewModel**, Blade→Vue bridge, sidebar, global component registration).
- Building the **frontend DDD** layer (domain/application/infrastructure in JS, gateways/adapters over `window.axios`, application-controller composables).
- **Porting** a BC from DELICIUS or adapting a reference pattern.

## How to use (the loop)

1. **Pick the dimension** you're working on (BC boundary, aggregate, VO, port, use case, repository, ViewModel, frontend port/adapter, domain policy, event sourcing).
2. **Read the in-project example first** (Section A) — the house style wins ties. Match its naming, layering, and idioms.
3. **Cross-reference the external sources** (Sections B–F) for the canonical pattern and trade-offs, then adapt — never copy a foreign kernel wholesale (adapt method names/exception contracts to this project, see Section A notes).
4. **Write it down** in the relevant `docs/**/0X-arquitectura-ddd.md` before coding (class tree per layer, namespaces, migrations, enforcement points).
5. Then implement, test (unit domain + feature), Pint, verify.

> Don't fan out reads of every source every time. Read the **in-project** example always; pull the **one or two** external sources that match the dimension at hand (table below).

## Quick routing — which source for which question

| You're working on… | Read (in this order) |
|---|---|
| BC boundary / context map / ACL | A (this project) → S2 Evans Ch04/Ch14 → S4 `03-dungeon-context-map` |
| Aggregate / entity / factory | A → S2 Evans Ch05–06 → S3 `Order/Domain` → S1 creacionales |
| Value Object / immutability | A `Core\Shared` VO base → S2 Evans Ch05 → S3 `Shared/.../ValueObjects` → S1 `05-inmutabilidad.ts` |
| Port / repository (backend) | A (existing `*Repository` + Eloquent impl) → S3 `Domain/Contracts` + `Infrastructure/Repositories` |
| Use case / application action | A → S3 `Application/Commands`+`Handlers` → S1 strategy/command |
| Domain policy / specification | A → S3 `Domain/Specifications` → S2 Fowler Ch09 |
| Domain events / CQRS / read models | A (Calendar reader/snapshot) → S2 Fowler Ch09/Ch10 → S4 `04-event-sourced-transfer` |
| ViewModel / web presentation | A (`app/ViewModels/**`) → S2 Fowler Ch14 (Application Controller, Two-Step View) |
| Frontend DDD (ports/adapters/use cases) | A (`resources/js/{calendar,reports}/**`) → S4 `02-hexagonal-dungeon` → S3 hexagonal Ports↔Adapters |
| Access / roles / permisos / módulos | A → **DELICIUS Access BC** (Section B0) |

---

## Section A — THIS project (the house style — read first, always)

`/home/joel/atomic-habits-manager` — PSR-4 `Core\ => src/`. Already implements DDD; **new code must match it**.

- **Bounded contexts** (9): `src/BoundedContext/{Calendar,Conversations,DailyReports,HabitOccurrences,HabitSchedules,Habits,Identity,Insights}/{Domain,Application,Infrastructure}`. Namespace root `Core\BoundedContext\<BC>\...`.
  - `Calendar` — the reference for a **read-projection BC + CQRS reader** (`Application/CalendarReader`, `Application/ReadModels/CalendarBlockSnapshot`, `Infrastructure/Persistence/Eloquent/EloquentCalendarReader`). Read side only; no aggregate writes.
  - `Identity` — the reference for an **aggregate with behavior**: `Domain/User.php extends AggregateRoot` (private ctor + `register()`/`fromPrimitives()` factories; `activate()/deactivate()/logIn()`; VOs in `Domain/ValueObjects/Concretes/`), `Domain/UserRepository.php` port + `Infrastructure/.../EloquentUserRepository` (`findActiveByEmail`, `save` publishes events), `is_active` enforcement throwing `UserNotActive`.
  - `DailyReports` — richest **write+read** BC (aggregate + child entity + collections + `Domain/Criteria/*` + `EloquentDailyReportsCriteriaTranslator` + many `Application/Actions` + `ReadModels`).
- **Shared kernel** `src/Shared/` (namespace `Core\Shared\...`, sibling of `BoundedContext`): `Domain/ValueObjects/ValueObject.php` (abstract, `value()`, `equals()`, immutable, inherited `static from(...)` factory), primitives `Domain/ValueObjects/Primitives/{IntegerId,BoundedText,StringEnum}`, `Domain/Collection.php` (abstract, subclass implements `type(): string`), `Domain/AggregateRoot.php` (`record()/pullDomainEvents()/peekDomainEvents()` — **aggregates record events, repositories publish them via `Core\Shared\Domain\Bus\DomainEventBus`**), `Domain/DomainException.php` base, `Domain/ProvidesValidationErrors.php` (`validationErrors(): array` → mapped to **422** by a single `render()` in `bootstrap/app.php`), `Application/Persistence/TransactionManager.php` (**`execute(callable)`**, not `run()`).
- **Backoffice wiring**: custom `jsonGroup` route macro (index/json/store/update/destroy) in `AppServiceProvider`; **ViewModel** (`app/ViewModels/Backoffice/**`) — *only public methods* are reflected to `Str::snake(name)` JSON keys; Controllers go **Controller → Service → (use cases + repositories)** (never call Actions/Repositories directly), responses via `ToastNotificationService` (`extra` param); sidebar links built in `HandleBackofficeRequests`; Vue components registered in `resources/js/components/App.vue`.
- **Frontend hexagonal** (`resources/js/{calendar,reports}/{domain,application,infrastructure}`): domain (status/value objects), application (`use-*` composables = application controllers, ports), infrastructure (`http-*-gateway` over `window.axios`). The node-modal create/edit flow (`AppModalSteps` + `adapters/useModalSteps.ts` + `application/step-actions.ts` + `Notifier` port) is the reference for a thin-component + application-controller split.

**House rules that override foreign references** (from project memory / CLAUDE.md):
- No legacy coexistence: new DDD layers must NOT point to `App\Repositories\*` / `App\Services\*` in prod.
- Domain validations (uniqueness/existence) live in the **use case via repository**, throw a `ProvidesValidationErrors` exception → 422. No `Rule::unique`/Eloquent in FormRequest `authorize()`.
- New field/config via **builder methods** (e.g. `->visibleWhen()`), not constructor args.
- Comments/PHPDoc in **English**; curly braces always; explicit return types; constructor property promotion.

---

## Section B — Reference projects (DDD in practice)

### B0 — DELICIUS Access BC (the port source for roles/permisos/módulos)
`/home/joel/DELICIUS-FOOD-CRM` — same PSR-4 `Core\ => src/`. Review `src/BoundedContext/Access/{Domain,Application,Infrastructure}` for the **Role / Permission / Module** aggregates, their VOs (RoleName, PermissionName, PermissionCode, ModuleCode, ModuleName, collections), the **`Capability`** enum (single source of truth of codes, incl. `BackofficeAdmin`), the read port **`UserCapabilities`** (`has`/`all`) + `EloquentUserCapabilities` (single JOIN `permissions → permission_role → role_user`, memoized per request), the **`Authorize`** use case, and the Create/Update/Delete + AssignRole use cases. Pivots `permission_role`, `role_user`. **Adapt on port** (see Section A house rules): `TransactionManager::execute()`, re-add `from()` per VO, exceptions implement `ProvidesValidationErrors`. Drop DELICIUS's SEGMENT permission nature, `permission_user` pivot, and tenancy/`company_id`.

### B1 — jessegriffin-ddd-laravel (DDD-in-Laravel, backend)
`/home/joel/arquitectura_laravel/jessegriffin-ddd-laravel` — PSR-4 `Domain\ => src/`. Bounded contexts as top-level folders, each `Domain/Application/Infrastructure`; `Claim` shows **sub-contexts** (`Estimation/Validation/Submission`). Patterns to extract:
- **Aggregate slice**: `src/Order/Domain/{Models/Order.php, Contracts/OrderRepositoryInterface.php, Factories/OrderFactory.php}` + `src/Order/Infrastructure/Repositories/EloquentOrderRepository.php`.
- **Value objects**: `src/Shared/Domain/ValueObjects/{ValueObject.php, AbstractValue.php, Money.php, EmailAddress.php}`.
- **Use cases as Command+Handler**: `src/Auth/Application/Commands/SignupUserCommand.php` → `.../Handlers/SignupUserHandler.php`.
- **Domain events** (past tense): `src/Claim/.../Domain/Events/ClaimWasSubmitted.php`. **Specifications**: `src/Claim/Submission/Domain/Specifications/`.
- **Explicit hexagonal ports↔adapters**: `src/Shared/Application/Hexagonal/Ports/` ↔ `src/Shared/Infrastructure/Hexagonal/Adapters/`.

### B2 — js-ddd-mazmorra (DDD in the FRONTEND, JS)
`/home/joel/arquitectura_laravel/js-ddd-mazmorra` — frontend DDD (vanilla ESM, JSDoc ports). Four progressive demos; extract:
- **Ports & adapters on the front**: `02-hexagonal-dungeon/src/domain/ports.js` (duck-typed port contracts) ↔ `02-hexagonal-dungeon/src/adapters/` (in-memory + localStorage repos, DOM/console notifiers). This is the closest mirror to our `infrastructure/http-*-gateway.js` + `application` port split.
- **VO vs entity / domain service**: `01-orc-tactical-blocks/src/domain/{value-objects.js, prisoner-transfer-service.js}`.
- **Strategic DDD / context map / ACL**: `03-dungeon-context-map/src/contexts/` (BC-per-folder) + `.../prisoner-management/anticorruption-layer.js`.
- **Event sourcing / CQRS on the front**: `04-event-sourced-transfer/src/domain/{events.js, event-store.js, projections.js, transfer-commands.js}`.
- Overall map: `README.md`.

---

## Section C — Theory corpus (Evans + Fowler, book summaries)

`/mnt/c/Users/USUARIO/Documents/books/desarrollosoftware/resumenes` — 130 Markdown summaries, paired `*-reporte.md` (writeup) + `*-ejemplos-ddd.md` (DDD code). Two books:

- **`Domain-Driven_Design_Evans/`** (start at `INDICE.md`): Ch04 Isolating the Domain (Layered Arch), Ch05 A Model Expressed in Software (Entities/VOs/Services/Modules), **Ch06 Life Cycle of a Domain Object** (`aggregates-factories-repositories-reporte.md` — aggregates, factories, repositories), Ch09 Making Implicit Concepts Explicit, Ch10 Supple Design. Read these when modeling aggregates, VOs, repositories, and BC boundaries.
- **`Patterns_of_Enterprise_Application_Architecture/`** (Fowler): **Ch09 Domain Logic** (Domain Model vs Service Layer vs Transaction Script), **Ch10 Data Source** (Data Mapper / Active Record — repositories), **Ch14 Web Presentation** (MVC, Application Controller, Two-Step View — maps to our ViewModel + thin-component/composable split), plus `11_agregados/` (aggregate variants). Read these for the Service Layer vs Domain Model decision and ViewModel/presentation choices.

---

## Section D — Design-pattern mechanics (GoF, TypeScript)

`/home/joel/desing-patterns/patrones-diseno` — 23 GoF patterns in TS (Deno), one file per pattern (+ `.2` worked variants), in `01-creacionales/`, `02-estructurales/`, `03-comportamiento/`. Most relevant to DDD:
- VO/aggregate construction: `01-creacionales/` (builder, abstract-factory, factory-function) + **`01-creacionales/05-inmutabilidad.ts`** (immutability → VO design).
- Ports/adapters: `02-estructurales/{01-adapter.ts, 05-facade.ts, 07-proxy.ts}`.
- Use cases / domain events: `03-comportamiento/{08-strategy.ts, 02-command.ts, 06-observer.ts}`.

---

## Output expectation

After a review, you should be able to produce/extend the relevant **`docs/**/0X-arquitectura-ddd.md`** with: BC map, per-layer class tree (Domain/Application/Infrastructure), exact namespaces, value objects + invariants, ports + use case signatures, migrations, domain policies, enforcement points, and the back/front wiring — grounded in the sources above, matched to **Section A's** house style. Cite the source paths you used.
