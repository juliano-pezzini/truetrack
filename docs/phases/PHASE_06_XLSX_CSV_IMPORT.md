# Phase 6.6-6.13: XLSX/CSV Import with Flexible Column Mapping

**Status**: Planned  
**Estimated Effort**: 2-3 weeks  
**Complexity**: High (column mapping, error handling, row-level duplicate detection)

---

## Overview

Implement flexible XLSX/CSV file import with intelligent column header detection, user-friendly column mapping interface, row-level duplicate detection, error handling, and saved mapping configurations for reuse.

For complete architectural overview, see [PHASE_06_STATEMENT_IMPORT.md](PHASE_06_STATEMENT_IMPORT.md).

---

## Core Features

### 1. Column Detection & Smart Suggestions
- Automatic header row detection (skip empty rows)
- Intelligent column name matching with confidence scores
- Suggests mapping based on column names:
  - Date, Transaction Date, Trans Date → `transaction_date`
  - Description, Memo, Details → `description`
  - Amount, Total → `amount_column`
  - Debit, Withdrawal → `debit_column`
  - Credit, Deposit → `credit_column`
  - Category → `category_column`
  - Tags, Labels → `tags_column`

### 2. Flexible Amount Strategies
**Strategy A**: Single amount column (negative = debit, positive = credit)  
**Strategy B**: Separate debit and credit columns  
**Strategy C**: Amount + Type columns (explicit type values)

### 3. Template Generation
- Downloadable XLSX with headers, example data, and format notes
- Column names marked as (Required) or (Optional)
- Example data showing expected format
- Format hints for date, amounts, tags

### 4. Column Mapping Persistence
- `XlsxColumnMapping` model for saving mappings
- Per-account or global mappings
- Default mapping support
- Usage tracking (last_used_at)
- Automatic selection on second import

### 5. Row-Level Duplicate Detection
- SHA-256 hash of (date + amount + description)
- `XlsxTransactionHash` table for tracking
- Prevents duplicates across multiple imports
- Scheduled cleanup (1-year retention)

### 6. Error Handling & Reporting
- Skip invalid rows, continue processing
- Collect error details: row number, field, error message, raw value
- Generate downloadable error CSV report
- Show summary: processed, skipped, duplicate counts

### 7. Background Job Processing
- `ProcessXlsxImport` job extends `BaseProcessingJob`
- Row-by-row processing with progress tracking
- Auto-create categories and tags from imported data
- Optional fuzzy matching to existing transactions for reconciliation

---

## Database Schema

### XLSX Import Tracking
```sql
-- xlsx_imports table
CREATE TABLE xlsx_imports (
    id BIGINT PRIMARY KEY, filename VARCHAR(255),
    file_hash VARCHAR(64), account_id BIGINT NOT NULL,
    reconciliation_id BIGINT, user_id BIGINT NOT NULL,
    column_mapping_id BIGINT,
    status ENUM('pending', 'processing', 'completed', 'failed'),
    processed_count INT DEFAULT 0, total_count INT,
    skipped_count INT DEFAULT 0, duplicate_count INT DEFAULT 0,
    error_message TEXT, error_report_path VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP, updated_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE INDEX idx_file_hash_account (file_hash, account_id, status)
);

-- xlsx_column_mappings table
CREATE TABLE xlsx_column_mappings (
    id BIGINT PRIMARY KEY, user_id BIGINT NOT NULL,
    account_id BIGINT,
    name VARCHAR(255), mapping_config JSON,
    is_default BOOLEAN DEFAULT FALSE,
    last_used_at TIMESTAMP,
    created_at TIMESTAMP, updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_default (is_default)
);

-- xlsx_transaction_hashes table
CREATE TABLE xlsx_transaction_hashes (
    id BIGINT PRIMARY KEY, user_id BIGINT NOT NULL,
    account_id BIGINT NOT NULL, row_hash VARCHAR(64),
    transaction_id BIGINT,
    imported_at TIMESTAMP,
    UNIQUE INDEX idx_user_account_hash (user_id, account_id, row_hash),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);
```

---

## XLSX Service

### Core Methods

**`parseXlsxFile()`** - Parse spreadsheet, detect headers, return preview rows

**`detectHeaders()`** - Find first non-empty row as headers

**`guessColumnMapping()`** - Smart heuristics with confidence scores
```php
// Returns suggested mapping_config JSON with confidence_scores
{
  "date_column": "Transaction Date",        // 100% confidence
  "description_column": "Description",      // 100%
  "amount_strategy": "single_column",       // 75%
  "amount_column": "Amount",
  "category_column": "Category"             // 80%
}
```

**`validateMapping()`** - Ensure required fields and strategy consistency

