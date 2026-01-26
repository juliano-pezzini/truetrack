# TrueTrack Development Plan

## Overview

Cloud-based personal finance management system with double-entry accounting principles, built with Laravel, PostgreSQL, and Inertia.js/React. API-first architecture to support both web and future mobile applications.

## Technology Stack

- **Backend**: PHP 8.2+ with Laravel 11.x
- **Database**: PostgreSQL 15+ (AWS Aurora Serverless v2 in production)
- **Development Environment**: Laravel Sail (Docker)
- **Frontend**: Inertia.js with React 18+
- **Authentication**: Laravel Breeze with Sanctum for API
- **API**: RESTful with versioning (`/api/v1`)
- **Testing**: PHPUnit (backend), Jest (React components)
- **CI/CD**: GitHub Actions with strict quality gates

## Quality Standards

All Pull Requests must pass:
- ✅ PHPUnit tests (Feature, Unit, Integration)
- ✅ Jest tests for complex React components
- ✅ Laravel Pint (code style)
- ✅ PHPStan/Larastan (static analysis)
- ✅ Coverage thresholds (configurable)
- ⚠️ Manual approval bypass available for exceptional situations

## Development Phases

### Phase 1: Project Foundation
**Goal**: Bootstrap complete development environment with authentication

**Deliverables**:
- Laravel installation in workspace
- Sail configuration with PostgreSQL
- Breeze installation with Inertia/React stack
- API routes structure (`/api/v1`) with Sanctum
- GitHub Actions workflow (`.github/workflows/tests.yml`) with:
  - PostgreSQL service container
  - PHPUnit tests
  - Jest for React components
  - Laravel Pint enforcement
  - PHPStan/Larastan analysis
  - Coverage reporting
- Working user registration/login (web + API)
- First PR with all quality gates passing

**Key Files**:
- `compose.yaml` (Docker Compose configuration)
- `.github/workflows/tests.yml`
- `routes/api.php` (v1 routes)
- `phpunit.xml`
- `jest.config.js`
- `pint.json`
- `phpstan.neon`

---

### Phase 2: Accounts Module
**Goal**: Enable users to manage their financial accounts

**Deliverables**:
- `Account` model with attributes:
  - `type` enum: bank, credit_card, wallet, transitional
  - `name`, `description`
  - `initial_balance` (decimal) - Starting balance, never modified
  - `is_active` (boolean)
  - Timestamps, soft deletes
- `AccountBalance` model for monthly snapshots:
  - `account_id` (foreign key)
  - `year` (integer)
  - `month` (integer)
  - `closing_balance` (decimal) - Balance at end of month
  - Timestamps
  - Unique index on (account_id, year, month)
- Database migrations:
  - `accounts` table with `initial_balance` column
  - `account_balances` table for monthly snapshots
  - Indexes on foreign keys and (account_id, year, month)
- API Controller (`Api\V1\AccountController`) with:
  - Pagination support
  - Filtering (by type, active status)
  - Sorting capabilities
  - RESTful endpoints (index, store, show, update, destroy)
- Web Controller (`AccountController`) for Inertia
- React components:
  - `AccountList` (with filters)
  - `AccountForm` (create/edit)
- Form Request validation
- Factory and Seeder
- PHPUnit tests:
  - Feature tests (API endpoints)
  - Feature tests (Inertia responses)
  - Unit tests (model methods)
- Jest tests for forms
- All quality gates passing

**User Capability**: Create, view, edit, and delete financial accounts with current balances via web interface and API.

---

### Phase 3: Categories and Tags
**Goal**: Provide financial taxonomy for organizing transactions

**Deliverables**:
- `Category` model:
  - `name`, `description`
  - `type` enum: revenue, expense
  - `parent_id` (for subcategories)
  - Timestamps, soft deletes
- `Tag` model:
  - `name`, `color`
  - Timestamps
- Database migrations with indexes
- Many-to-many relationship (Transaction-Tag pivot)
- API Controllers with pagination/filtering
- Web Controllers for Inertia
- React components:
  - `CategoryList` and `CategoryForm`
  - `TagList` and `TagForm`
- Form Request validation
- Factories and Seeders
- PHPUnit tests (comprehensive)
- Jest tests for complex forms
- All quality gates passing

**User Capability**: Organize complete financial structure with revenue/expense categories and flexible tags via web and API.

---

### Phase 4: Transactions with Double-Entry Accounting
**Goal**: Record financial transactions with accounting integrity

**Deliverables**:
- `Transaction` model:
  - `account_id` (foreign key)
  - `category_id` (foreign key)
  - `amount` (decimal)
  - `description`
  - `transaction_date` (date)
  - `settled_date` (date, nullable)
  - `type` enum: credit, debit
  - Timestamps, soft deletes
- `AccountingService`:
  - Apply personal finance logic (credit increases balance, debit decreases balance)
  - Update current month's balance snapshot
  - Calculate balances efficiently using monthly snapshots
  - Handle transaction reversals
  - Unit tests for business logic
  - **Balance Calculation Logic**:
    - Credits (income, deposits, refunds) INCREASE account balance
    - Debits (expenses, withdrawals, payments) DECREASE account balance
    - Credit card accounts remain "in debit" (negative balance = amount owed)
    - To calculate balance for any date:
      1. Get the most recent monthly snapshot before the date
      2. Sum all transactions from that snapshot date to target date
      3. Formula: `balance = snapshot_balance + sum(credits) - sum(debits)`
