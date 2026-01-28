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

#### 6.13 XLSX/CSV Import with Flexible Column Mapping

**Goal**: Enable users to import transactions from spreadsheet files (XLSX, CSV) with flexible column mapping, reusing the existing background job infrastructure, reconciliation matching, and progress tracking from OFX import.

**Core Features**:
- **Flexible Column Mapping**: User-defined mapping of spreadsheet columns to transaction fields
- **Smart Detection**: Automatic column header recognition with intelligent suggestions
- **Template Generation**: Downloadable XLSX template with required/optional columns
- **Row-Level Duplicate Detection**: Hash-based detection (date+amount+description) with skip option
- **Error Handling**: Skip invalid rows, continue processing, provide downloadable error report
- **Saved Mappings**: Auto-save column configuration per account for repeat imports
- **Background Processing**: Reuses `BaseProcessingJob` infrastructure with progress tracking
- **Reconciliation Integration**: Optional fuzzy matching to existing transactions

---

##### 6.13.1 XLSX Column Specifications

**Required Transaction Fields** (must be mapped):
- **Transaction Date** - Date of transaction (formats: YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY)
- **Description/Memo** - Transaction description
- **Amount Detection** - One of three strategies:
  - **Strategy A**: Single "Amount" column (negative = debit, positive = credit)
  - **Strategy B**: Separate "Debit" and "Credit" columns (populate one per row)
  - **Strategy C**: "Amount" + "Type" columns (Type values: "debit", "credit", "expense", "income")

**Optional Transaction Fields** (can be mapped):
- **Category** - Category name (matched by name or created if not exists)
- **Settled Date** - Date when transaction settled (nullable)
- **Tags** - Comma-separated tag names (e.g., "vacation, travel, hotel")

**Account-Level Information** (specified during import, not per-row):
- **Account Selection** - User selects existing account OR creates new account
- **Statement Date** - Date of bank statement (for reconciliation creation)
- **Statement Balance** - Closing balance from statement (for discrepancy calculation)

**Template Column Names** (standardized):
```
Transaction Date | Description | Amount | Debit | Credit | Type | Category | Settled Date | Tags
```

**Template Download**:
- Users can download `.xlsx` template with:
  - First row: Column headers
  - Second row: Example data
  - Third row: Format notes (e.g., "YYYY-MM-DD or MM/DD/YYYY")
  - Columns marked as "(Required)" or "(Optional)" in header comments

---

##### 6.13.2 Database Schema

**XlsxImport Model**:
- `xlsx_imports` table (mirrors `ofx_imports` structure):
  - `filename` (original filename)
  - `file_hash` (SHA-256 of entire file, indexed)
  - `account_id` (foreign key)
  - `reconciliation_id` (foreign key, nullable)
  - `status` (enum: 'pending', 'processing', 'completed', 'failed')
  - `processed_count` (integer, default 0) - Successfully imported rows
  - `total_count` (integer) - Total rows to process
  - `skipped_count` (integer, default 0) - Rows skipped due to validation errors
  - `duplicate_count` (integer, default 0) - Rows skipped as duplicates
  - `error_message` (text, nullable) - General error message for failed imports
  - `error_report_path` (text, nullable) - Path to downloadable error CSV
  - `file_path` (path to compressed .xlsx.gz file)
  - `user_id` (foreign key)
  - `column_mapping_id` (foreign key, nullable) - Reference to saved mapping used
  - Timestamps
  - Unique index on (`file_hash`, `account_id`, `status`) for file-level duplicate detection
- Relationships:
  - `belongsTo(Account)`, `belongsTo(User)`, `belongsTo(Reconciliation)`
  - `belongsTo(XlsxColumnMapping, 'column_mapping_id')`
- Methods:
  - `isCompleted(): bool`, `isFailed(): bool`, `isProcessing(): bool`
  - `getProgressPercentage(): float` - Calculate from processed/total counts
  - `hasErrors(): bool` - Check if error_report_path exists
  - Scopes: `active()`, `forUser()`, `forAccount()`

**XlsxColumnMapping Model**:
- `xlsx_column_mappings` table (saved mapping configurations):
  - `user_id` (foreign key)
  - `account_id` (foreign key, nullable) - Mapping can be account-specific
  - `name` (string) - User-friendly name (e.g., "Chase Bank Format")
  - `mapping_config` (JSON) - Column mapping configuration:
    ```json
    {
      "date_column": "Transaction Date",
      "description_column": "Description",
      "amount_strategy": "single_column",
      "amount_column": "Amount",
      "debit_column": null,
      "credit_column": null,
      "type_column": null,
      "category_column": "Category",
      "settled_date_column": null,
      "tags_column": "Tags",
      "date_format": "auto"
    }
    ```
  - `is_default` (boolean) - Default mapping for this user/account
  - `last_used_at` (timestamp) - Track usage for sorting
  - Timestamps
  - Indexes: `user_id`, `account_id`, `is_default`
- Relationships:
  - `belongsTo(User)`, `belongsTo(Account)`
  - `hasMany(XlsxImport)`
- Methods:
  - `markAsUsed(): void` - Update `last_used_at`
  - `setAsDefault(): void` - Set `is_default` to true, clear others
  - Scopes: `forUser()`, `forAccount()`, `defaults()`

**XlsxTransactionHash Table** (row-level duplicate detection):
- `xlsx_transaction_hashes` table:
  - `user_id` (foreign key)
  - `account_id` (foreign key)
  - `row_hash` (SHA-256 of date+amount+description, indexed)
  - `transaction_id` (foreign key, nullable) - Link to created transaction
  - `imported_at` (timestamp)
  - Unique index on (`user_id`, `account_id`, `row_hash`)
