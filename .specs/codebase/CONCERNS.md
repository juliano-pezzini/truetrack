# Codebase Concerns

**Analysis Date:** 2025-05-25

## Tech Debt

**Service Locator Usage Instead of DI:**

- Issue: `AccountingService` resolved via `app()` service locator in several places instead of constructor injection
- Files: `app/Http/Resources/AccountResource.php:26`, `app/Http/Controllers/AccountController.php:59`, `app/Services/ReportingService.php` (5 occurrences)
- Why: Quick fix during implementation; `AccountResource` can't easily receive constructor args, `ReportingService` resolves it inline
- Impact: Harder to test in isolation, hidden dependency, inconsistent with DI pattern used elsewhere (e.g., `TransactionController` properly injects `AccountingService`)
- Fix approach: Inject `AccountingService` into `ReportingService` via constructor. For `AccountResource`, consider passing balance as resource `additional` data from the controller instead of resolving the service inside the resource.

**Balance Calculation in Resource:**

- Issue: `AccountResource::toArray()` calls `AccountingService::calculateBalance()` for every serialized account
- Files: `app/Http/Resources/AccountResource.php`
- Impact: N+1 performance risk when serializing account collections via API; each account triggers a DB query for balance snapshot + transaction sum
- Fix approach: Pre-compute balances in the controller and pass them as additional data, or add a batch balance calculation method to `AccountingService`

**BaseProcessingJob Typed to OfxImport:**

- Issue: `BaseProcessingJob` methods (`updateProgress`, `markCompleted`, `handleFailure`) are typed to `OfxImport` model, but `ProcessXlsxImport` also extends it
- Files: `app/Jobs/BaseProcessingJob.php`
- Impact: Type confusion — XLSX imports must either conform to `OfxImport` interface or bypass the base methods
- Fix approach: Create an `Importable` interface implemented by both `OfxImport` and `XlsxImport`, type base methods to the interface

## Security Considerations

**Raw SQL in Reporting/Import Services:**

- Risk: `selectRaw`, `DB::raw` used in aggregation queries
- Files: `app/Services/ReportingService.php` (4 occurrences), `app/Services/ImportHistoryService.php` (2 occurrences), `app/Http/Controllers/Api/V1/ImportController.php` (2 occurrences), `app/Console/Commands/CleanupOldTransactionHashes.php` (2 occurrences)
- Current mitigation: Raw SQL uses literal strings only — no user input is interpolated. All parameterized values go through query builder bindings.
- Recommendations: No immediate action needed. Document that raw SQL should never accept user input directly.

**CSRF Middleware Disabled in Tests:**

- Risk: `TestCase::setUp()` disables `ValidateCsrfToken` middleware globally for all tests
- Files: `tests/TestCase.php:18`
- Current mitigation: CSRF is active in production; test bypass is standard Laravel practice
- Recommendations: Acceptable pattern. Consider adding one explicit CSRF test to verify middleware is active in non-test environments.

## Performance Bottlenecks

**Per-Account Balance Calculation in Listings:**

- Problem: `AccountController::index()` iterates all paginated accounts and calls `calculateBalance()` per account
- Files: `app/Http/Controllers/AccountController.php:59-64`
- Cause: Each balance calculation queries `account_balances` + sums transactions since last snapshot
- Improvement path: Add batch method `calculateBalances(Collection $accounts, Carbon $date)` that loads all snapshots + transactions in 2 queries instead of 2×N

**No Database Indexes on Common Filter Columns:**

- Problem: Filtering by `transaction_date`, `type`, `category_id` in `TransactionController::index()` may be slow on large datasets
- Files: `app/Http/Controllers/TransactionController.php` (filter logic)
- Improvement path: Verify composite indexes exist on `(user_id, transaction_date)`, `(user_id, account_id, transaction_date)`, `(user_id, category_id)`. These may already exist from migrations — verify.

## Fragile Areas

**Monthly Balance Snapshot Recalculation:**

- Files: `app/Services/AccountingService.php` — `recalculateMonthlyBalance()`
- Why fragile: Any bug in balance recalculation silently corrupts all future balance reads; the snapshot is the single source of truth for "fast" balance lookups
- Common failures: Timezone mismatches, off-by-one month boundaries, concurrent transaction writes
- Safe modification: Always wrap in DB transaction with row-level locks. Add regression tests for month boundary edge cases. Consider adding a "verify snapshot" artisan command.
- Test coverage: Tested in `AccountingServiceTest`, but edge cases around year/month boundaries should be verified

**Transaction Observer Auto-Categorization:**

- Files: `app/Observers/TransactionObserver.php`
- Why fragile: Runs on every `Transaction::creating` event — including bulk imports. If `AutoCategorizationService` throws, the entire transaction creation fails.
- Safe modification: Wrap in try/catch; auto-categorization failure should log a warning, not block transaction creation.
- Test coverage: `tests/Feature/Integration/TransactionObserverTest.php`

## Dependencies at Risk

**asgrim/ofxparser ^1.1:**

- Risk: Niche library with limited maintenance activity
- Impact: OFX parsing relies entirely on this library; any format changes in bank OFX exports could break imports
- Migration plan: Monitor for issues; if abandoned, fork or switch to manual XML parsing (OFX is XML-based)