- API endpoints with pagination/filtering:
  - By date range
  - By account
  - By category
  - By tags
  - By settlement status
- Web Controller for Inertia
- React components:
  - `TransactionList` (with filters)
  - `TransactionForm` (with account/category/tag dropdowns)
  - Balance display
- Form Request validation
- Factory and Seeder
- PHPUnit tests:
  - Feature tests (CRUD operations)
  - Integration tests (balance updates)
  - Unit tests (AccountingService)
  - Test double-entry integrity
- Jest tests for form logic
- Coverage thresholds met
- All quality gates passing

**User Capability**: Record income and expenses with automatic balance calculations, track transaction vs settlement dates, categorize and tag entries via web and API.

---

### Phase 5: Analytics Dashboard
**Goal**: Provide comprehensive financial insights and projections

**Deliverables**:
- `ReportingService`:
  - Calculate period profit/loss (revenue - expenses)
  - Generate monthly cash flow projections
  - Identify spending patterns by category
  - Track investment returns
  - Alert on accounts at risk (negative balance)
  - Alert on credit card balances vs available funds
  - Unit tests for all calculations
- API endpoints:
  - `/api/v1/reports/period-summary` (with date filters)
  - `/api/v1/reports/cash-flow-projection`
  - `/api/v1/reports/spending-by-category`
  - `/api/v1/reports/investment-returns`
  - `/api/v1/reports/alerts`
- Web Controller for dashboard
- React components:
  - `Dashboard` (main layout)
  - `PeriodSummaryCard` (profit/loss)
  - `CashFlowChart` (projections)
  - `SpendingChart` (by category)
  - `AlertsList` (risk notifications)
  - `InvestmentSummary`
- Charts library integration (e.g., Recharts)
- Jest tests for complex visualizations
- PHPUnit tests for ReportingService
- Static analysis passing
- All quality gates passing

**User Capability**: View real-time financial status, analyze spending patterns, track investment returns, receive alerts for accounts at risk and credit card payment requirements via dashboard.

---

### Phase 6: Bank and Credit Card Reconciliation with OFX Import
**Goal**: Verify transactions against actual statements with automated OFX file import, intelligent fuzzy matching, and comprehensive import history

**Core Features**:
- **OFX File Import**: Upload bank/credit card statements in OFX format with automatic parsing
- **Duplicate Prevention**: SHA-256 hash-based detection with reimportation option
- **Multi-Account Support**: Detect and batch-process multiple accounts from single OFX file
- **Intelligent Matching**: Adaptive Levenshtein distance fuzzy matching (configurable threshold)
- **Background Processing**: Reusable queue-based job infrastructure with individual progress tracking
- **Import History**: Comprehensive audit trail with permanent file retention (gzip compressed)
- **Role-Based Permissions**: Explicit permission system for user and settings management
- **Configurable Settings**: Categorized admin settings with validation and full audit trail

---

#### 6.1 Role-Based Permission System

**Models**:
- `Role` model:
  - `name` (unique: 'admin', 'user')
  - `description`
  - Timestamps
- `Permission` model:
  - `name` (unique: 'manage-users', 'manage-settings', 'view-reports', 'manage-reconciliations')
  - `description`
  - Timestamps
- Pivot tables: `permission_role`, `role_user`
- `HasRoles` trait for User model with methods:
  - `hasPermission(string $permission): bool` - Check explicit permissions only (no inheritance)
  - `assignRole(Role $role): void`
  - `removeRole(Role $role): void`

**Seeding**:
- First registered user automatically assigned 'admin' role
- Admin role has all permissions explicitly assigned
- User role has basic permissions only

**API Endpoints**:
- `GET /api/v1/users` - List users with roles (requires 'manage-users' permission)
- `PUT /api/v1/users/{id}/roles` - Assign/remove roles (requires 'manage-users' permission)
- `GET /api/v1/roles` - List all roles with permissions

**React Components**:
- `UserAdministration` page (permission-gated)
- `RolePermissionMatrix` component showing explicit assignments
- Permission-aware navigation hiding admin features

**Authorization**:
- `UserPolicy` with `viewAny()`, `update()`, `assignRole()` methods
- `CheckPermission` middleware for route protection
- All admin endpoints protected by explicit permission checks

---

#### 6.2 Configurable Settings System with Audit Trail

**Settings Model**:
- `settings` table:
  - `key` (unique)
  - `value` (text)
  - `type` (enum: 'integer', 'float', 'string', 'boolean')
  - `category` (enum: 'import', 'system', 'security', 'matching')
  - `description`
  - `validation_rules` (JSON)
  - `min_value`, `max_value`
  - Timestamps

**Default Settings** (seeded):
- **Import Category**:
  - `ofx_import_retention_days`: 90 (min: 1, max: 365)
  - `max_concurrent_imports_per_user`: 5 (min: 1, max: 20)
- **Matching Category**:
  - `levenshtein_distance_threshold_percent`: 20 (min: 1, max: 100)

**Audit Trail**:
- `setting_changes` table:
  - `setting_key` (foreign key)
  - `old_value`
  - `new_value`
  - `changed_by_user_id` (foreign key to users)
  - `changed_at` (timestamp)