- Purpose: Prevent duplicate imports of same transaction across multiple XLSX imports
- Cleanup: Delete hashes older than 1 year (scheduled command)

---

##### 6.13.3 XLSX Import Service

**Installation**:
```bash
cd workspace
docker compose exec truetrack composer require maatwebsite/excel
```

**XlsxImportService** (`app/Services/XlsxImportService.php`):

**Core Methods**:

1. **`parseXlsxFile(UploadedFile $file): array`**
   - Use `Maatwebsite\Excel` to read spreadsheet
   - Detect first row with headers (skip empty rows)
   - Returns: `['headers' => [...], 'row_count' => N, 'preview_rows' => [...]]`
   - Preview rows: First 5 data rows for user review

2. **`detectHeaders(UploadedFile $file): array`**
   - Find first non-empty row as headers
   - Returns array of column names
   - Handle Excel date columns (convert serial dates to readable format)

3. **`guessColumnMapping(array $headers): array`**
   - Smart heuristics to suggest mapping:
     - "Date", "Transaction Date", "Trans Date" → `transaction_date`
     - "Description", "Memo", "Details" → `description`
     - "Amount", "Total" → `amount_column` (check for negative values to suggest single_column strategy)
     - "Debit", "Withdrawal" → `debit_column`
     - "Credit", "Deposit" → `credit_column`
     - "Type", "Transaction Type" → `type_column`
     - "Category" → `category_column`
     - "Settled", "Posted Date" → `settled_date_column`
     - "Tags", "Labels" → `tags_column`
   - Returns suggested `mapping_config` JSON
   - Confidence score per mapping (100% = exact match, 75% = partial match)

4. **`validateMapping(array $mappingConfig, array $headers): array`**
   - Ensure required fields are mapped:
     - `date_column` must exist
     - `description_column` must exist
     - Amount strategy must have corresponding columns:
       - `single_column`: `amount_column` required
       - `separate_columns`: `debit_column` AND `credit_column` required
       - `type_column`: `amount_column` AND `type_column` required
   - Return validation errors array or empty if valid

5. **`previewWithMapping(UploadedFile $file, array $mappingConfig): array`**
   - Apply mapping to first 5 rows
   - Return transformed data:
     ```php
     [
       ['transaction_date' => '2026-01-15', 'amount' => 50.00, 'type' => 'debit', 'description' => 'Groceries', ...],
       // ... 4 more rows
     ]
     ```
   - Include validation warnings per row (e.g., "Invalid date format", "Amount not numeric")

6. **`extractTransactionFromRow(array $row, array $mappingConfig): array`**
   - Map single row to transaction data structure:
     ```php
     [
       'transaction_date' => Carbon::parse($row[$mappingConfig['date_column']]),
       'description' => $row[$mappingConfig['description_column']],
       'amount' => abs($amount),
       'type' => $this->detectType($row, $mappingConfig),
       'category_name' => $row[$mappingConfig['category_column']] ?? null,
       'settled_date' => $row[$mappingConfig['settled_date_column']] ?? null,
       'tags' => $this->parseTags($row[$mappingConfig['tags_column']] ?? ''),
     ]
     ```
   - Validate each field (date parsing, numeric amount, required fields)
   - Throw `InvalidRowDataException` with row number and field errors

7. **`detectType(array $row, array $mappingConfig): string`**
   - Implement type detection based on strategy:
     - **single_column**: Check if amount is negative → 'debit', else 'credit'
     - **separate_columns**: Check which column has value (debit_column → 'debit', credit_column → 'credit')
     - **type_column**: Map type value (case-insensitive):
       - "debit", "expense", "withdrawal" → 'debit'
       - "credit", "income", "deposit" → 'credit'
   - Throw exception if type cannot be determined

8. **`calculateRowHash(Carbon $date, float $amount, string $description): string`**
   - Generate SHA-256 hash: `hash('sha256', $date->format('Y-m-d') . '|' . $amount . '|' . strtolower(trim($description)))`
   - Used for row-level duplicate detection

9. **`checkRowDuplicate(int $userId, int $accountId, string $rowHash): ?int`**
   - Query `xlsx_transaction_hashes` table
   - Returns `transaction_id` if duplicate found, null otherwise

10. **`compressAndStoreFile(UploadedFile $file): string`**
    - Compress file using gzip compression
    - Store as `.xlsx.gz` in `storage/app/xlsx_imports/`
    - Return storage path

11. **`generateTemplate(): string`**
    - Create XLSX file with template structure:
      - Row 1: Headers (with comments marking required/optional)
      - Row 2: Example data
      - Row 3: Format hints
    - Return download path
    - Template cached for 1 hour (regenerate daily)

12. **`generateErrorReport(array $errors): string`**
    - Create CSV file with error details:
      - Columns: Row Number, Field, Error Message, Raw Value
    - Store in `storage/app/xlsx_imports/errors/`
    - Return path for download
    - Example errors:
      ```csv
      Row Number,Field,Error Message,Raw Value
      5,transaction_date,Invalid date format,13/32/2025
      12,amount,Amount must be numeric,ABC
      18,type,Cannot determine transaction type,
      ```

**Unit Tests**:
- `test_can_parse_valid_xlsx_file()`
- `test_detects_headers_correctly()`
- `test_guesses_column_mapping_accurately()`
- `test_validates_mapping_config()`
- `test_rejects_missing_required_fields()`
- `test_previews_with_mapping_applied()`
- `test_extracts_transaction_from_row()`
- `test_detects_type_from_single_column_strategy()`
- `test_detects_type_from_separate_columns_strategy()`
- `test_detects_type_from_type_column_strategy()`
- `test_calculates_row_hash_consistently()`
- `test_detects_duplicate_rows()`
- `test_compresses_file_successfully()`
- `test_generates_template_with_correct_structure()`
- `test_generates_error_report_csv()`
- `test_handles_invalid_xlsx_format()`
- `test_handles_empty_rows()`
- `test_parses_comma_separated_tags()`

