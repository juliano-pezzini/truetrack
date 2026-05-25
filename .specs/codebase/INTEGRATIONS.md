# External Integrations

## File Parsing

### OFX/QFX Parser

**Service:** `asgrim/ofxparser`
**Purpose:** Parse OFX/QFX bank statement files into structured transaction data
**Implementation:** `app/Services/OfxImportService.php`
**Usage:** Reads uploaded OFX files, extracts transactions with dates, amounts, descriptions, and bank references

### XLSX/CSV Parser

**Service:** `maatwebsite/excel` (Laravel Excel)
**Purpose:** Parse XLSX and CSV spreadsheet files with flexible column mapping
**Implementation:** `app/Services/XlsxImportService.php`
**Usage:** Header detection, column mapping suggestions, row extraction, template generation, error reporting

## Authentication

### Session Auth (Web)

**Service:** Laravel Breeze
**Purpose:** Registration, login, password reset, email verification for web UI
**Implementation:** `app/Http/Controllers/Auth/` (Breeze scaffolded controllers)
**Configuration:** Session-based, CSRF protection via `VerifyCsrfToken` middleware

### Token Auth (API)

**Service:** Laravel Sanctum
**Purpose:** Token-based authentication for API endpoints
**Implementation:** `auth:sanctum` middleware on `/api/v1` routes
**Configuration:** `config/sanctum.php`, personal access tokens in `personal_access_tokens` table

## Background Jobs

**Queue system:** Database driver (worker container), Redis default in config
**Worker:** Dedicated Docker container `queue-worker` running `php artisan queue:work database --queue=default --tries=3 --timeout=300 --sleep=3`

### ProcessOfxImport

**Location:** `app/Jobs/ProcessOfxImport.php`
**Purpose:** Background processing of OFX file imports
**Extends:** `BaseProcessingJob` (shared progress tracking, failure handling, concurrency limits)
**Config:** 3 retries, 300s timeout

### ProcessXlsxImport

**Location:** `app/Jobs/ProcessXlsxImport.php`
**Purpose:** Background processing of XLSX/CSV file imports with column mapping
**Extends:** `BaseProcessingJob`
**Config:** 3 retries, 300s timeout

### BaseProcessingJob

**Location:** `app/Jobs/BaseProcessingJob.php`
**Purpose:** Abstract base with shared import infrastructure
**Features:** Progress tracking (`updateProgress`), completion marking (`markCompleted`), failure handling (`handleFailure`), concurrency limit checking via `Setting` model

## Scheduled Tasks

**Location:** `routes/console.php`

| Command | Schedule | Purpose |
| --- | --- | --- |
| `ofx:cleanup-expired` | Daily at 02:00 | Remove expired/orphaned OFX import records |
| `xlsx:cleanup-expired` | Daily at 02:00 | Remove expired/orphaned XLSX import records |
| `xlsx:cleanup-hashes` | Weekly (Sunday 03:00) | Purge old transaction hash records |

**Command implementations:** `app/Console/Commands/CleanupExpiredOfxImports.php`, `CleanupExpiredXlsxImports.php`, `CleanupOldTransactionHashes.php`

## File Storage

**Configuration:** `config/filesystems.php`
**Default disk:** `local` (storage_path `app/private`)
**Usage:** Uploaded OFX/XLSX files stored locally, XLSX files compressed as `.gz` via `XlsxImportService::compressAndStoreFile()`

## Settings System

**Models:** `Setting`, `SettingChange`
**Purpose:** Application-level configuration (e.g., `max_concurrent_imports_per_user`)
**Implementation:** Key-value store with change audit trail

## Caching

**Service:** Redis 7 (Docker container)
**Configuration:** `config/cache.php`
**Usage:** Cache store, session store (production); array driver in tests