**Setting Model Methods**:
- `getValue(string $key, mixed $default = null): mixed` - Retrieve with caching
- `setValue(string $key, mixed $value): void` - Update with validation
- `recordChange(string $key, mixed $oldValue, mixed $newValue, int $userId): void`
- `validate(string $key, mixed $value): bool` - Check min/max and validation rules

**API Endpoints**:
- `GET /api/v1/settings` - List all settings grouped by category
- `PUT /api/v1/settings/{key}` - Update single setting (requires 'manage-settings' permission)
- `GET /api/v1/settings/{key}/history` - View audit log for specific setting

**React Components**:
- `SettingsManagement` page with category tabs
- Inline validation showing min/max constraints
- Real-time error feedback for invalid values
- `SettingChangeHistory` component per setting

**Validation**:
- `UpdateSettingRequest` enforcing min/max constraints
- Type casting based on `type` field
- JSON validation rules support (regex, enum values)

---

#### 6.3 Reconciliation Core Models

**Reconciliation Model**:
- `reconciliations` table:
  - `account_id` (foreign key)
  - `user_id` (foreign key)
  - `statement_date` (date)
  - `statement_balance` (decimal 15,2)
  - `status` (enum: 'pending', 'completed')
  - `reconciled_at` (timestamp, nullable)
  - `reconciled_by` (foreign key to users, nullable)
  - Soft deletes, timestamps
- Relationships:
  - `belongsTo(Account)`, `belongsTo(User)`, `belongsToMany(Transaction)`
- Methods:
  - `calculateDiscrepancy(): float`
  - `isCompleted(): bool`, `isPending(): bool`
  - Scopes: `pending()`, `completed()`, `forAccount()`

**OFX Import Model**:
- `ofx_imports` table:
  - `filename` (original filename)
  - `file_hash` (SHA-256, indexed)
  - `account_id` (foreign key)
  - `reconciliation_id` (foreign key, nullable)
  - `status` (enum: 'pending', 'processing', 'completed', 'failed')
  - `processed_count` (integer, default 0)
  - `total_count` (integer)
  - `error_message` (text, nullable)
  - `file_path` (path to compressed .ofx.gz file)
  - `user_id` (foreign key)
  - Timestamps
  - Unique index on (`file_hash`, `account_id`, `status`) for duplicate detection
- Relationships:
  - `belongsTo(Account)`, `belongsTo(User)`, `belongsTo(Reconciliation)`
- Methods:
  - `isCompleted(): bool`, `isFailed(): bool`, `isProcessing(): bool`
  - `getProgressPercentage(): float` - Calculate from processed/total counts
  - Scopes: `active()`, `forUser()`, `forAccount()`

**Pivot Table**:
- `reconciliation_transaction`:
  - `reconciliation_id`, `transaction_id`
  - Timestamps
  - Unique constraint preventing duplicate matches

---

#### 6.4 OFX Import Service

**Installation**:
```bash
cd workspace
docker compose exec truetrack composer require asgrim/ofxparser
```

**OfxImportService** (`app/Services/OfxImportService.php`):

**Core Methods**:
- `parseOfxFile(UploadedFile $file): array`
  - Parse OFX using `asgrim/ofxparser` library
  - Extract statement date, closing balance
  - Convert OFX transactions to TrueTrack format
  - Returns: `['statement_date', 'closing_balance', 'transactions']`

- `detectAccounts(UploadedFile $file): array`
  - Identify all bank accounts in OFX file
  - Returns: `[['account_number', 'name', 'type', 'balance'], ...]`
  - Used for multi-account selection prompt

- `compressAndStoreFile(UploadedFile $file): string`
  - Compress file using gzip compression
  - Generate unique filename based on hash
  - Store as `.ofx.gz` in `storage/app/ofx_imports/`
  - Return storage path

- `checkDuplicateImport(string $fileHash, int $accountId): ?OfxImport`
  - Query `ofx_imports` table by hash + account_id
  - Returns existing import record if found within retention period
  - Allows user confirmation for reimportation

- `checkConcurrencyLimit(int $userId): void`
  - Count user's active imports (status: pending, processing)
  - Fetch limit from `Setting::getValue('max_concurrent_imports_per_user')`
  - Throw `TooManyImportsException` if limit exceeded

- `extractTransactions(array $ofxTransactions): array`
  - Map OFX fields to TrueTrack transaction format:
    - `<DTPOSTED>` → `transaction_date`
    - `<TRNAMT>` → `amount` (absolute value)
    - Detect `type`: negative amount → 'debit', positive → 'credit'
    - `<NAME>` or `<PAYEE>` → `description`
    - `<MEMO>` → append to description
    - `<FITID>` → `external_id` (for duplicate detection)
  - Returns array of transaction data

**Unit Tests**:
- `test_can_parse_valid_ofx_file()`
- `test_rejects_invalid_ofx_format()`
- `test_detects_multiple_accounts()`
- `test_compresses_file_successfully()`
- `test_detects_duplicate_import()`
- `test_enforces_concurrency_limit()`
- `test_maps_ofx_transaction_fields_correctly()`
- `test_handles_corrupted_file_gracefully()`

---

#### 6.5 Adaptive Fuzzy Matching Algorithm

**ReconciliationService Enhancements** (`app/Services/ReconciliationService.php`):

**New Method**: `findMatchingTransactionsWithConfidence(int $accountId, float $amount, Carbon $date, string $description): Collection`

**Three-Tier Matching Logic**:

1. **Exact Match (100% confidence)**:
   - Amount matches exactly
   - Date within ±3 days
   - Description identical (case-insensitive)
   - Auto-attach to reconciliation

2. **Strong Match (75% confidence)**:
   - Amount matches exactly
   - Date within ±3 days
   - Levenshtein distance ≤ threshold% of description length
   - Threshold fetched from `Setting::getValue('levenshtein_distance_threshold_percent')` (default: 20%)
   - Calculate: `levenshtein($desc1, $desc2) <= (strlen($desc1) * 0.20)`
   - Suggest to user for review

3. **Weak Match (50% confidence)**:
   - Amount matches exactly
   - Date within ±7 days
   - No description check
   - Suggest to user as possible match

**Return Value**: Collection of `MatchResult` DTOs:
```php
class MatchResult
{
    public Transaction $transaction;
    public int $confidence_score; // 100, 75, or 50
    public string $match_reason; // e.g., "Exact match", "Similar description (Levenshtein: 3 of 15 chars)"
    public ?int $levenshtein_distance; // Null for exact/weak matches
}
```

**Existing Methods** (preserve):
- `createReconciliation(array $data): Reconciliation`
- `addTransaction(Reconciliation $rec, int $txnId): void`
- `removeTransaction(Reconciliation $rec, int $txnId): void`
- `completeReconciliation(Reconciliation $rec, int $userId): Reconciliation`
- `calculateDiscrepancy(Reconciliation $rec): float`
- `performCreditCardClosure(array $data): array`

**Unit Tests**:
- `test_exact_match_returns_100_confidence()`
- `test_strong_match_uses_levenshtein_threshold_from_settings()`
- `test_weak_match_returns_50_confidence()`
- `test_no_match_returns_empty_collection()`
- `test_levenshtein_calculation_accuracy()`
- `test_date_tolerance_boundaries()`
- `test_handles_empty_descriptions()`

---

#### 6.6 Background Job Infrastructure

**Queue Configuration**:
- Add Redis to `config/queue.php`:
  ```php
  'default' => env('QUEUE_CONNECTION', 'redis'),
  'connections' => [
      'redis' => [
          'driver' => 'redis',
          'connection' => 'default',
          'queue' => env('REDIS_QUEUE', 'default'),
          'retry_after' => 90,
          'block_for' => null,
      ],
  ],
  ```

**Docker Compose Updates** (`compose.yaml`):
```yaml
services:
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - truetrack-network

  queue-worker:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan queue:work redis --sleep=3 --tries=1 --max-time=3600
    depends_on:
      - redis
      - truetrack
    volumes:
      - ./workspace:/var/www/html
    networks:
      - truetrack-network

volumes:
  redis-data:
```

**Base Job Class** (`app/Jobs/BaseProcessingJob.php`):
```php
abstract class BaseProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function updateProgress(int $processed, int $total): void
    {
        // Update database record with progress
    }

    protected function markCompleted(): void
    {
        // Set status to 'completed'
    }

    protected function handleFailure(\Throwable $exception): void
    {
        // Log error, set status to 'failed', store error_message
    }

    protected function checkConcurrencyLimit(int $userId): void
    {
        // Validate against max_concurrent_imports_per_user setting
    }

    abstract public function handle(): void;
}
```

**ProcessOfxImport Job** (`app/Jobs/ProcessOfxImport.php`):
```php
class ProcessOfxImport extends BaseProcessingJob
{
    public function __construct(
        public int $ofxImportId,
        public int $accountId,
        public int $userId
    ) {}

    public function handle(): void
    {
        $import = OfxImport::findOrFail($this->ofxImportId);
        $import->update(['status' => 'processing']);

        try {
            // 1. Decompress and parse OFX file
            $ofxData = $this->ofxImportService->parseOfxFile($import->file_path);

            // 2. Create reconciliation
            $reconciliation = $this->reconciliationService->createReconciliation([
                'account_id' => $this->accountId,
                'user_id' => $this->userId,
                'statement_date' => $ofxData['statement_date'],
                'statement_balance' => $ofxData['closing_balance'],
            ]);

            // 3. Match transactions
            $totalTxns = count($ofxData['transactions']);
            foreach ($ofxData['transactions'] as $index => $txn) {
                $matches = $this->reconciliationService->findMatchingTransactionsWithConfidence(...);
                // Auto-attach exact matches, store suggestions for review
                $this->updateProgress($index + 1, $totalTxns);
            }

            // 4. Link reconciliation to import
            $import->update([
                'reconciliation_id' => $reconciliation->id,
                'status' => 'completed',
                'processed_count' => $totalTxns,
            ]);

            $this->markCompleted();
        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }
}
```

**PHPUnit Tests**:
- `test_job_processes_ofx_import_successfully()`
- `test_job_updates_progress_correctly()`
- `test_job_marks_failed_on_exception()`
- `test_job_respects_concurrency_limits()`
- `test_job_creates_reconciliation_and_matches_transactions()`

---

#### 6.7 API Endpoints

**Import Endpoints**:

1. **`POST /api/v1/reconciliations/import-ofx`**
   - **Request**: `ImportOfxRequest`
     - `ofx_file` (required, file, mimes:ofx,qfx, max:5120 KB)
     - No account_id yet (multi-account detection first)
   - **Validation**:
     - Check user's active imports count vs `max_concurrent_imports_per_user`
     - Compute SHA-256 hash, check for duplicates
   - **Process**:
     - Compress and store file
     - Parse and detect accounts
   - **Response**:
     - If 1 account: Dispatch job immediately, return import ID
     - If >1 account: Return account list for user selection
     ```json
     {
       "data": {
         "import_id": 123,
         "accounts": [
           {"account_number": "1234", "name": "Checking", "type": "bank", "balance": 5000.00},
           {"account_number": "5678", "name": "Savings", "type": "bank", "balance": 10000.00}
         ],
         "requires_selection": true
       }
     }
     ```
   - **Status Codes**:
     - 201: Job dispatched (single account)
     - 200: Account selection required (multiple accounts)
     - 409: Duplicate file detected (with reimport option)
     - 429: Concurrency limit exceeded (with retry-after header)

2. **`POST /api/v1/reconciliations/import-ofx/confirm`**
   - **Request**:
     - `import_id` (required, integer)
     - `selected_account_ids[]` (required, array of account numbers from OFX)
   - **Process**:
     - Dispatch separate `ProcessOfxImport` job per selected account
     - Each job processes same file but filters for specific account
   - **Response**:
     ```json
     {
       "data": {
         "jobs_created": 2,
         "import_ids": [124, 125]
       }
     }
     ```

3. **`GET /api/v1/ofx-imports/{id}`**
   - **Purpose**: Poll job status and progress
   - **Response**:
     ```json
     {
       "data": {
         "id": 123,
         "status": "processing",
         "progress_percentage": 65.5,
         "processed_count": 131,
         "total_count": 200,
         "reconciliation_id": 45,
         "error_message": null,
         "created_at": "2026-01-13T10:30:00Z"
       }
     }
     ```

4. **`GET /api/v1/ofx-imports`**
   - **Purpose**: Import history with pagination
   - **Query Parameters**:
     - `filter[account_id]`, `filter[status]`, `filter[date_from]`, `filter[date_to]`
     - `sort` (e.g., `-created_at`)
     - `per_page` (default: 15)
   - **Response**: Paginated collection with metadata

5. **`GET /api/v1/ofx-imports/{id}/download`**
   - **Purpose**: Download stored compressed OFX file for reimportation
   - **Authorization**: User must own the import
   - **Response**: File download (.ofx.gz)

**Existing Reconciliation Endpoints** (preserve):
- `GET /api/v1/reconciliations` - List reconciliations
- `POST /api/v1/reconciliations` - Create manual reconciliation
- `GET /api/v1/reconciliations/{id}` - Show reconciliation details
- `PUT /api/v1/reconciliations/{id}` - Update reconciliation
- `DELETE /api/v1/reconciliations/{id}` - Delete reconciliation
- `POST /api/v1/reconciliations/{id}/transactions` - Add transaction match
- `DELETE /api/v1/reconciliations/{id}/transactions/{txnId}` - Remove match
- `POST /api/v1/reconciliations/{id}/complete` - Mark completed

**Settings Endpoints**:
- `GET /api/v1/settings` - List all settings (grouped by category)
- `PUT /api/v1/settings/{key}` - Update setting (requires 'manage-settings')
- `GET /api/v1/settings/{key}/history` - View audit log

**User Management Endpoints**:
- `GET /api/v1/users` - List users (requires 'manage-users')
- `PUT /api/v1/users/{id}/roles` - Assign roles (requires 'manage-users')
- `GET /api/v1/roles` - List roles with permissions

---

#### 6.8 React Components

**Import Workflow Components**:

1. **`OfxImportForm`** (Main upload interface)
   - File input accepting .ofx, .qfx
   - Display active imports: "3 of 5 imports active"
   - Disable submit when concurrency limit reached
   - Handle duplicate file warnings with reimport confirmation
   - Show upload progress bar

2. **`MultiAccountSelector`** (Modal for account selection)
   - Display when OFX contains >1 account
   - Checkboxes for account selection
   - Show account details (number, name, type, balance)
   - Display: "Will create X separate import jobs"
   - Confirm button dispatches jobs

3. **`ImportProgressList`** (Real-time progress tracking)
   - Uses `useJobProgress` hook for polling
   - Individual progress bar per import job
   - Show account name + processed/total counts
   - Status badges (Processing, Completed, Failed)
   - Expandable error details for failed imports

4. **`TransactionMatchReview`** (Review matched transactions)
   - Display transactions grouped by confidence:
     - ✓ Exact Matches (100%) - Auto-attached, green badge
     - ~ Strong Matches (75%) - Yellow badge with action buttons
     - ? Weak Matches (50%) - Gray badge, user decides
   - Confidence tooltips explaining matching logic:
     - "Strong match (75%): Similar description - Levenshtein distance: 3 of 15 characters different"
   - Action buttons: Accept, Reject, View Details
   - Batch accept/reject functionality

5. **`UnmatchedTransactionForm`** (Import missing transactions)
   - List of unmatched OFX transactions
   - Inline form for each: Category, Tags, Notes
   - Pre-filled with OFX data (date, amount, description)
   - Batch import button: "Import 12 transactions"
   - Individual import option

6. **`OfxImportHistory`** (History page)
   - Paginated table of all imports
   - Columns: Date, Account, Status, Matched/Unmatched Counts, Actions
   - Filters: Account, Status, Date Range
   - Expandable rows showing:
     - Reconciliation link
     - Matched transaction count
     - Unmatched transaction count
     - Error message (if failed)
   - Actions: View Reconciliation, Download File, Reimport
   - Download original .ofx.gz file for reimportation