---

##### 6.13.4 Background Job

**ProcessXlsxImport Job** (`app/Jobs/ProcessXlsxImport.php`):

Extends `BaseProcessingJob` (reusing progress tracking, error handling, concurrency checks).

```php
class ProcessXlsxImport extends BaseProcessingJob
{
    public function __construct(
        public int $xlsxImportId,
        public int $accountId,
        public int $userId,
        public array $mappingConfig,
        public bool $createReconciliation = false,
        public ?string $statementDate = null,
        public ?float $statementBalance = null
    ) {}

    public function handle(): void
    {
        $import = XlsxImport::findOrFail($this->xlsxImportId);
        $import->update(['status' => 'processing']);

        $errors = [];
        $processedCount = 0;
        $skippedCount = 0;
        $duplicateCount = 0;

        try {
            // 1. Decompress and parse XLSX file
            $rows = $this->xlsxImportService->parseXlsxFileRows($filePath, $this->mappingConfig);
            $import->update(['total_count' => count($rows)]);

            // 2. Create reconciliation if requested
            $reconciliation = null;
            if ($this->createReconciliation) {
                $reconciliation = $this->reconciliationService->createReconciliation([...]);
            }

            // 3. Process each row
            foreach ($rows as $index => $row) {
                try {
                    // Extract transaction data
                    $txnData = $this->xlsxImportService->extractTransactionFromRow($row, $this->mappingConfig);

                    // Check row-level duplicate
                    $rowHash = $this->xlsxImportService->calculateRowHash(...);
                    $existingTxnId = $this->xlsxImportService->checkRowDuplicate(...);

                    if ($existingTxnId) {
                        $duplicateCount++;
                        continue; // Skip duplicate
                    }

                    // Create transaction
                    $transaction = $this->accountingService->createTransaction([...]);

                    // Attach tags
                    if (!empty($txnData['tags'])) {
                        $tagIds = $this->resolveTagIds($txnData['tags']);
                        $transaction->tags()->sync($tagIds);
                    }

                    // Store hash for future duplicate detection
                    DB::table('xlsx_transaction_hashes')->insert([...]);

                    // Fuzzy match to reconciliation if exists
                    if ($reconciliation) {
                        $matches = $this->reconciliationService->findMatchingTransactionsWithConfidence(...);
                        // Auto-attach exact matches (100% confidence)
                    }

                    $processedCount++;

                } catch (InvalidRowDataException $e) {
                    // Log error, skip row, continue
                    $errors[] = [...];
                    $skippedCount++;
                }

                // Update progress every 10 rows
                if ($processedCount % 10 === 0) {
                    $this->updateProgress($processedCount, count($rows), $skippedCount, $duplicateCount);
                }
            }

            // 4. Generate error report if errors exist
            $errorReportPath = !empty($errors) ? $this->xlsxImportService->generateErrorReport($errors) : null;

            // 5. Update import record
            $import->update([
                'reconciliation_id' => $reconciliation?->id,
                'status' => 'completed',
                'processed_count' => $processedCount,
                'skipped_count' => $skippedCount,
                'duplicate_count' => $duplicateCount,
                'error_report_path' => $errorReportPath,
            ]);

            $this->markCompleted();

        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    private function resolveCategoryId(?string $categoryName): ?int
    {
        if (!$categoryName) return null;
        $category = Category::firstOrCreate(
            ['user_id' => $this->userId, 'name' => $categoryName],
            ['type' => 'expense']
        );
        return $category->id;
    }

    private function resolveTagIds(array $tagNames): array
    {
        return collect($tagNames)->map(function ($name) {
            $tag = Tag::firstOrCreate(
                ['user_id' => $this->userId, 'name' => trim($name)],
                ['color' => $this->randomTagColor()]
            );
            return $tag->id;
        })->toArray();
    }
}
```

**PHPUnit Tests**:
- `test_job_processes_xlsx_import_successfully()`
- `test_job_updates_progress_correctly()`
- `test_job_skips_invalid_rows_and_continues()`
- `test_job_detects_and_skips_duplicate_rows()`
- `test_job_creates_reconciliation_when_requested()`
- `test_job_matches_transactions_with_fuzzy_matching()`
- `test_job_creates_categories_if_not_exist()`
- `test_job_attaches_tags_correctly()`
- `test_job_generates_error_report_for_invalid_rows()`
- `test_job_marks_failed_on_exception()`
- `test_job_respects_concurrency_limits()`

---

##### 6.13.5 API Endpoints

**XLSX Import Endpoints**:

1. **`POST /api/v1/xlsx-imports/detect-columns`**
   - **Request**: `DetectXlsxColumnsRequest`
     - `xlsx_file` (required, file, mimes:xlsx,csv, max:10240 KB)
   - **Process**:
     - Parse file, detect headers
     - Guess column mapping with confidence scores
     - Return preview of first 5 rows
   - **Response**:
     ```json
     {
       "data": {
         "headers": ["Date", "Description", "Amount", "Category"],
         "suggested_mapping": {
           "date_column": "Date",
           "description_column": "Description",
           "amount_strategy": "single_column",
           "amount_column": "Amount",
           "category_column": "Category"
         },
         "confidence_scores": {
           "date_column": 100,
           "description_column": 100,
           "amount_column": 100,
           "category_column": 100
         },
         "preview_rows": [
           ["2026-01-15", "Groceries", "-50.00", "Food"],
           ["2026-01-16", "Salary", "2000.00", "Income"]
         ],
         "total_rows": 150
       }
     }
     ```

