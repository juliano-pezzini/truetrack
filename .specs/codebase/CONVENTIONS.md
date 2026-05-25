# Code Conventions

## Naming Conventions

**Files (PHP):**
- Models: PascalCase singular (`Account.php`, `Transaction.php`, `AutoCategoryRule.php`)
- Controllers: PascalCase singular resource + `Controller` (`AccountController.php`, `OfxImportController.php`)
- Services: PascalCase purpose + `Service` (`AccountingService.php`, `AutoCategorizationService.php`)
- Form Requests: Action + Resource + `Request` (`StoreAccountRequest.php`, `UpdateTransactionRequest.php`, `BulkDeleteAccountRequest.php`)
- Resources: Resource + `Resource` (`AccountResource.php`, `TransactionResource.php`)
- Policies: Resource + `Policy` (`AccountPolicy.php`, `TransactionPolicy.php`)
- Jobs: Verb + Noun (`ProcessOfxImport.php`, `BaseProcessingJob.php`)
- Enums: PascalCase (`AccountType.php`, `TransactionType.php`)
- Commands: Verb + Noun (`CleanupExpiredOfxImports.php`)
- Factories: Model + `Factory` (`AccountFactory.php`)
- Seeders: Model + `Seeder` (`AccountSeeder.php`)
- Migrations: Timestamp + snake_case (`2026_01_09_000000_create_accounts_table.php`)

**Files (JS/React):**
- Pages: PascalCase (`Index.jsx`, `Create.jsx`, `Edit.jsx`)
- Components: PascalCase descriptive (`CashFlowChart.jsx`, `FileDropZone.jsx`, `XlsxColumnMapper.jsx`)
- Layouts: PascalCase + `Layout` (`AuthenticatedLayout.jsx`, `GuestLayout.jsx`)
- Utils: camelCase (`normalizeInertiaUrl.js`)
- Tests: Component + `.test.jsx` (`AutoRuleForm.test.jsx`, `ConvertPatternModal.test.jsx`)

**Functions/Methods:**
- PHP: camelCase (`recordTransaction`, `calculateBalance`, `suggestCategory`, `recalculateMonthlyBalance`)
- Scopes: `scope` prefix + PascalCase (`scopeActive`, `scopeOfType`, `scopeForUser`, `scopeBetweenDates`)
- React: camelCase (`handleSubmit`, `handleDelete`)

**Variables:**
- PHP: camelCase (`$accountData`, `$transactionDate`, `$baseBalance`)
- JS: camelCase (`currentBalance`, `sortField`, `filterType`)

**Constants/Enums:**
- Enum cases: UPPER_SNAKE (`BANK`, `CREDIT_CARD`, `WALLET`, `TRANSITIONAL`)
- Backed values: lower_snake (`'bank'`, `'credit_card'`)

## Code Organization

**Strict types:** Every PHP file starts with `declare(strict_types=1);`

**Import ordering (PHP):**
1. PHP built-in classes
2. Framework/vendor classes (alphabetical)
3. App classes (alphabetical)

**File structure (Controllers):**
- Constructor with DI (if service needed)
- CRUD methods in order: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- Custom actions after CRUD (`bulkDestroy`, `complete`, etc.)

**File structure (Models):**
- Traits (`HasFactory`, `SoftDeletes`)
- `$fillable` array
- `$casts` array or `casts()` method
- Relationships (alphabetical by method name)
- Scopes
- Custom methods

## Type Safety

**Approach:** Full type hinting — parameters, return types, PHPDoc for arrays

```php
// Return types on all public methods
public function recordTransaction(array $data): Transaction
public function calculateBalance(Account $account, Carbon $date): float

// Relationship return types
public function account(): BelongsTo
public function transactions(): HasMany
public function tags(): BelongsToMany

// PHPDoc for array shapes
/** @var list<string> */
protected $fillable = [...]

/** @var array<string, string> */
protected $casts = [...]

/** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
public function rules(): array
```

## Error Handling

**Pattern:** DB transactions in services with try/catch/rollback:
```php
DB::beginTransaction();
try {
    // ... business logic ...
    DB::commit();
    return $result;
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

**Locking:** `Account::query()->lockForUpdate()->firstOrFail()` before balance-affecting operations

## Comments/Documentation

**Style:** PHPDoc blocks on all public methods with `@param`, `@return`, `@throws`. Inline comments for complex logic steps.

```php
/**
 * Record a new transaction and update account balance.
 *
 * @param  array<string, mixed>  $data
 * @throws InvalidArgumentException
 */
public function recordTransaction(array $data): Transaction
```

## Model Conventions

**Mass assignment:** Always `$fillable` (never `$guarded`)
**Casting:** Enums cast to enum classes, decimals to `decimal:2`, dates to `date`, booleans to `boolean`
**Soft deletes:** Used on `Account`, `Transaction` (via `SoftDeletes` trait)
**Factories:** Every model has a factory in `database/factories/`

## Code Style (Pint)

- Preset: `laravel`
- Custom rules: `simplified_null_return`, `new_with_braces` (anonymous + named), `return_type_declaration` (no space before)
- Excludes: `bootstrap`, `storage`, `vendor`