**Settings Management Components**:

7. **`SettingsManagement`** (Admin settings page)
   - Permission-gated to 'manage-settings'
   - Category tabs: Import Settings, System Settings, Security Settings, Matching Settings
   - Inline editing with validation
   - Show min/max constraints below inputs
   - Real-time validation feedback
   - Save button per setting

8. **`SettingChangeHistory`** (Audit log component)
   - Expandable panel per setting
   - Table showing: Date, Changed By, Old Value, New Value
   - Paginated for settings with many changes

**User Management Components**:

9. **`UserAdministration`** (Admin user management page)
   - Permission-gated to 'manage-users'
   - Paginated user list
   - Columns: Name, Email, Roles, Created Date, Actions
   - Multi-select role dropdown per user
   - Save roles button

10. **`RolePermissionMatrix`** (Visual permission grid)
    - Table: Roles (rows) × Permissions (columns)
    - Checkmarks showing explicit assignments
    - No inheritance indicators (explicit only)
    - Read-only view (roles managed via seeder/config)

**Existing Reconciliation Components** (preserve and enhance):
- `ReconciliationList` - Add OFX import button
- `ReconciliationForm` - Keep for manual reconciliation
- `CreditCardClosure` - Preserve existing functionality
- `DiscrepancyReport` - Preserve existing functionality

**Reusable Components**:

11. **`useJobProgress` Hook** (Polling hook)
    ```jsx
    const { status, progress, data, error } = useJobProgress(importId, {
      interval: 2000, // Poll every 2 seconds
      onComplete: (data) => { /* callback */ },
      onError: (error) => { /* callback */ }
    });
    ```

12. **`JobStatusBadge`** (Status display)
    - Color-coded badges: Pending (gray), Processing (blue), Completed (green), Failed (red)
    - Optional loading spinner for processing state

13. **`ProgressBar`** (Progress visualization)
    - Percentage display
    - Processed/Total counts
    - Smooth animation

---

#### 6.9 Scheduled Commands

**CleanupExpiredOfxImports Command**:
- **Purpose**: Delete old OFX imports and files
- **Schedule**: Daily at 2:00 AM
- **Process**:
  1. Fetch `ofx_import_retention_days` from settings (default: 90)
  2. Query `ofx_imports` where `created_at < now() - retention_days`
  3. Delete compressed .ofx.gz files from storage
  4. Delete database records
  5. Log cleanup summary (files deleted, disk space freed)