2. **`POST /api/v1/xlsx-imports/preview`**
   - **Request**: `PreviewXlsxImportRequest`
     - `xlsx_file` (required, file)
     - `mapping_config` (required, JSON object)
   - **Process**:
     - Apply mapping to first 5 rows
     - Validate data (show warnings)
   - **Response**:
     ```json
     {
       "data": {
         "preview_transactions": [
           {
             "transaction_date": "2026-01-15",
             "description": "Groceries",
             "amount": 50.00,
             "type": "debit",
             "category": "Food",
             "warnings": []
           }
         ],
         "validation_summary": {
           "valid_rows": 1,
           "rows_with_warnings": 1
         }
       }
     }
     ```

3. **`POST /api/v1/xlsx-imports/store`**
   - **Request**: `StoreXlsxImportRequest`
     - `xlsx_file` (required, file, mimes:xlsx,csv, max:10240 KB)
     - `account_id` (required, integer, exists:accounts,id)
     - `mapping_config` (required, JSON object)
     - `save_mapping` (boolean, default: true) - Auto-save mapping
     - `mapping_name` (string, required if save_mapping=true)
     - `create_reconciliation` (boolean, default: false)
     - `statement_date` (date, required if create_reconciliation=true)
     - `statement_balance` (numeric, required if create_reconciliation=true)
   - **Validation**:
     - Check user's active imports count vs `max_concurrent_imports_per_user`
     - Compute SHA-256 hash, check for file-level duplicates (optional warning)
     - Validate mapping config has required fields
   - **Process**:
     - Compress and store file
     - Save column mapping if requested
     - Create `XlsxImport` record
     - Dispatch `ProcessXlsxImport` job
   - **Response**:
     ```json
     {
       "data": {
         "import_id": 123,
         "status": "pending",
         "total_rows": 150,
         "message": "Import queued for processing"
       }
     }
     ```
   - **Status Codes**:
     - 201: Job dispatched
     - 429: Concurrency limit exceeded
     - 422: Validation errors (invalid mapping, missing required fields)

4. **`GET /api/v1/xlsx-imports`**
   - **Purpose**: Import history with pagination
   - **Query Parameters**:
     - `filter[account_id]`, `filter[status]`, `filter[date_from]`, `filter[date_to]`
     - `sort` (e.g., `-created_at`)
     - `per_page` (default: 15)
   - **Response**: Paginated collection with metadata

5. **`GET /api/v1/xlsx-imports/{id}`**
   - **Purpose**: Poll job status and progress
   - **Response**:
     ```json
     {
       "data": {
         "id": 123,
         "filename": "bank_statement_jan.xlsx",
         "status": "processing",
         "progress_percentage": 65.5,
         "processed_count": 98,
         "total_count": 150,
         "skipped_count": 2,
         "duplicate_count": 5,
         "reconciliation_id": 45,
         "has_errors": true,
         "error_message": null,
         "created_at": "2026-01-13T10:30:00Z"
       }
     }
     ```

6. **`GET /api/v1/xlsx-imports/{id}/download`**
   - **Purpose**: Download stored compressed XLSX file
   - **Authorization**: User must own the import
   - **Response**: File download (.xlsx.gz)

7. **`GET /api/v1/xlsx-imports/{id}/error-report`**
   - **Purpose**: Download error report CSV for imports with skipped rows
   - **Authorization**: User must own the import
   - **Response**: CSV file download with error details

8. **`GET /api/v1/xlsx-imports/template`**
   - **Purpose**: Download XLSX template with required/optional columns
   - **Response**: XLSX file download with example data and format hints

**Column Mapping Endpoints**:

9. **`GET /api/v1/xlsx-column-mappings`**
   - **Purpose**: List saved column mappings for user
   - **Query Parameters**:
     - `filter[account_id]` (optional) - Filter by account
     - `sort` (default: `-last_used_at`)
   - **Response**:
     ```json
     {
       "data": [
         {
           "id": 1,
           "name": "Chase Bank Format",
           "account_id": 5,
           "mapping_config": {...},
           "is_default": true,
           "last_used_at": "2026-01-20T10:30:00Z"
         }
       ]
     }
     ```

10. **`POST /api/v1/xlsx-column-mappings`**
    - **Purpose**: Manually save column mapping
    - **Request**:
      - `name` (required, string, max: 100)
      - `account_id` (nullable, integer)
      - `mapping_config` (required, JSON object)
      - `is_default` (boolean, default: false)
    - **Response**: Created mapping resource

11. **`PUT /api/v1/xlsx-column-mappings/{id}`**
    - **Purpose**: Update saved mapping
    - **Request**: Same as POST
    - **Response**: Updated mapping resource

12. **`DELETE /api/v1/xlsx-column-mappings/{id}`**
    - **Purpose**: Delete saved mapping
    - **Response**: 204 No Content

---

##### 6.13.6 React Components

**XLSX Import Workflow Components**:

1. **`XlsxImportUpload`** (Main upload and mapping interface)
   - **Features**:
     - File upload input (.xlsx, .csv)
     - Account selector (existing accounts) or "Create New Account" option
     - Display active imports: "3 of 5 imports active"
     - Disable submit when concurrency limit reached
     - "Download Template" button (calls `/api/v1/xlsx-imports/template`)
   - **Workflow**:
     - Step 1: Select account
     - Step 2: Upload file → Auto-detect columns
     - Step 3: Review/adjust column mapping (delegates to `XlsxColumnMapper`)
     - Step 4: Preview data (delegates to `XlsxPreviewTable`)
     - Step 5: Confirm and import
   - **Props**: `onImportStarted(importId)`