**`previewWithMapping()`** - Apply mapping to first 5 rows with validation warnings

**`extractTransactionFromRow()`** - Map row to transaction data
```php
// Returns:
[
  'transaction_date' => Carbon object,
  'amount' => float,
  'type' => 'credit' or 'debit',
  'description' => string,
  'category_name' => string|null,
  'tags' => array of tag names
]
```

**`detectType()`** - Determine credit/debit based on strategy

**`calculateRowHash()`** - SHA-256 of date+amount+description

**`checkRowDuplicate()`** - Query hash table, return existing transaction_id or null

**`compressAndStoreFile()`** - Gzip compression, return storage path

**`generateTemplate()`** - Create XLSX template download

**`generateErrorReport()`** - Create CSV with error details

---

## Installation

```bash
cd workspace
docker compose exec truetrack composer require maatwebsite/excel
```

---

## API Endpoints

### Column Detection

**`POST /api/v1/xlsx-imports/detect-columns`**
```json
Request: { "xlsx_file": <file> }
Response:
{
  "data": {
    "headers": ["Date", "Description", "Amount", "Category"],
    "suggested_mapping": { ... },
    "confidence_scores": { "date_column": 100, ... },
    "preview_rows": [["2026-01-15", "Groceries", "-50.00", "Food"], ...],
    "total_rows": 150
  }
}
```

### Mapping Preview

**`POST /api/v1/xlsx-imports/preview`**
```json
Request:
{
  "xlsx_file": <file>,
  "mapping_config": { ... }
}

Response: Preview with 5 rows and validation warnings
```

### Import Storage

**`POST /api/v1/xlsx-imports/store`**
```json
Request:
{
  "xlsx_file": <file>,
  "account_id": 1,
  "mapping_config": { ... },
  "save_mapping": true,
  "mapping_name": "Chase Bank Format",
  "create_reconciliation": false,
  "statement_date": "2026-01-31",
  "statement_balance": 5000.00
}

Response (201): { "data": { "import_id": 123, "status": "pending" } }
```

### Import Management

**`GET /api/v1/xlsx-imports`** - Import history with filters and pagination  
**`GET /api/v1/xlsx-imports/{id}`** - Poll job status and progress  
**`GET /api/v1/xlsx-imports/{id}/download`** - Download original XLSX file  
**`GET /api/v1/xlsx-imports/{id}/error-report`** - Download error CSV

### Column Mappings

**`GET /api/v1/xlsx-column-mappings`** - List saved mappings  
**`POST /api/v1/xlsx-column-mappings`** - Create new mapping  
**`PUT /api/v1/xlsx-column-mappings/{id}`** - Update mapping  
**`DELETE /api/v1/xlsx-column-mappings/{id}`** - Delete mapping

### Template

**`GET /api/v1/xlsx-imports/template`** - Download XLSX template

---

## React Components

### Workflow Components
1. **`XlsxImportUpload`** - Main upload form
   - File input (.xlsx, .csv)
   - Account selector
   - Concurrency limit warning
   - "Download Template" button

2. **`XlsxColumnMapper`** - Column mapping configuration
   - Display detected headers
   - Dropdown per column to select mapping
   - Radio buttons for type detection strategy
   - Confidence badges
   - "Load Saved Mapping" dropdown
   - "Save Mapping" checkbox with name input
   - "Auto-detect" button

3. **`XlsxPreviewTable`** - Preview mapped data
   - Show first 5 rows with mapping applied
   - Warning badges for validation issues
   - "Looks good? Import N rows" confirmation
   - "Back to mapping" button

4. **`XlsxImportProgress`** - Real-time progress tracking
   - Progress bar with percentage
   - Processed/Skipped/Duplicate counts
   - "Download Error Report" button (if errors exist)
   - Status badges

5. **`XlsxImportHistory`** - Import history table
   - Columns: Date, Filename, Account, Status, Processed/Skipped/Duplicates
   - Filters: Account, Status, Date Range
   - Expandable rows with reconciliation link and error details
   - Actions: View Details, Download File, Download Errors, Reimport

### Reusable Components
- **`SavedMappingSelector`** - Dropdown for saved mappings
- **`ReconciliationOptionsPanel`** - Optional reconciliation config during import

---

## Form Requests

### DetectXlsxColumnsRequest
```php
'xlsx_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
```

### PreviewXlsxImportRequest
```php
'xlsx_file' => 'required|file|...',
'mapping_config' => 'required|array|...',
// Conditional validations based on strategy
```