**Registration** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('ofx:cleanup-expired')->dailyAt('02:00');
}
```

---

#### 6.10 Testing Strategy

**PHPUnit Tests** (Backend):

**Unit Tests**:
- `OfxImportService`:
  - OFX parsing accuracy
  - Multi-account detection
  - Compression/decompression
  - Duplicate detection
  - Concurrency limit enforcement
- `ReconciliationService`:
  - Exact match logic
  - Levenshtein distance calculation
  - Threshold application from settings
  - Confidence score assignment
- `Setting` model:
  - Validation rules enforcement
  - Min/max constraint checks
  - Type casting
  - Audit trail recording
- `ProcessOfxImport` job:
  - Progress tracking
  - Error handling
  - Reconciliation creation
  - Transaction matching

**Feature Tests** (API):
- Import endpoints:
  - Single account import flow
  - Multi-account selection flow
  - Duplicate file detection
  - Concurrency limit enforcement
  - Progress polling
  - History retrieval with filters
  - File download
- Settings endpoints:
  - Update with validation
  - Unauthorized access denial
  - Audit trail creation
- User management endpoints:
  - Role assignment
  - Permission checks
  - Authorization failures

**Integration Tests**:
- End-to-end OFX import workflow:
  - Upload → Parse → Match → Complete
  - Multi-account batch processing
  - Concurrent imports by multiple users
- Reconciliation with fuzzy matching:
  - Various Levenshtein thresholds
  - Large transaction sets (500+)
- Settings change impact:
  - Update threshold, verify matching behavior
  - Update retention, verify cleanup

**Test Fixtures**:
- `tests/fixtures/valid_statement.ofx` - Typical bank statement
- `tests/fixtures/multi_account.ofx` - Multiple accounts
- `tests/fixtures/credit_card.ofx` - Credit card statement
- `tests/fixtures/invalid.ofx` - Malformed XML
- `tests/fixtures/large.ofx` - 1000+ transactions (performance test)

**Jest Tests** (Frontend):
- `OfxImportForm`:
  - File upload handling
  - Concurrency limit warning
  - Duplicate confirmation dialog
- `MultiAccountSelector`:
  - Account selection state
  - Job count calculation
- `ImportProgressList`:
  - Real-time polling updates
  - Progress bar rendering
- `TransactionMatchReview`:
  - Confidence badge display
  - Tooltip content accuracy
  - Batch accept/reject actions
- `SettingsManagement`:
  - Inline validation
  - Min/max constraint display
  - Error message rendering
- `useJobProgress` hook:
  - Polling behavior
  - Callback invocation
  - Error handling

**Coverage Targets**:
- Services: 85%+ (critical business logic)
- Controllers: 80%+
- Jobs: 80%+
- Models: 75%+
- React Components: 70%+ (focus on logic, not presentational)
- Overall: 75%+

---

#### 6.11 Deliverables Summary

**Database Migrations**:
- `create_reconciliations_table` (with indexes)
- `create_reconciliation_transaction_pivot_table`
- `create_ofx_imports_table` (with hash index, status index)
- `create_roles_table`
- `create_permissions_table`
- `create_permission_role_pivot_table`
- `create_role_user_pivot_table`
- `create_settings_table` (with category index)
- `create_setting_changes_table` (audit trail)

**Models**:
- `Reconciliation` (with relationships, scopes, business methods)
- `OfxImport` (with progress tracking methods)
- `Role` (with permissions relationship)
- `Permission`
- `Setting` (with validation, caching, audit trail)
- `SettingChange` (audit record)
- Updated `User` with `HasRoles` trait

**Services**:
- `OfxImportService` (parsing, compression, validation)
- Enhanced `ReconciliationService` (fuzzy matching algorithm)
- `SettingsService` (centralized config management)

**Jobs**:
- `BaseProcessingJob` (abstract reusable class)
- `ProcessOfxImport` (extends base job)
- `CleanupExpiredOfxImports` (scheduled command)

**Controllers**:
- Enhanced `Api\V1\ReconciliationController`:
  - `importOfx()`, `confirmImport()`, `getImportStatus()`, `getImportHistory()`, `downloadImport()`
- New `Api\V1\SettingController`:
  - `index()`, `update()`, `history()`
- New `Api\V1\UserController`:
  - `index()`, `updateRoles()`

**Form Requests**:
- `ImportOfxRequest` (file validation, concurrency check)
- `ConfirmImportRequest` (account selection validation)
- `UpdateSettingRequest` (validation rules, min/max enforcement)
- `UpdateUserRolesRequest` (role assignment validation)

**API Resources**:
- `ReconciliationResource` (enhanced with OFX import data)
- `OfxImportResource` (status, progress, error details)
- `SettingResource` (with validation metadata)
- `UserResource` (with roles and permissions)
- `MatchResultResource` (confidence scores and match reasons)

**Policies**:
- `ReconciliationPolicy` (preserve existing)
- `OfxImportPolicy` (ownership checks)
- `SettingPolicy` (admin-only access)
- `UserPolicy` (manage-users permission)

**React Components** (15+ components):
- Import Workflow: `OfxImportForm`, `MultiAccountSelector`, `ImportProgressList`, `TransactionMatchReview`, `UnmatchedTransactionForm`, `OfxImportHistory`
- Settings: `SettingsManagement`, `SettingChangeHistory`
- User Management: `UserAdministration`, `RolePermissionMatrix`
- Reusable: `JobStatusBadge`, `ProgressBar`, `useJobProgress` hook
- Existing (enhanced): `ReconciliationList`, `ReconciliationForm`, `CreditCardClosure`, `DiscrepancyReport`

**Seeders**:
- `RoleSeeder` (admin, user roles with explicit permissions)
- `PermissionSeeder` (manage-users, manage-settings, etc.)
- `SettingSeeder` (all default configuration values)
- `ReconciliationSeeder` (test data)
- `OfxImportSeeder` (test data)

**Factories**:
- `ReconciliationFactory`
- `OfxImportFactory`
- `RoleFactory`
- `PermissionFactory`
- `SettingFactory`

**Tests**:
- PHPUnit: 50+ tests across unit, feature, and integration
- Jest: 20+ tests for complex React components
- Test fixtures: 5+ OFX sample files
- Coverage: 75%+ overall, 85%+ for services

**Documentation Updates**:
- API documentation (endpoints, request/response examples)
- User guide (OFX import workflow, settings management)
- Admin guide (user management, role assignment)
- Developer guide (background job patterns, reusable components)

**Infrastructure**:
- Redis configuration for queues
- Queue worker service in Docker Compose
- File storage configuration (gzip compression)
- Scheduled command registration

---

#### 6.12 User Capabilities

After Phase 6 completion, users will be able to:

**Reconciliation**:
- ✅ Upload OFX/QFX bank and credit card statement files
- ✅ Automatically parse and extract transactions from OFX files
- ✅ Select specific accounts when OFX contains multiple accounts
- ✅ Track import progress in real-time with individual job monitoring
- ✅ Review intelligently matched transactions with confidence scores
- ✅ Understand matching logic via detailed tooltips (Levenshtein distance explanation)
- ✅ Accept or reject suggested transaction matches
- ✅ Import unmatched transactions with category/tag assignment
- ✅ View comprehensive import history with filters
- ✅ Download stored OFX files for reimportation
- ✅ Perform manual reconciliations (existing functionality preserved)
- ✅ Calculate and review discrepancies
- ✅ Complete reconciliations and lock matched transactions
- ✅ Execute credit card monthly closures (existing functionality)

**Administration** (Admin users only):
- ✅ Manage user roles and permissions
- ✅ Assign/remove admin privileges
- ✅ Configure system settings via UI (retention days, concurrency limits, matching thresholds)
- ✅ View full audit trail of setting changes
- ✅ Validate setting values with min/max constraints

**System Benefits**:
- ✅ Prevent duplicate OFX imports via hash detection
- ✅ Reimport historical statements when needed
- ✅ Concurrent processing of multiple imports (up to configured limit)
- ✅ Automatic cleanup of old imports (configurable retention period)
- ✅ Permanent file retention with gzip compression (storage optimized)
- ✅ Reusable background job infrastructure for future features (CSV import, bulk operations)
- ✅ Explicit role-based permissions (no inheritance complexity)
- ✅ Centralized configuration management with validation

---

**Phase 6 Status**: Ready for implementation with comprehensive OFX import integration

---

## Database Schema Overview

### Core Tables
- `users` - User accounts (from Breeze)
- `accounts` - Financial accounts (bank, credit card, wallet, transitional) with initial_balance
- `account_balances` - Monthly balance snapshots for performance optimization
- `categories` - Revenue/Expense categories (hierarchical)
- `tags` - Flexible transaction grouping
- `transactions` - Financial entries with personal finance logic (credits increase, debits decrease)
- `reconciliations` - Bank/credit card statement reconciliation records

### Pivot Tables
- `reconciliation_transaction` - Links transactions to reconciliations
- `tag_transaction` - Many-to-many (transactions and tags)

### Key Constraints
- Foreign keys with cascading rules
- Check constraints for accounting integrity
- Unique indexes for performance
- Soft deletes for audit trail

---

## API Design Principles

### Versioning
- Base path: `/api/v1`
- Version in URL (not headers)
- Maintain backward compatibility within version

### Response Format
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Filtering & Sorting
- Query parameters: `?filter[type]=bank&sort=-created_at`
- Cursor pagination for large datasets
- Field selection: `?fields=id,name,balance`

### Authentication
- Web: Session-based (Laravel Breeze)
- API: Token-based (Sanctum)
- Scopes for granular permissions (future)

---

## Testing Strategy

### Backend (PHPUnit)
- **Unit Tests**: Services, models, business logic
- **Feature Tests**: API endpoints, Inertia responses
- **Integration Tests**: Multi-model operations (accounting, reconciliation)
- **Database**: RefreshDatabase trait, factories
- **Coverage Target**: 80%+ for services, 70%+ overall

### Frontend (Jest)
- **Component Tests**: Complex forms (AccountForm, TransactionForm)
- **Logic Tests**: Calculations, validations
- **Integration Tests**: Form submission flows
- **Coverage Target**: Focus on business logic, not presentational components

### CI/CD Pipeline
```yaml
# .github/workflows/tests.yml
- PostgreSQL service container
- PHP 8.2+ with extensions
- Node.js for frontend
- Composer install
- NPM install & build
- Database migrations
- PHPUnit execution
- Jest execution
- Laravel Pint check
- PHPStan/Larastan analysis
- Coverage report generation
- Quality gates enforcement (with manual bypass option)
```

---

## Future Considerations

### Post-Phase 6 Features
1. **AWS Deployment**
   - Create `.github/workflows/deploy.yml`
   - Aurora PostgreSQL Serverless v2 setup
   - EC2 or Fargate for application
   - S3 for file storage
   - CloudFront CDN

2. **Multi-Currency Support**
   - Add currency fields to accounts/transactions
   - Exchange rate tracking
   - Multi-currency reports

3. **Mobile Application**
   - React Native app
   - Consume existing `/api/v1` endpoints
   - Offline support with sync

4. **Advanced Features**
   - Recurring transactions
   - Budget planning
   - Financial goals tracking
   - Import from banks (OFX/CSV)
   - Export reports (PDF, Excel)
   - Multi-tenancy (family accounts)

5. **Performance Optimization**
   - Redis for caching and queues
   - Database query optimization
   - Background job processing
   - CDN for static assets

6. **Monitoring & Observability**
   - Laravel Telescope (local)
   - Error tracking (Sentry/Bugsnag)
   - Application performance monitoring
   - Database query monitoring

---

## Development Workflow

### Branch Strategy
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/*` - Individual features
- `hotfix/*` - Critical fixes

