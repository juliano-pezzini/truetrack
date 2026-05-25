# Architecture

**Pattern:** Monolith — Laravel MVC + Service Layer, dual interface (Inertia.js web + REST API)

## High-Level Structure

```
Browser ─→ Inertia.js (web routes) ─→ Web Controllers ─→ Services ─→ Models ─→ PostgreSQL
                                                                    ↕
Mobile  ─→ REST API /api/v1 (Sanctum) ─→ API Controllers ─→ Services ─→ Redis (cache/queue)
                                                                    ↕
Queue Worker ─→ Jobs (ProcessOfxImport, ProcessXlsxImport) ─→ Services
```

## Identified Patterns

### Dual Controller Layer

**Location:** `app/Http/Controllers/` (web) + `app/Http/Controllers/Api/V1/` (API)
**Purpose:** Separate Inertia responses (web) from JSON responses (API) while sharing services
**Implementation:** Web controllers return `Inertia::render()`, API controllers return `JsonResource`. Both delegate to the same Service classes.
**Example:** `AccountController` (web, returns Inertia) vs `Api\V1\AccountController` (API, returns `AccountResource`)

### Service Layer

**Location:** `app/Services/` (8 services)
**Purpose:** Encapsulate business logic, DB transactions, and complex operations
**Implementation:** Services are injected via constructor DI. They wrap DB operations in `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()`. Controllers remain thin.
**Services:** `AccountingService`, `AutoCategorizationService`, `AutoCategoryLearningService`, `ImportHistoryService`, `OfxImportService`, `ReconciliationService`, `ReportingService`, `XlsxImportService`

### Form Request Validation

**Location:** `app/Http/Requests/` (27 request classes)
**Purpose:** Validate and authorize requests before they reach controllers
**Implementation:** Each mutation action has a dedicated `Store*Request` or `Update*Request`. Includes custom error messages. Authorization returns `true` (delegated to policies).

### API Resource Responses

**Location:** `app/Http/Resources/` (12 resources)
**Purpose:** Transform Eloquent models into consistent JSON API responses
**Implementation:** Each resource maps model attributes to a flat array. `AccountResource` calculates balance on-the-fly via `AccountingService`.

### Policy-Based Authorization

**Location:** `app/Policies/` (10 policies)
**Purpose:** Ownership-based authorization — users can only access their own resources
**Implementation:** Simple `user_id === $user->id` checks. `viewAny` and `create` always return `true`.

### Observer Pattern

**Location:** `app/Observers/TransactionObserver.php`
**Purpose:** Auto-categorize transactions on creation when no category is set
**Implementation:** Hooks into `creating` event, calls `AutoCategorizationService::suggestCategory()`, applies if confidence threshold met.

### Base Job Pattern

**Location:** `app/Jobs/BaseProcessingJob.php`
**Purpose:** Shared infrastructure for import processing jobs
**Implementation:** Abstract class with progress tracking, completion marking, failure handling, and concurrency limit checking. `ProcessOfxImport` and `ProcessXlsxImport` extend it.

### Enum Types

**Location:** `app/Enums/` (4 enums)
**Purpose:** Type-safe constants for account types, category types, reconciliation statuses, transaction types
**Implementation:** Backed string enums with `values()` and `label()` helper methods. Cast in models via `$casts`.

## Data Flow

### Transaction Recording

```
Controller → AccountingService::recordTransaction()
  → DB::beginTransaction()
  → Account::lockForUpdate()
  → Transaction::create()
  → TransactionObserver::creating() → AutoCategorizationService (if no category)
  → AccountingService::recalculateMonthlyBalance()
  → DB::commit()
```

### Statement Import (OFX/XLSX)

```
Controller → validate + store file → dispatch Job
  → Job::handle() → ImportService::parse()
  → For each row: check duplicate → create Transaction
  → AccountingService::recalculateMonthlyBalance()
  → Mark import complete
```

### Balance Calculation

```
AccountingService::calculateBalance(account, date)
  → Find latest AccountBalance snapshot before date
  → If none, use account.initial_balance
  → Sum transactions from snapshot date to target date
  → Return: base + credits - debits
```

## Code Organization

**Approach:** Layer-based (MVC + Services) with domain grouping in frontend

**Backend layers:**
- `app/Models/` — Eloquent models (14 models)
- `app/Services/` — Business logic (8 services)
- `app/Http/Controllers/` — Web controllers (12) + API controllers (13)
- `app/Http/Requests/` — Validation (27 form requests)
- `app/Http/Resources/` — API transformers (12 resources)
- `app/Policies/` — Authorization (10 policies)
- `app/Jobs/` — Queue jobs (3: base + 2 import processors)
- `app/Observers/` — Event observers (1: TransactionObserver)
- `app/Enums/` — Type enums (4)
- `app/Console/Commands/` — Artisan commands (3 cleanup commands)

**Frontend grouping:**
- `resources/js/Pages/` — Inertia pages grouped by domain (Accounts, Categories, Tags, Transactions, Imports, etc.)
- `resources/js/Components/` — Reusable components grouped by feature (Dashboard, Import, OfxImport, XlsxImport)
- `resources/js/Layouts/` — AuthenticatedLayout, GuestLayout
