# Phase 6.1-6.5: OFX/QFX Import with Fuzzy Matching

**Status**: Planned  
**Estimated Effort**: 2-3 weeks  
**Complexity**: High (fuzzy matching algorithm, background jobs)

---

## Overview

Implement comprehensive OFX/QFX bank statement import with intelligent transaction matching using Levenshtein distance calculation, role-based permissions system, configurable settings, and background job processing.

For complete architectural overview, see [PHASE_06_STATEMENT_IMPORT.md](PHASE_06_STATEMENT_IMPORT.md).

---

## Core Features

### 1. Role-Based Permission System
- `Role` model with explicit permissions (no inheritance)
- `Permission` model with standard permissions
- Pivot relationships: `permission_role`, `role_user`
- First user registered = admin with all permissions
- New users = basic user permissions only
- **Permissions**: manage-users, manage-settings, view-reports, manage-reconciliations

### 2. Configurable Settings with Audit Trail
- `Setting` model with JSON validation rules
- `SettingChange` model for audit trail
- Categories: import, system, security, matching
- **Default Settings**:
  - `ofx_import_retention_days`: 90
  - `max_concurrent_imports_per_user`: 5
  - `levenshtein_distance_threshold_percent`: 20

### 3. OFX Import Service
- **Installation**: `composer require asgrim/ofxparser`
- **Core Methods**:
  - `parseOfxFile()` - Parse OFX using asgrim/ofxparser library
  - `detectAccounts()` - Identify all accounts in OFX file
  - `compressAndStoreFile()` - Gzip compression for storage
  - `checkDuplicateImport()` - SHA-256 hash-based duplicate detection
  - `checkConcurrencyLimit()` - Enforce max imports per user
  - `extractTransactions()` - Map OFX fields to TrueTrack format

### 4. Reconciliation Core Models
- `Reconciliation` model for statement reconciliation
- `OfxImport` model for import tracking and progress
- Pivot table: `reconciliation_transaction` for matches
- Unique indexes for duplicate detection

### 5. Fuzzy Matching Algorithm
- **Three-Tier Matching Logic**:
  1. **Exact Match (100%)**: Amount exact, date ±3 days, description identical
  2. **Strong Match (75%)**: Amount exact, date ±3 days, Levenshtein ≤ threshold%
  3. **Weak Match (50%)**: Amount exact, date ±7 days, no description check
- **Return**: `MatchResult` DTO with confidence_score, match_reason, levenshtein_distance
- Threshold configurable via `Setting::getValue('levenshtein_distance_threshold_percent')`

### 6. Background Job Infrastructure
- **Installation**: Redis configuration in `compose.yaml`
- `BaseProcessingJob` abstract class with:
  - `updateProgress()` - Database progress tracking
  - `markCompleted()` - Set status to completed
  - `handleFailure()` - Log error and set status to failed
  - `checkConcurrencyLimit()` - Validate concurrent import limit
- `ProcessOfxImport` job extends `BaseProcessingJob`
  - Decompress OFX file
  - Create reconciliation
  - Match transactions with confidence scoring
  - Update import record with results

---

## Database Schema

### Roles & Permissions
```sql
-- roles table
CREATE TABLE roles (
    id BIGINT PRIMARY KEY, name VARCHAR(255) UNIQUE, description TEXT,
    created_at TIMESTAMP, updated_at TIMESTAMP
);

-- permissions table  
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY, name VARCHAR(255) UNIQUE, description TEXT,
    created_at TIMESTAMP, updated_at TIMESTAMP
);

-- permission_role pivot (explicit, no inheritance)
CREATE TABLE permission_role (
    permission_id BIGINT, role_id BIGINT, PRIMARY KEY (permission_id, role_id)
);

-- role_user pivot
CREATE TABLE role_user (
    role_id BIGINT, user_id BIGINT, PRIMARY KEY (role_id, user_id)
);
```

### Settings & Audit
```sql
-- settings table
CREATE TABLE settings (
    id BIGINT PRIMARY KEY, key VARCHAR(255) UNIQUE, value TEXT,
    type ENUM('integer', 'float', 'string', 'boolean'),
    category ENUM('import', 'system', 'security', 'matching'),
    description TEXT, validation_rules JSON,
    min_value DECIMAL(15,2), max_value DECIMAL(15,2),
    created_at TIMESTAMP, updated_at TIMESTAMP,
    INDEX idx_category (category)
);

-- setting_changes audit trail
CREATE TABLE setting_changes (
    id BIGINT PRIMARY KEY, setting_key VARCHAR(255),
    old_value TEXT, new_value TEXT,
    changed_by_user_id BIGINT, changed_at TIMESTAMP,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);
```

### Reconciliation & Imports
```sql
-- reconciliations table
CREATE TABLE reconciliations (
    id BIGINT PRIMARY KEY, account_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL, statement_date DATE,
    statement_balance DECIMAL(15,2), status ENUM('pending', 'completed'),
    reconciled_at TIMESTAMP, reconciled_by BIGINT,
    created_at TIMESTAMP, updated_at TIMESTAMP, deleted_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_account_id (account_id), INDEX idx_status (status)
);

-- ofx_imports table
CREATE TABLE ofx_imports (
    id BIGINT PRIMARY KEY, filename VARCHAR(255),
    file_hash VARCHAR(64), account_id BIGINT NOT NULL,
    reconciliation_id BIGINT, user_id BIGINT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed'),
    processed_count INT DEFAULT 0, total_count INT,
    error_message TEXT, file_path VARCHAR(255),
    created_at TIMESTAMP, updated_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE INDEX idx_file_hash_account (file_hash, account_id, status)
);

-- reconciliation_transaction pivot
CREATE TABLE reconciliation_transaction (
    id BIGINT PRIMARY KEY, reconciliation_id BIGINT,
    transaction_id BIGINT, created_at TIMESTAMP,
    FOREIGN KEY (reconciliation_id) REFERENCES reconciliations(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    UNIQUE INDEX idx_unique_match (reconciliation_id, transaction_id)
);
```

