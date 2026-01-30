# Phase 6: Bank and Credit Card Reconciliation with Statement Import

**Goal**: Verify transactions against actual statements with automated file import (OFX, XLSX, CSV), intelligent fuzzy matching, and comprehensive import history

**Status**: Planned (Sub-phases: OFX, XLSX/CSV, Extended Features)

**Estimated Effort**: 4-6 weeks (includes OFX + XLSX)

---

## Overview

Implement comprehensive bank and credit card statement reconciliation with support for multiple import formats (OFX, QFX, XLSX, CSV). Includes role-based permissions, configurable settings, fuzzy matching with Levenshtein distance, and background job processing.

This is the largest phase, split into detailed sub-phase documentation:
- [Phase 6.1: Role-Based Permissions & Settings](PHASE_06_OFX_IMPORT.md#61-role-based-permission-system) (6.1 section of OFX guide)
- [Phase 6.2-6.5: OFX Import Details](PHASE_06_OFX_IMPORT.md) (see dedicated file)
- [Phase 6.6-6.10: XLSX/CSV Import Details](PHASE_06_XLSX_CSV_IMPORT.md) (see dedicated file)

---

## Phase 6 Core Deliverables

### Foundation Components (All Sub-phases)

**Models**
- `Role` - User role definitions
- `Permission` - Granular permissions
- `Setting` - Configurable system settings
- `SettingChange` - Audit trail for settings
- `Reconciliation` - Bank/credit card statement reconciliation
- `OfxImport` - OFX file import tracking
- `XlsxImport` - XLSX/CSV file import tracking
- `XlsxColumnMapping` - Saved column mapping configurations
- Pivot tables: `permission_role`, `role_user`, `reconciliation_transaction`, `tag_transaction`, `xlsx_transaction_hashes`

**Services**
- `OfxImportService` - OFX parsing, compression, validation (detailed in [PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md))
- `XlsxImportService` - XLSX/CSV parsing, mapping, validation (detailed in [PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md))
- `ReconciliationService` - Enhanced with fuzzy matching
- `SettingsService` - Centralized configuration management

**Jobs**
- `BaseProcessingJob` - Abstract reusable background job base class
- `ProcessOfxImport` - OFX file processing job (detailed in [PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md))
- `ProcessXlsxImport` - XLSX/CSV file processing job (detailed in [PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md))
- `CleanupExpiredOfxImports` - Scheduled cleanup command
- `CleanupExpiredXlsxImports` - Scheduled cleanup command
- `CleanupOldTransactionHashes` - Scheduled hash cleanup

**Controllers**
- `ImportController` - **SINGLE unified import page** at `/imports` (handles all file types)
- `Api\V1\ReconciliationController` - Enhanced with import endpoints
- `Api\V1\SettingController` - Settings management
- `Api\V1\UserController` - User management with role assignment
- `Api\V1\OfxImportController` - OFX import API (detailed in [PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md))
- `Api\V1\XlsxImportController` - XLSX import API (detailed in [PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md))
- `Api\V1\XlsxColumnMappingController` - Column mapping API

**Policies**
- `ReconciliationPolicy` - Reconciliation authorization
- `OfxImportPolicy` - OFX import ownership checks
- `SettingPolicy` - Settings admin-only access
- `UserPolicy` - User management permissions

**Web Routes**
```
GET /imports - Unified import page (handles OFX, XLSX, CSV)
```

**Database Migrations** (Order matters!)
```
1. create_roles_table
2. create_permissions_table
3. create_permission_role_pivot_table
4. create_role_user_pivot_table
5. create_settings_table
6. create_setting_changes_table
7. create_reconciliations_table
8. create_reconciliation_transaction_pivot_table
9. create_ofx_imports_table
10. create_xlsx_imports_table
11. create_xlsx_column_mappings_table
12. create_xlsx_transaction_hashes_table
```

**Tests**
- PHPUnit: 80+ tests across OFX and XLSX imports
- Jest: 25+ component tests
- Test fixtures: OFX sample files, XLSX sample files
- Coverage: 75%+ overall, 85%+ for services

---

## Phase 6 Core Architecture

### System Architecture Diagram
```
┌─────────────────────────────────────────────────────────────────┐
│                   Single Unified Import Page (/imports)          │
│                    Detect file type → Route to handler           │
└──────────┬──────────────────────────────────────────────┬────────┘
           │                                              │
    ┌──────▼──────────┐                           ┌──────▼──────────┐
    │ OFX Upload Flow │                           │XLSX/CSV Upload  │
    │ (6.2-6.5)       │                           │ Flow (6.6-6.10) │
    └──────┬──────────┘                           └──────┬──────────┘
           │                                              │
    ┌──────▼────────────────────┐            ┌──────────▼──────────┐
    │ OfxImportService          │            │ XlsxImportService   │
    │ - Parse OFX              │            │ - Column Detection  │
    │ - Detect Accounts        │            │ - Smart Mapping     │
    │ - Compress/Store File    │            │ - Parse XLSX/CSV   │
    └──────┬────────────────────┘            └──────────┬──────────┘
           │                                            │
    ┌──────▼────────────────────┐            ┌─────────▼──────────┐
    │ ProcessOfxImport Job      │            │ ProcessXlsxImport  │
    │ (Queue-based)            │            │ Job (Queue-based)  │
    └──────┬────────────────────┘            └─────────┬──────────┘
           │                                            │
    ┌──────▼────────────────────────────────────────────▼──────────┐
    │        ReconciliationService                                 │
    │        Fuzzy Matching (Levenshtein distance)               │
    │        Auto-attach exact matches (100% confidence)         │
    │        Suggest strong/weak matches (75%/50%)              │
    └──────┬──────────────────────────────────────────────────────┘
           │
    ┌──────▼──────────────────────┐
    │ AccountingService           │
    │ Create transactions         │
    │ Update monthly balances    │
    └─────────────────────────────┘
```

### Key Design Decisions

1. **Single Unified Import Page** at `/imports`
   - File type detection based on extension
   - Conditional rendering of OFX vs XLSX/CSV components
   - Shared import history with type filtering

2. **Reusable Background Job Infrastructure**
   - `BaseProcessingJob` abstract class
   - Both OFX and XLSX use queue-based processing
   - Individual progress tracking per job
   - Concurrency limit enforcement

3. **Role-Based Permissions** (No Inheritance)
   - Explicit permission assignment (no role inheritance)
   - Permissions: `manage-users`, `manage-settings`, `view-reports`, `manage-reconciliations`
   - First registered user = admin with all permissions
   - New users = basic permissions only

4. **Configurable Settings** with Audit Trail
   - Centralized `Setting` model with JSON validation rules
   - Categories: import, system, security, matching
   - `SettingChange` table tracks all modifications
   - Examples: retention days, concurrency limits, matching thresholds

5. **Fuzzy Matching Algorithm** (Adaptive)
   - Three-tier confidence scoring: 100% (exact), 75% (strong), 50% (weak)
   - Levenshtein distance calculation with configurable threshold
   - Date tolerance: ±3 days (strong), ±7 days (weak)
   - Amount must match exactly for all tiers

6. **Duplicate Detection**
   - OFX: File-level SHA-256 hash (prevents reimporting same file)
   - XLSX: Row-level SHA-256 hash (date+amount+description)
   - Users can confirm reimport if needed

---

## Phase 6 Detailed Sub-Phases

### Phase 6.1-6.5: OFX/QFX Import with Fuzzy Matching
See detailed documentation: [PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md)

**Includes:**
- Role-based permissions system
- Configurable settings with audit trail
- Reconciliation core models
- OFX import service (parsing, compression, validation)
- Fuzzy matching algorithm (Levenshtein distance)
- Background job infrastructure (BaseProcessingJob)
- ProcessOfxImport job with progress tracking
- API endpoints (import, confirm, status, history, download)
- React components for OFX workflow
- 45+ PHPUnit tests
- 12+ Jest tests

**User Capabilities:**
- Upload OFX/QFX bank statements
- Automatic multi-account detection
- Real-time import progress tracking
- Intelligent transaction matching with confidence scores
- Accept/reject suggested matches
- Import unmatched transactions
- View import history with filters
- Download original OFX files for reimportation

---

### Phase 6.6-6.13: XLSX/CSV Import with Column Mapping
See detailed documentation: [PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md)

**Includes:**
- XLSX/CSV file parsing (Maatwebsite/Excel)
- Intelligent column header detection with smart suggestions
- Flexible column mapping UI with confidence scores
- Template generation (.xlsx with example data)
- Row-level duplicate detection (transaction hash)
- Error handling (skip invalid rows, continue processing)
- Saved mapping configurations (per-account or global)
- Background job processing (ProcessXlsxImport)
- API endpoints (detect, preview, store, history, error reports)
- React components for XLSX workflow
- 40+ PHPUnit tests
- 15+ Jest tests

**User Capabilities:**
- Upload XLSX/CSV spreadsheets
- Download standardized template
- Automatic column header recognition
- Review/adjust column mapping
- Preview data before import
- Save mapping configurations for reuse
- Import transactions with auto-created categories/tags
- Optional reconciliation during import
- Track import progress (processed/skipped/duplicates)
- Download error reports for invalid rows
- View import history
- Download original files for reference

---

## Core Features (All Sub-Phases)

### Role-Based Authorization
- **Admin users** can:
  - Manage user roles and permissions
  - Configure system settings
  - View audit trails
  - Assign/remove admin privileges
  - View all imports and reconciliations

- **Regular users** can:
  - Upload and manage their own imports
  - Create and manage reconciliations
  - View their own import history
  - Download their own files

### Configurable Settings
- **Import Category:**
  - `ofx_import_retention_days`: Days to keep OFX files (default: 90, min: 1, max: 365)
  - `xlsx_import_retention_days`: Days to keep XLSX files (default: 90, min: 1, max: 365)
  - `max_concurrent_imports_per_user`: Concurrent imports limit (default: 5, min: 1, max: 20)
  - `max_xlsx_rows_per_import`: Max rows per XLSX import (default: 5000)

- **Matching Category:**
  - `levenshtein_distance_threshold_percent`: Fuzzy match threshold (default: 20%, min: 1, max: 100)
  - `xlsx_duplicate_detection`: Strategy (row_level, file_level, disabled)

- **System Category:**
  - `xlsx_transaction_hash_retention_days`: Hash retention (default: 365, min: 30, max: 1095)

### Unified Import History
- Single import table view with type identification (OFX vs XLSX)
- Filters: Account, Status, Date Range, Import Type
- Tabs: All Imports, OFX Only, XLSX Only
- Type-specific actions:
  - OFX: Download file, View reconciliation, Reimport
  - XLSX: Download file, Download error report, View reconciliation, Reimport
- Pagination and sorting

### Alert System
When reconciliations have discrepancies or imports fail:
- Show clear error messages
- Provide troubleshooting suggestions
- Allow retry with different settings
- Log all import attempts for audit

---

## Testing Strategy Overview

### PHPUnit Tests (80+ tests)
- **OFX Services** (25+ tests): Parsing, compression, account detection, duplicate checking, concurrency limits
- **OFX Jobs** (10+ tests): Progress tracking, error handling, reconciliation creation, transaction matching
- **XLSX Services** (25+ tests): Column detection, mapping validation, row extraction, duplicate detection, error report generation
- **XLSX Jobs** (10+ tests): Row processing, category/tag creation, duplicate skipping, reconciliation matching
- **Settings** (5+ tests): Validation, auditing, caching
- **Reconciliation** (5+ tests): Fuzzy matching with various Levenshtein thresholds

### Jest Tests (25+ tests)
- **Import Upload** (5+ tests): File selection, type detection, error handling, concurrency warnings
- **Column Mapping** (5+ tests): Header detection, mapping adjustment, validation
- **Progress Tracking** (5+ tests): Real-time polling, status updates, completion
- **History & Filters** (5+ tests): Table rendering, filtering, pagination
- **Error Reporting** (5+ tests): Error display, report download, troubleshooting

### Test Fixtures
- `tests/fixtures/valid_statement.ofx` - Standard OFX file
- `tests/fixtures/multi_account.ofx` - OFX with multiple accounts
- `tests/fixtures/credit_card.ofx` - Credit card OFX
- `tests/fixtures/large.ofx` - 1000+ transactions (performance)
- `tests/fixtures/standard_format.xlsx` - Date, Description, Amount
- `tests/fixtures/debit_credit.xlsx` - Separate debit/credit columns
- `tests/fixtures/with_tags.xlsx` - All optional columns included
- `tests/fixtures/large_import.xlsx` - 1000+ rows for testing

### Coverage Targets
- **Services**: 90%+ (critical business logic)
- **Jobs**: 85%+ (background processing)
- **Controllers**: 80%+ (API endpoints)
- **Models**: 75%+ (data relationships)
- **React Components**: 70%+ (focus on logic, not presentational)
- **Overall**: 80%+

---

## Database Transactions & Consistency

### Atomic Operations
- Transaction creation and balance snapshot update in single transaction
- Reconciliation and match creation together
- Role assignment with permission checks
- Setting updates with change audit in single transaction

### Concurrency Control
- Row locking during balance updates
- File hash uniqueness constraint prevents duplicate OFX imports
- Row hash uniqueness for XLSX duplicate detection
- Concurrent import limits enforced via database count

---

## Performance Considerations

### Optimization Strategies
- Monthly balance snapshots prevent N+1 queries
- Eager loading for relationships in API responses
- Pagination on all list endpoints (default: 15 items)
- Caching of settings (invalidate on update)
- Chunked file processing for large imports
- Background job queues to prevent blocking

### Query Optimization
- Indexes on: `user_id`, `account_id`, `created_at`, file hashes
- Covering indexes for common filters
- Database transaction timestamps in UTC
- Efficient subqueries for reconciliation matching

---

## Infrastructure Requirements

### Redis
- Queue storage for background jobs
- Session and cache storage
- Added to `compose.yaml`
- Queue worker service in Docker Compose

### Storage
- Local: `storage/app/ofx_imports/` for compressed OFX files
- Local: `storage/app/xlsx_imports/` for compressed XLSX files
- Local: `storage/app/xlsx_imports/errors/` for error reports
- Gzip compression for file storage

### Composer Dependencies
```
composer require asgrim/ofxparser
composer require maatwebsite/excel
```

---

## Scheduled Commands

### Daily (2:00 AM)
- `ofx:cleanup-expired` - Delete OFX files older than retention period
- `xlsx:cleanup-expired` - Delete XLSX files older than retention period

### Weekly (Sundays, 3:00 AM)
- `xlsx:cleanup-hashes` - Delete transaction hashes older than retention period

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (80+ tests, all passing)
- ✅ Jest tests (25+ tests, all passing)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 80%+ overall, 90%+ for services

---

## Detailed Sub-Phase Documentation

For complete implementation details, see:

1. **[PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md)** - OFX/QFX import with fuzzy matching
   - Role-based permissions
   - Configurable settings with audit trail
   - OFX parsing and compression
   - Levenshtein distance matching
   - Background job processing
   - 45+ tests
   - 12+ React components

2. **[PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md)** - XLSX/CSV import with column mapping
   - Intelligent column detection
   - Flexible mapping configuration
   - Row-level duplicate detection
   - Error handling and reporting
   - Saved mapping reuse
   - 40+ tests
   - 10+ React components

---

## User Capabilities

After Phase 6 completion, users will be able to:

✅ **Import Transactions**
- Upload OFX/QFX/XLSX/CSV statement files
- Automatically parse and extract transactions
- Handle multiple file formats in single interface
- Track import progress in real-time

✅ **Intelligent Matching**
- Review automatically matched transactions
- See confidence scores and match reasoning
- Accept or reject suggested matches
- Import unmatched transactions
- Optional fuzzy matching with configurable thresholds

✅ **Reconciliation**
- Match imported transactions to existing entries
- Identify discrepancies
- Lock matched transactions
- Complete reconciliations
- View reconciliation history

✅ **Import Management**
- View comprehensive import history
- Filter by type, account, status, date
- Download original files for reference
- Reimport when needed
- Download error reports for troubleshooting

✅ **Administration** (Admin only)
- Manage user roles and permissions
- Configure system settings (retention, limits, thresholds)
- View audit trail of all setting changes
- Assign/remove admin privileges
- View all imports and reconciliations

---

## Estimated Timeline

- **OFX Import** (Phases 6.1-6.5): 2-3 weeks
- **XLSX/CSV Import** (Phases 6.6-6.13): 2-3 weeks
- **Integration & Testing**: 1 week
- **Total Phase 6**: 4-6 weeks

---

## Future Enhancements

- CSV format detection and parsing
- Bank API integrations (plaid, yodlee)
- Automatic transaction categorization (ML)
- Recurring transaction detection
- Budget alerts and planning
- Multi-currency support
- Export to QuickBooks/other tools

---

## Previous Phase

[Phase 5: Analytics Dashboard](PHASE_05_ANALYTICS_DASHBOARD.md)

## Detailed Sub-Phases

- [Phase 6.1-6.5: OFX Import Details](PHASE_06_OFX_IMPORT.md)
- [Phase 6.6-6.13: XLSX/CSV Import Details](PHASE_06_XLSX_CSV_IMPORT.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026