### PR Process
1. Create feature branch from `develop`
2. Implement feature with tests
3. Run local quality checks
4. Push and create PR to `develop`
5. GitHub Actions runs all checks
6. Code review required
7. All quality gates must pass (or manual bypass)
8. Merge to `develop`
9. Periodic releases to `main`

### Commit Conventions
- `feat:` New feature
- `fix:` Bug fix
- `test:` Test additions/modifications
- `refactor:` Code restructuring
- `docs:` Documentation updates
- `chore:` Maintenance tasks

---

## Getting Started

### Prerequisites
- Docker Desktop
- Git
- Composer
- Node.js 20+

### Initial Setup (Phase 1)
```bash
# Clone repository
git clone <repo-url>
cd truetrack/workspace

# Install dependencies
docker compose run --rm truetrack composer install
docker compose run --rm truetrack npm install

# Start Docker containers
docker compose up -d

# Set up environment
cp .env.example .env
docker compose exec truetrack php artisan key:generate

# Run migrations
docker compose exec truetrack php artisan migrate

# Build frontend assets
docker compose exec truetrack npm run build

# For development, watch frontend changes
docker compose exec truetrack npm run dev

# Configure GitHub Actions
# (See Phase 1 deliverables)
```

---

## Maintenance Notes

- Database backups: Daily automated backups in production
- Security updates: Monthly dependency updates
- Performance review: Quarterly optimization sprints
- User feedback: Continuous collection and prioritization

---

**Last Updated**: January 7, 2026
**Status**: Planning Phase
**Next Phase**: Phase 1 - Project Foundation
