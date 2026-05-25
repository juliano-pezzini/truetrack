# Project Structure

**Root:** `workspace/`

## Directory Tree

```
workspace/
├── app/
│   ├── Console/Commands/          # Artisan commands (3 cleanup tasks)
│   ├── Enums/                     # Backed string enums (4)
│   ├── Exceptions/                # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/           # Web controllers (12)
│   │   │   ├── Api/V1/            # API v1 controllers (13)
│   │   │   └── Auth/              # Breeze auth controllers
│   │   ├── Middleware/            # HandleInertiaRequests, VerifyCsrfToken
│   │   ├── Requests/             # Form request validation (27)
│   │   │   └── Auth/             # Auth-specific requests
│   │   └── Resources/            # API JSON transformers (12)
│   ├── Jobs/                      # Queue jobs (3: base + 2 processors)
│   ├── Models/                    # Eloquent models (14)
│   │   └── Traits/                # Model traits
│   ├── Observers/                 # TransactionObserver
│   ├── Policies/                  # Authorization policies (10)
│   ├── Providers/                 # Service providers
│   └── Services/                  # Business logic services (8)
├── bootstrap/                     # App bootstrap + cache
├── config/                        # Laravel config files (12)
├── database/
│   ├── factories/                 # Model factories (14)
│   ├── migrations/                # Schema migrations (32)
│   └── seeders/                   # Database seeders (15)
├── public/                        # Web root (index.php, build/)
├── resources/
│   ├── css/                       # Tailwind entry CSS
│   ├── js/
│   │   ├── Components/            # Reusable React components
│   │   │   ├── Dashboard/         # Dashboard widgets (6)
│   │   │   ├── Import/            # Unified import components (6)
│   │   │   ├── OfxImport/         # OFX-specific components (4)
│   │   │   └── XlsxImport/       # XLSX-specific components (7)
│   │   ├── Layouts/               # AuthenticatedLayout, GuestLayout
│   │   ├── Pages/                 # Inertia page components
│   │   │   ├── Accounts/          # Index, Create, Edit
│   │   │   ├── Auth/              # Login, Register, etc.
│   │   │   ├── AutoCategoryRules/ # Rules management
│   │   │   ├── Categories/        # Index, Create, Edit
│   │   │   ├── Imports/           # Unified import page
│   │   │   ├── LearnedPatterns/   # Pattern management
│   │   │   ├── OfxImports/        # OFX import pages
│   │   │   ├── Profile/           # User profile
│   │   │   ├── Reconciliations/   # Reconciliation workflow
│   │   │   ├── Tags/              # Index, Create, Edit
│   │   │   └── Transactions/      # Index, Create, Edit
│   │   ├── Utils/                 # JS utilities
│   │   └── __tests__/             # Jest test files
│   └── views/                     # Blade templates (app.blade.php)
├── routes/
│   ├── api.php                    # API v1 routes (Sanctum-protected)
│   ├── web.php                    # Web routes (session auth)
│   ├── auth.php                   # Breeze auth routes
│   └── console.php                # Scheduled tasks
├── storage/                       # Logs, cache, uploads
├── tests/
│   ├── Feature/                   # Feature/integration tests
│   │   ├── Api/V1/                # API endpoint tests (10)
│   │   ├── Auth/                  # Auth flow tests
│   │   ├── Console/               # Command tests
│   │   └── Integration/           # Cross-component tests
│   ├── Unit/                      # Unit tests
│   │   ├── Jobs/                  # Job tests
│   │   ├── Models/                # Model tests (5)
│   │   ├── Resources/             # Resource transform tests
│   │   └── Services/              # Service tests (5)
│   ├── Helpers/                   # Test helper classes
│   └── fixtures/                  # Test fixture files (XLSX, CSV)
└── vendor/                        # Composer dependencies
```

## Where Things Live

**Accounts:**
- UI: `resources/js/Pages/Accounts/` (Index, Create, Edit)
- Controller: `app/Http/Controllers/AccountController.php` (web) + `Api/V1/AccountController.php` (API)
- Service: `app/Services/AccountingService.php`
- Model: `app/Models/Account.php`, `app/Models/AccountBalance.php`

**Transactions:**
- UI: `resources/js/Pages/Transactions/` (Index, Create, Edit)
- Controller: `app/Http/Controllers/TransactionController.php` + `Api/V1/TransactionController.php`
- Service: `app/Services/AccountingService.php`
- Model: `app/Models/Transaction.php`
- Observer: `app/Observers/TransactionObserver.php`

**Import (OFX + XLSX):**
- UI: `resources/js/Pages/Imports/Index.jsx` (unified)
- Components: `resources/js/Components/Import/`, `Components/OfxImport/`, `Components/XlsxImport/`
- Controllers: `ImportController`, `OfxImportController`, `Api/V1/XlsxImportController`
- Services: `OfxImportService`, `XlsxImportService`, `ImportHistoryService`
- Jobs: `ProcessOfxImport`, `ProcessXlsxImport`

**Auto-Categorization:**
- UI: `resources/js/Pages/AutoCategoryRules/`, `Pages/LearnedPatterns/`
- Services: `AutoCategorizationService`, `AutoCategoryLearningService`
- Models: `AutoCategoryRule`, `LearnedCategoryPattern`, `AutoCategoryCorrection`, `AutoCategorySuggestionLog`

**Dashboard:**
- UI: `resources/js/Pages/Dashboard.jsx`
- Components: `resources/js/Components/Dashboard/` (6 widgets)
- Controller: `DashboardController`
- Service: `ReportingService`