2. **`XlsxColumnMapper`** (Column mapping configuration)
   - **Features**:
     - Display detected spreadsheet headers
     - Dropdown per header to map to TrueTrack fields:
       - Transaction Date (required)
       - Description (required)
       - Amount / Debit / Credit (required)
       - Type (optional)
       - Category (optional)
       - Settled Date (optional)
       - Tags (optional)
       - "Not Used" option
     - Radio buttons for type detection strategy:
       - Single column (amount sign)
       - Separate columns (debit + credit)
       - Type column
     - Show confidence badges (100% = exact match, 75% = suggested)
     - "Load Saved Mapping" dropdown (shows user's saved mappings)
     - "Save Mapping" checkbox with name input
     - "Auto-detect" button to rerun smart detection
   - **Validation**:
     - Highlight missing required fields in red
     - Disable "Next" button until valid
   - **Props**: `headers`, `suggestedMapping`, `onMappingConfirmed(mappingConfig)`

3. **`XlsxPreviewTable`** (Preview mapped data)
   - **Features**:
     - Show first 5 rows with mapping applied
     - Columns: Transaction Date, Description, Amount, Type, Category, Tags
     - Warning badges for validation issues (invalid date, non-numeric amount)
     - "Looks good? Import N rows" confirmation
     - "Back to mapping" button
   - **Props**: `previewData`, `validationSummary`, `onConfirm()`, `onBack()`

4. **`XlsxImportProgress`** (Reuse from OFX, enhanced)
   - **Enhancements** (additional to OFX progress):
     - Show skipped rows count: "Processed: 98/150 | Skipped: 2 | Duplicates: 5"
     - "Download Error Report" button (appears if `has_errors === true`)
     - Link to error report: `/api/v1/xlsx-imports/{id}/error-report`
   - **Reuse**: Uses existing `useJobProgress` hook for polling
   - **Props**: `importId`

5. **`XlsxImportHistory`** (History page)
   - **Features**:
     - Paginated table of all XLSX imports
     - Columns: Date, Filename, Account, Status, Processed/Skipped/Duplicates, Actions
     - Filters: Account, Status, Date Range
     - Expandable rows showing:
       - Reconciliation link (if created)
       - Processed/Skipped/Duplicate counts
       - Error report download (if errors exist)
     - Actions: View Details, Download File, Download Errors, Reimport
   - **Props**: None (fetches data on mount)

6. **`SavedMappingSelector`** (Reusable dropdown)
   - **Features**:
     - Dropdown showing user's saved mappings
     - Sort by last used date
     - Show account name (if account-specific)
     - "Default" badge for default mappings
     - "Load" button per mapping
   - **Props**: `accountId`, `onMappingSelected(mappingConfig)`

7. **`ReconciliationOptionsPanel`** (Optional reconciliation during import)
   - **Features**:
     - Checkbox: "Create reconciliation from this import"
     - Conditional fields (shown if checkbox enabled):
       - Statement Date (date picker)
       - Statement Balance (numeric input)
     - Info tooltip: "Transactions will be automatically matched to existing entries"
   - **Props**: `onReconciliationConfigChanged(config)`

**Reusable Components** (shared with OFX):
- `ImportProgressList` - Polling progress for multiple imports
- `JobStatusBadge` - Status badges (Pending, Processing, Completed, Failed)
- `ProgressBar` - Visual progress indicator
- `useJobProgress` - Polling hook for job status

**Integration with Existing Components**:
- **`ImportHistory` Page** - Unified page showing both OFX and XLSX imports
  - Tabs: "All Imports", "OFX Imports", "XLSX Imports"
  - Combined table with "Type" column (OFX vs XLSX)
  - Type-specific action buttons (download original file, download errors for XLSX)

---

##### 6.13.7 Form Requests

1. **`DetectXlsxColumnsRequest`**
   - Validation:
     - `xlsx_file`: required, file, mimes:xlsx,xls,csv, max:10240 (10MB)
   - Authorization: User must be authenticated

2. **`PreviewXlsxImportRequest`**
   - Validation:
     - `xlsx_file`: required, file, mimes:xlsx,xls,csv, max:10240
     - `mapping_config`: required, array
     - `mapping_config.date_column`: required, string
     - `mapping_config.description_column`: required, string
     - `mapping_config.amount_strategy`: required, in:single_column,separate_columns,type_column
     - (Conditional validations based on strategy)

3. **`StoreXlsxImportRequest`**
   - Validation:
     - `xlsx_file`: required, file, mimes:xlsx,xls,csv, max:10240
     - `account_id`: required, integer, exists:accounts,id, owned by user
     - `mapping_config`: required, array, validated via `validateMapping()`
     - `save_mapping`: boolean
     - `mapping_name`: required_if:save_mapping,true, string, max:100
     - `create_reconciliation`: boolean
     - `statement_date`: required_if:create_reconciliation,true, date
     - `statement_balance`: required_if:create_reconciliation,true, numeric
   - Custom validation:
     - Check concurrency limit via `XlsxImportService::checkConcurrencyLimit()`
     - Validate mapping config via `XlsxImportService::validateMapping()`
   - Authorization: User must own the account

4. **`StoreXlsxColumnMappingRequest`**
   - Validation:
     - `name`: required, string, max:100
     - `account_id`: nullable, integer, exists:accounts,id, owned by user
     - `mapping_config`: required, array, validated via `validateMapping()`
     - `is_default`: boolean

---

##### 6.13.8 Settings

**New Settings** (added to `settings` table via seeder):

- **Import Category**:
  - `xlsx_import_retention_days`: 90 (min: 1, max: 365)
  - `max_xlsx_rows_per_import`: 5000 (min: 1, max: 50000) - Prevent memory issues
  - `xlsx_duplicate_detection`: 'row_level' (enum: 'file_level', 'row_level', 'disabled')

- **System Category**:
  - `xlsx_transaction_hash_retention_days`: 365 (min: 30, max: 1095) - How long to keep hashes for duplicate detection

**Reused Settings**:
- `max_concurrent_imports_per_user`: 5 (shared with OFX)
- `levenshtein_distance_threshold_percent`: 20 (shared for fuzzy matching)

---

##### 6.13.9 Scheduled Commands

**CleanupExpiredXlsxImports Command** (`app/Console/Commands/CleanupExpiredXlsxImports.php`):
- **Purpose**: Delete old XLSX imports and files
- **Schedule**: Daily at 2:00 AM
- **Process**:
  1. Fetch `xlsx_import_retention_days` from settings (default: 90)
  2. Query `xlsx_imports` where `created_at < now() - retention_days`
  3. Delete compressed .xlsx.gz files from storage
  4. Delete error report CSV files from storage
  5. Delete database records
  6. Log cleanup summary

**CleanupOldTransactionHashes Command** (`app/Console/Commands/CleanupOldTransactionHashes.php`):
- **Purpose**: Delete old transaction hashes to prevent table bloat
- **Schedule**: Weekly (Sundays at 3:00 AM)
- **Process**:
  1. Fetch `xlsx_transaction_hash_retention_days` from settings (default: 365)
  2. Delete `xlsx_transaction_hashes` where `imported_at < now() - retention_days`
  3. Log rows deleted

**Registration** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('ofx:cleanup-expired')->dailyAt('02:00');
    $schedule->command('xlsx:cleanup-expired')->dailyAt('02:00');
    $schedule->command('xlsx:cleanup-hashes')->weeklyOn(0, '03:00'); // Sunday 3am
}
```

---

##### 6.13.10 Testing Strategy

**PHPUnit Tests** (Backend):

**Unit Tests** (`XlsxImportService`):
- `test_can_parse_valid_xlsx_file()`
- `test_can_parse_csv_file()`
- `test_detects_headers_correctly()`
- `test_skips_empty_rows_before_headers()`
- `test_guesses_column_mapping_with_exact_matches()`
- `test_guesses_column_mapping_with_partial_matches()`
- `test_validates_mapping_config_for_required_fields()`
- `test_rejects_invalid_amount_strategy()`
- `test_previews_with_single_column_strategy()`
- `test_previews_with_separate_columns_strategy()`
- `test_previews_with_type_column_strategy()`
- `test_extracts_transaction_from_row_successfully()`
- `test_handles_invalid_date_formats()`
- `test_handles_non_numeric_amounts()`
- `test_detects_type_from_negative_amount()`
- `test_detects_type_from_debit_credit_columns()`
- `test_detects_type_from_type_column_values()`
- `test_calculates_row_hash_consistently()`
- `test_detects_duplicate_rows_by_hash()`
- `test_parses_comma_separated_tags()`
- `test_compresses_xlsx_file_successfully()`
- `test_generates_template_with_required_columns()`
- `test_generates_error_report_csv_correctly()`
- `test_handles_corrupted_xlsx_file()`
- `test_handles_empty_xlsx_file()`

**Unit Tests** (`ProcessXlsxImport` Job):
- `test_job_processes_xlsx_import_successfully()`
- `test_job_updates_progress_every_10_rows()`
- `test_job_skips_invalid_rows_and_continues()`
- `test_job_logs_errors_for_invalid_rows()`
- `test_job_detects_and_skips_duplicate_rows()`
- `test_job_tracks_skipped_and_duplicate_counts()`
- `test_job_creates_reconciliation_when_requested()`
- `test_job_matches_transactions_with_fuzzy_matching()`
- `test_job_creates_categories_if_not_exist()`
- `test_job_attaches_tags_from_comma_separated_string()`
- `test_job_generates_error_report_for_skipped_rows()`
- `test_job_marks_completed_with_summary()`
- `test_job_marks_failed_on_exception()`
- `test_job_respects_max_rows_per_import_setting()`

**Feature Tests** (API Endpoints):
- `test_can_detect_columns_from_uploaded_xlsx()`
- `test_suggests_column_mapping_with_confidence_scores()`
- `test_can_preview_import_with_mapping_applied()`
- `test_shows_validation_warnings_in_preview()`
- `test_can_create_xlsx_import_with_valid_mapping()`
- `test_rejects_import_with_invalid_mapping()`
- `test_enforces_concurrency_limit_for_xlsx_imports()`
- `test_auto_saves_column_mapping_when_requested()`
- `test_can_create_reconciliation_during_import()`
- `test_can_list_xlsx_imports_with_filters()`
- `test_can_poll_import_status_and_progress()`
- `test_can_download_imported_xlsx_file()`
- `test_can_download_error_report_csv()`
- `test_can_download_xlsx_template()`
- `test_can_list_saved_column_mappings()`
- `test_can_create_column_mapping()`
- `test_can_update_column_mapping()`
- `test_can_delete_column_mapping()`
- `test_unauthorized_user_cannot_access_others_imports()`

**Integration Tests**:
- `test_end_to_end_xlsx_import_workflow()`
  - Upload → Detect → Map → Preview → Import → Poll → Complete
- `test_import_with_saved_mapping_reuse()`
- `test_import_with_reconciliation_and_fuzzy_matching()`
- `test_concurrent_imports_by_same_user()`
- `test_large_xlsx_file_import_performance()` (1000+ rows)
- `test_csv_file_import_workflow()`
- `test_duplicate_row_detection_across_multiple_imports()`
- `test_error_handling_with_partially_valid_file()`
- `test_settings_impact_on_import_behavior()`
  - Update `max_xlsx_rows_per_import`, verify rejection
  - Update `xlsx_duplicate_detection`, verify behavior

**Jest Tests** (Frontend):
- `XlsxImportUpload`:
  - `test_uploads_file_and_triggers_column_detection()`
  - `test_shows_concurrency_limit_warning()`
  - `test_downloads_template_on_button_click()`
- `XlsxColumnMapper`:
  - `test_displays_detected_headers()`
  - `test_shows_suggested_mapping_with_confidence_badges()`
  - `test_allows_manual_column_mapping_adjustments()`
  - `test_validates_required_fields_before_next_step()`
  - `test_loads_saved_mapping_on_selection()`
  - `test_shows_save_mapping_option()`
- `XlsxPreviewTable`:
  - `test_displays_preview_rows_with_mapped_data()`
  - `test_shows_validation_warnings()`
  - `test_confirms_import_on_button_click()`
- `XlsxImportProgress`:
  - `test_polls_job_status_in_real_time()`
  - `test_displays_skipped_and_duplicate_counts()`
  - `test_shows_download_error_report_button_when_errors_exist()`
- `XlsxImportHistory`:
  - `test_displays_paginated_import_list()`
  - `test_filters_by_account_and_status()`
  - `test_downloads_original_file_on_action_click()`
  - `test_downloads_error_report_on_action_click()`
- `SavedMappingSelector`:
  - `test_displays_saved_mappings_for_user()`
  - `test_loads_mapping_on_selection()`

**Test Fixtures**:
- `tests/fixtures/valid_statement.xlsx` - Standard format (Date, Description, Amount)
- `tests/fixtures/debit_credit_format.xlsx` - Separate debit/credit columns
- `tests/fixtures/type_column_format.xlsx` - Explicit type column
- `tests/fixtures/with_tags_and_categories.xlsx` - All optional columns
- `tests/fixtures/invalid_dates.xlsx` - Rows with invalid date formats
- `tests/fixtures/non_numeric_amounts.xlsx` - Rows with text in amount column
- `tests/fixtures/large_import.xlsx` - 1000+ rows for performance testing
- `tests/fixtures/empty_rows.xlsx` - File with empty rows between data
- `tests/fixtures/valid_statement.csv` - CSV version of standard format

**Coverage Targets**:
- `XlsxImportService`: 90%+ (complex mapping logic)
- `ProcessXlsxImport` job: 85%+
- Controllers: 80%+
- React Components: 70%+ (focus on logic)
- Overall: 75%+

---

##### 6.13.11 Deliverables Summary

**Database Migrations**:
- `create_xlsx_imports_table` (mirrors `ofx_imports` with additional fields)
- `create_xlsx_column_mappings_table` (saved mapping configurations)
- `create_xlsx_transaction_hashes_table` (row-level duplicate detection)

**Models**:
- `XlsxImport` (progress tracking, error handling, relationships)
- `XlsxColumnMapping` (mapping persistence, default handling)
- No dedicated model for `xlsx_transaction_hashes` (direct DB access)

**Services**:
- `XlsxImportService` (parsing, column detection, mapping, validation, template generation)

**Jobs**:
- `ProcessXlsxImport` (extends `BaseProcessingJob`, row-by-row processing)
- `CleanupExpiredXlsxImports` (scheduled cleanup)
- `CleanupOldTransactionHashes` (scheduled cleanup)

**Controllers**:
- `Api\V1\XlsxImportController`:
  - `detectColumns()`, `preview()`, `store()`, `index()`, `show()`, `download()`, `errorReport()`, `template()`
- `Api\V1\XlsxColumnMappingController`:
  - `index()`, `store()`, `update()`, `destroy()`

**Form Requests**:
- `DetectXlsxColumnsRequest` (file validation)
- `PreviewXlsxImportRequest` (mapping validation)
- `StoreXlsxImportRequest` (comprehensive validation, concurrency check)
- `StoreXlsxColumnMappingRequest` (mapping persistence validation)

**API Resources**:
- `XlsxImportResource` (status, progress, error details)
- `XlsxColumnMappingResource` (mapping configuration, usage metadata)

**Policies**:
- `XlsxImportPolicy` (ownership checks, authorization)

**React Components** (8 new components):
- `XlsxImportUpload` - Main import interface
- `XlsxColumnMapper` - Column mapping configuration
- `XlsxPreviewTable` - Preview mapped data
- `XlsxImportProgress` - Enhanced progress tracking
- `XlsxImportHistory` - Import history page
- `SavedMappingSelector` - Reusable mapping selector
- `ReconciliationOptionsPanel` - Optional reconciliation config
- **Enhanced `ImportHistory`** - Unified OFX + XLSX history page

**Seeders**:
- `XlsxImportSeeder` (test data)
- `XlsxColumnMappingSeeder` (example mappings)
- Updated `SettingSeeder` (XLSX-specific settings)

**Factories**:
- `XlsxImportFactory`
- `XlsxColumnMappingFactory`

**Tests**:
- PHPUnit: 40+ tests (unit, feature, integration)
- Jest: 15+ tests (component logic, user interactions)
- Test fixtures: 9 XLSX/CSV sample files
- Coverage: 75%+ overall, 90%+ for `XlsxImportService`

**Documentation Updates**:
- API documentation (new endpoints, request/response examples)
- User guide (XLSX import workflow, column mapping, template usage)
- Developer guide (column mapping architecture, duplicate detection strategy)

**Infrastructure**:
- Laravel Excel / PhpSpreadsheet dependency
- Storage configuration (xlsx_imports directory, error reports)
- Scheduled command registration (cleanup jobs)

---

##### 6.13.12 User Capabilities

After XLSX import implementation, users will be able to:

**XLSX/CSV Import**:
- ✅ Upload XLSX or CSV files (up to 10MB, 5000 rows)
- ✅ Download standardized XLSX template with example data
- ✅ Automatically detect spreadsheet column headers
- ✅ Review smart column mapping suggestions with confidence scores
- ✅ Manually adjust column mappings via intuitive UI
- ✅ Choose transaction type detection strategy (amount sign, separate columns, type column)
- ✅ Preview first 5 rows with mapping applied before importing
- ✅ Save column mappings for future imports (account-specific or global)
- ✅ Load previously saved mappings with one click
- ✅ Import transactions with categories and tags (auto-created if needed)
- ✅ Optionally create reconciliation during import with fuzzy matching
- ✅ Track import progress in real-time (processed/skipped/duplicates)
- ✅ Automatically skip duplicate rows (based on date+amount+description hash)
- ✅ Continue import when encountering invalid rows (skip and log errors)
- ✅ Download detailed error report (CSV) showing row numbers and validation issues
- ✅ View comprehensive import history with filters (account, status, date)
- ✅ Download original imported XLSX files for reference
- ✅ Reimport files when needed

**System Benefits**:
- ✅ Prevent duplicate transaction imports via row-level hashing
- ✅ Handle malformed data gracefully (skip invalid rows, continue processing)
- ✅ Reuse column mappings across imports (saved per account or globally)
- ✅ Support multiple spreadsheet formats (XLSX, CSV)
- ✅ Automatic category and tag creation during import
- ✅ Reconciliation integration with fuzzy matching (same as OFX)
- ✅ Unified import history (OFX + XLSX in single interface)
- ✅ Configurable retention policies (auto-cleanup old imports and hashes)
- ✅ Memory-efficient processing (chunked reading for large files)
- ✅ Detailed error reporting for troubleshooting

---

**Phase 6 Status**: Ready for implementation with comprehensive OFX + XLSX import integration

---

## Database Schema Overview

### 1. Authentication & Authorization
- **`users`** - User accounts and authentication (Laravel Breeze)
  - Relationships: Has roles, created imports, transactions, and reconciliations
- **`roles`** - User role definitions (admin, user)
  - Relationships: BelongsToMany users (via role_user), permissions (via permission_role)
- **`permissions`** - Granular permission definitions (manage-users, manage-settings, etc.)
  - Relationships: BelongsToMany roles (via permission_role)
- **`permission_role`** (pivot) - Role-permission assignments (explicit, no inheritance)
- **`role_user`** (pivot) - User-role assignments

### 2. Financial Core
- **`accounts`** - Financial accounts (bank, credit_card, wallet, transitional)
  - Stores initial_balance (never modified after creation)
  - Relationships: Has balances, transactions, imports, reconciliations
- **`account_balances`** - Monthly balance snapshots for performance optimization
  - One record per account per month with closing_balance
  - Enables fast balance calculations and historical tracking
  - Relationships: Belongs to account
- **`categories`** - Hierarchical revenue/expense categories
  - Self-referential (parent_id for hierarchy)
  - Relationships: Has transactions, has child categories
- **`tags`** - Flexible transaction grouping and labeling
  - Relationships: BelongsToMany transactions (via tag_transaction)
- **`transactions`** - Transaction records with personal finance logic
  - Type: 'credit' (increases balance) or 'debit' (decreases balance)
  - Relationships: Belongs to account, category; has tags, reconciliations
- **`tag_transaction`** (pivot) - Transaction-tag associations (many-to-many)

### 3. Reconciliation
- **`reconciliations`** - Bank/credit card statement reconciliation records
  - Tracks statement dates, balances, and discrepancies
  - Status: pending, in_progress, completed
  - Relationships: Belongs to account, user; has matched transactions
- **`reconciliation_transaction`** (pivot) - Transaction-reconciliation links
  - Includes confidence scores (100%, 75%, 50%) for fuzzy matching

### 4. Import Management
- **`ofx_imports`** - OFX/QFX file import tracking and status
  - File hash (SHA-256) for duplicate detection
  - Progress tracking: processed_count, total_count
  - Status: pending, processing, completed, failed
  - Compressed file storage (.ofx.gz)
  - Relationships: Belongs to account, user, reconciliation (optional)
- **`xlsx_imports`** - XLSX/CSV file import tracking and status
  - Mirrors ofx_imports structure with additional fields
  - Row-level duplicate detection support
  - Tracks skipped_count, duplicate_count
  - Error report generation (downloadable CSV)
  - Relationships: Belongs to account, user, reconciliation (optional), column_mapping (optional)
- **`xlsx_column_mappings`** - Saved spreadsheet column mapping configurations
  - User-defined or account-specific mappings
  - Stores mapping_config (JSON) for reuse
  - Default mapping support, usage tracking (last_used_at)
  - Relationships: Belongs to user, account (optional); has xlsx_imports
- **`xlsx_transaction_hashes`** - Row-level duplicate detection for XLSX imports
  - SHA-256 hash of (date + amount + description) per account
  - Prevents duplicate imports across multiple files
  - Unique index on (user_id, account_id, row_hash)
  - Automatic cleanup via scheduled command (1-year retention)

### 5. System Configuration
- **`settings`** - Configurable system settings with validation rules
  - Categories: import, system, security, matching
  - Includes min/max constraints, type casting, default values
  - Examples: retention days, concurrency limits, matching thresholds
  - Relationships: Has change history (setting_changes)
- **`setting_changes`** - Settings change audit trail
  - Tracks who changed what setting, when, and old/new values
  - Relationships: Belongs to setting, user (changed_by)

### Key Database Characteristics
- **Foreign Keys**: All relationships use cascading rules (delete, update) where appropriate
- **Unique Indexes**: File hashes, transaction hashes, user identifiers for performance
- **Check Constraints**: Enforce accounting integrity (e.g., non-negative amounts)
- **Soft Deletes**: Audit trail support on core tables (users, accounts, transactions, categories, tags)
- **Status Enums**: Standardized status values (pending, processing, completed, failed)
- **Timestamps**: All tables include created_at, updated_at for audit purposes

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