---

## API Endpoints

### Import Workflow

**`POST /api/v1/reconciliations/import-ofx`**
```json
Request:
{
  "ofx_file": <file>
}

Response (201 or 200):
{
  "data": {
    "import_id": 123,
    "accounts": [{...}],
    "requires_selection": true
  }
}
```

**`POST /api/v1/reconciliations/import-ofx/confirm`**
```json
Request:
{
  "import_id": 123,
  "selected_account_ids": ["1234", "5678"]
}

Response (201):
{
  "data": {
    "jobs_created": 2,
    "import_ids": [124, 125]
  }
}
```

**`GET /api/v1/ofx-imports/{id}`** - Poll job status
**`GET /api/v1/ofx-imports`** - Import history with pagination  
**`GET /api/v1/ofx-imports/{id}/download`** - Download stored OFX file

### Settings Endpoints

**`GET /api/v1/settings`** - List all settings  
**`PUT /api/v1/settings/{key}`** - Update setting (requires manage-settings)  
**`GET /api/v1/settings/{key}/history`** - View audit log

### User Management

**`GET /api/v1/users`** - List users (requires manage-users)  
**`PUT /api/v1/users/{id}/roles`** - Assign roles (requires manage-users)  
**`GET /api/v1/roles`** - List roles with permissions

---

## React Components

### Import Workflow
1. **`OfxImportForm`** - Main upload interface with file input and account selector
2. **`MultiAccountSelector`** - Modal for selecting which accounts to import
3. **`ImportProgressList`** - Real-time polling progress for active imports
4. **`TransactionMatchReview`** - Review matched transactions by confidence tier
5. **`UnmatchedTransactionForm`** - Import unmatched OFX transactions
6. **`OfxImportHistory`** - Paginated import history table

### Reusable Components
- **`useJobProgress`** hook - Polling hook for job status
- **`JobStatusBadge`** - Status badge component
- **`ProgressBar`** - Progress visualization

### Settings Management
- **`SettingsManagement`** - Admin settings page with tabs
- **`SettingChangeHistory`** - Audit log per setting

### User Management
- **`UserAdministration`** - User list with role assignment
- **`RolePermissionMatrix`** - Visual permission grid

---

## Form Requests

### ImportOfxRequest
```php
'ofx_file' => 'required|file|mimes:ofx,qfx|max:5120',
// Plus concurrency limit check and duplicate detection
```

### ConfirmImportRequest
```php
'import_id' => 'required|integer|exists:ofx_imports,id',
'selected_account_ids' => 'required|array',
```

### UpdateSettingRequest
```php
// Enforcement of min/max constraints based on setting type
```

---

## Testing

### PHPUnit Tests (45+ tests)
- **OFX Service**: Parsing, compression, account detection, duplicate checking, concurrency
- **OFX Job**: Progress tracking, reconciliation creation, transaction matching, error handling
- **Reconciliation Service**: Fuzzy matching, Levenshtein calculation, confidence scoring
- **Settings**: Validation, auditing, caching
- **API Endpoints**: All import endpoints, settings endpoints, user management

### Jest Tests (12+ tests)
- **OfxImportForm**: File upload, duplicate warning, concurrency feedback
- **MultiAccountSelector**: Account selection, job creation
- **ImportProgressList**: Real-time updates, progress display
- **TransactionMatchReview**: Confidence badges, tooltips, batch actions
- **SettingsManagement**: Validation, inline editing, error feedback

### Test Fixtures
- `tests/fixtures/valid_statement.ofx` - Standard bank statement
- `tests/fixtures/multi_account.ofx` - Multiple accounts
- `tests/fixtures/credit_card.ofx` - Credit card statement
- `tests/fixtures/large.ofx` - 1000+ transactions

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass, 45+)
- ✅ Jest tests (all tests pass, 12+)
- ✅ Laravel Pint (no violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 85%+ for services, 80%+ overall

---

## User Capabilities

✅ Upload OFX/QFX bank and credit card statements  
✅ Automatically parse and extract transactions  
✅ Select specific accounts when OFX contains multiple  
✅ Track import progress in real-time with individual job monitoring  
✅ Review intelligently matched transactions with confidence scores  
✅ Understand matching logic via detailed tooltips  
✅ Accept or reject suggested matches  
✅ Import unmatched transactions  
✅ View comprehensive import history with filters  
✅ Download stored OFX files for reimportation  
✅ Perform manual reconciliations  
✅ Calculate and review discrepancies  
✅ Complete reconciliations and lock matched transactions  

---

## Key Implementation Notes

- **Database Transactions**: Atomic operations for consistency
- **Row Locking**: During balance updates
- **Concurrency Control**: Via settings-based limits
- **File Storage**: Gzip compressed in `storage/app/ofx_imports/`
- **Duplicate Prevention**: SHA-256 file hash with user confirmation for reimport
- **Progress Tracking**: Database-backed progress, not in-memory
- **Error Handling**: Graceful failures with detailed logging

---

## Previous Phase

[Phase 5: Analytics Dashboard](PHASE_05_ANALYTICS_DASHBOARD.md)

## Related Documentation

- [Phase 6: Statement Import Overview](PHASE_06_STATEMENT_IMPORT.md)
- [Phase 6.6-6.13: XLSX/CSV Import](PHASE_06_XLSX_CSV_IMPORT.md)

---

**Created**: January 30, 2026  
**Last Updated**: January 30, 2026