### StoreXlsxImportRequest
```php
'xlsx_file' => 'required|file|...',
'account_id' => 'required|integer|exists:accounts,id',
'mapping_config' => 'required|array|...',
'save_mapping' => 'boolean',
'mapping_name' => 'required_if:save_mapping,true',
'create_reconciliation' => 'boolean',
'statement_date' => 'required_if:create_reconciliation,true',
'statement_balance' => 'required_if:create_reconciliation,true',
// Plus concurrency limit check
```

---

## Testing

### PHPUnit Tests (40+ tests)
- **XLSX Service**: Parsing, column detection, mapping validation, row extraction, hash calculation, error reporting
- **XLSX Job**: Row processing, category/tag creation, duplicate detection, reconciliation matching
- **Column Mapping**: Persistence, loading, default handling
- **API Endpoints**: Detect, preview, store, history, error report, mappings
- **Settings**: Retention days, row limits, duplicate detection strategy

### Jest Tests (15+ tests)
- **XlsxImportUpload**: File selection, concurrency warning, template download
- **XlsxColumnMapper**: Header detection, mapping adjustment, saved mapping loading, validation
- **XlsxPreviewTable**: Data display, validation warning badges, confirmation
- **XlsxImportProgress**: Progress polling, skipped/duplicate counts, error report button
- **XlsxImportHistory**: Table rendering, filtering, pagination, error report download

### Test Fixtures
- `tests/fixtures/standard_format.xlsx` - Date, Description, Amount
- `tests/fixtures/debit_credit.xlsx` - Separate debit/credit columns
- `tests/fixtures/type_column.xlsx` - Explicit type column
- `tests/fixtures/with_tags.xlsx` - All optional columns
- `tests/fixtures/invalid_dates.xlsx` - Invalid date formats
- `tests/fixtures/non_numeric_amounts.xlsx` - Non-numeric amounts
- `tests/fixtures/large.xlsx` - 1000+ rows (performance)
- `tests/fixtures/valid_statement.csv` - CSV format

---

## Settings

**New Settings** (configured via seeder):
- `xlsx_import_retention_days`: 90 (min: 1, max: 365)
- `max_xlsx_rows_per_import`: 5000 (min: 1, max: 50000)
- `xlsx_duplicate_detection`: 'row_level' (row_level, file_level, disabled)
- `xlsx_transaction_hash_retention_days`: 365 (min: 30, max: 1095)

---

## Scheduled Commands

**Daily (2:00 AM)**
- `xlsx:cleanup-expired` - Delete XLSX files older than retention period

**Weekly (Sundays, 3:00 AM)**
- `xlsx:cleanup-hashes` - Delete transaction hashes older than retention period

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass, 40+)
- ✅ Jest tests (all tests pass, 15+)
- ✅ Laravel Pint (no violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 85%+ for services, 80%+ overall

---

## User Capabilities

✅ Upload XLSX or CSV files (up to 10MB, 5000 rows)  
✅ Download standardized XLSX template  
✅ Automatically detect column headers  
✅ Review smart mapping suggestions with confidence scores  
✅ Manually adjust column mappings  
✅ Choose type detection strategy (amount sign, separate columns, type column)  
✅ Preview first 5 rows with mapping applied  
✅ Save mapping configurations for reuse  
✅ Load saved mappings with one click  
✅ Import transactions with categories and tags (auto-created)  
✅ Optional reconciliation during import  
✅ Track import progress in real-time  
✅ Automatically skip duplicate rows  
✅ Continue import on invalid rows (skip and log)  
✅ Download detailed error report (CSV)  
✅ View import history with filters  
✅ Download original files for reference  

---

## Key Implementation Notes

- **Memory Efficient**: Chunked reading for large files
- **Flexible Parsing**: Supports XLSX and CSV via Maatwebsite/Excel
- **Auto-Categorization**: Creates categories if not exist
- **Tag Handling**: Comma-separated in spreadsheet, auto-created per tag
- **Error Recovery**: Invalid rows logged, import continues
- **Reusable Mappings**: Save per account or globally for repeat imports
- **Hash Cleanup**: Scheduled deletion prevents table bloat

---

## Unified Import Page Integration

Both OFX and XLSX/CSV components integrated into single `/imports` page:
- File type detection on upload
- Conditional rendering (OFX vs XLSX/CSV)
- Unified import history with type tabs
- Shared reusable components
- Single API base: `/api/v1`

---

## Previous Phase

[Phase 5: Analytics Dashboard](PHASE_05_ANALYTICS_DASHBOARD.md)

## Related Documentation

- [Phase 6: Statement Import Overview](PHASE_06_STATEMENT_IMPORT.md)
- [Phase 6.1-6.5: OFX Import](PHASE_06_OFX_IMPORT.md)

---

**Created**: January 30, 2026  
**Last Updated**: January 30, 2026
