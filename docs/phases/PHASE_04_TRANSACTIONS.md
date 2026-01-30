# Phase 4: Transactions with Double-Entry Accounting

**Goal**: Record financial transactions with accounting integrity

**Status**: Planned

**Estimated Effort**: 2-3 weeks

---

## Overview

Implement the transaction system with personal finance balance logic, monthly snapshot updates, and comprehensive transaction tracking. This is the core business logic phase.

---

## Deliverables

### Models
- `Transaction` model with relationships
- Migration with proper indexes and constraints

### Business Logic
- `AccountingService` for transaction recording
- Personal finance balance calculation logic
- Monthly balance snapshot updates
- Transaction reversal handling

### API
- RESTful API Controller (`Api\V1\TransactionController`)
- Comprehensive filtering and sorting
- Balance calculations on endpoints

### Web
- Web Controller for Inertia
- React components for transaction management

### Database
- `create_transactions_table` migration
- Indexes on foreign keys, dates, user_id
- Check constraints for data integrity

### Testing
- Factory: `TransactionFactory`
- Seeder: `TransactionSeeder`
- Extensive PHPUnit tests for business logic
- Jest tests for forms

---

## Models

### Transaction Model

```php
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'amount',
        'description',
        'transaction_date',
        'settled_date',
        'type',
        'external_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'settled_date' => 'date',
        'type' => TransactionType::class,
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function account(): BelongsTo { }
    public function category(): BelongsTo { }
    public function tags(): BelongsToMany { }
    public function reconciliations(): BelongsToMany { }

    // Scopes
    public function scopeByAccount(Builder $query, int $accountId): Builder { }
    public function scopeByCategory(Builder $query, int $categoryId): Builder { }
    public function scopeByType(Builder $query, TransactionType $type): Builder { }
    public function scopeByDateRange(Builder $query, Carbon $from, Carbon $to): Builder { }
    public function scopeSettled(Builder $query): Builder { }
    public function scopeUnsettled(Builder $query): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `account_id` - Foreign key to accounts
- `category_id` - Foreign key to categories (nullable)
- `amount` - Transaction amount (decimal, always positive)
- `description` - Transaction description
- `transaction_date` - When transaction occurred
- `settled_date` - When transaction settled (nullable)
- `type` - TransactionType enum: `credit` (increases balance), `debit` (decreases balance)
- `external_id` - External identifier (for imports, optional)
- `created_at`, `updated_at`, `deleted_at`

---

## Database Schema

### transactions Table
```sql
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    account_id BIGINT NOT NULL,
    category_id BIGINT,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    transaction_date DATE NOT NULL,
    settled_date DATE,
    type ENUM('credit', 'debit') NOT NULL,
    external_id VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CHECK (amount > 0),
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_category_id (category_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_settled_date (settled_date),
    UNIQUE INDEX idx_external_id (account_id, external_id)
);
```

### account_balances Table (Updated)
```sql
CREATE TABLE account_balances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    account_id BIGINT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    closing_balance DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_account_month (account_id, year, month),
    INDEX idx_account_id (account_id)
);
```

---

## Business Logic: Personal Finance Balance

### Transaction Types
- **`credit`**: Income, deposits, refunds → **INCREASES** account balance
- **`debit`**: Expenses, withdrawals, payments → **DECREASES** account balance

### Balance Calculation Formula
```
balance = base_balance + sum(credits) - sum(debits)
```

### Balance Calculation Process
1. Find most recent monthly snapshot before target date
2. Use snapshot balance if exists, otherwise use `initial_balance`
3. Sum all transactions between snapshot date and target date
4. Apply formula: credits add, debits subtract

### Credit Card Behavior
- Account balance typically negative (amount owed)
- Credit transaction (payment): reduces amount owed (-$400 + $100 = -$300)
- Debit transaction (purchase): increases amount owed (-$300 - $50 = -$350)

### AccountingService

```php
class AccountingService
{
    public function recordTransaction(array $data): Transaction
    {
        DB::beginTransaction();
        try {
            // Create transaction
            $transaction = Transaction::create($data);
            
            // Update monthly balance snapshot
            $this->updateMonthlyBalance($transaction);
            
            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function calculateBalance(Account $account, Carbon $date): float
    {
        // Get most recent monthly snapshot
        $snapshot = AccountBalance::where('account_id', $account->id)
            ->where(fn($q) => $q->whereYear('year', '<', $date->year)
                ->orWhere(fn($q) => $q->whereYear('year', $date->year)
                    ->whereMonth('month', '<=', $date->month)))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
        
        $baseBalance = $snapshot 
            ? $snapshot->closing_balance 
            : $account->initial_balance;
        
        $startDate = $snapshot 
            ? Carbon::create($snapshot->year, $snapshot->month, 1)->endOfMonth()
            : $account->created_at;
        
        // Sum transactions
        $transactions = $account->transactions()
            ->whereBetween('transaction_date', [$startDate, $date])
            ->get();
        
        $balance = $baseBalance;
        foreach ($transactions as $txn) {
            $balance += ($txn->type === 'credit') ? $txn->amount : -$txn->amount;
        }
        
        return $balance;
    }
    
    public function updateMonthlyBalance(Transaction $transaction): void
    {
        $year = $transaction->transaction_date->year;
        $month = $transaction->transaction_date->month;
        
        $balance = $this->calculateMonthlyClosingBalance(
            $transaction->account_id,
            $year,
            $month
        );
        
        AccountBalance::updateOrCreate(
            ['account_id' => $transaction->account_id, 'year' => $year, 'month' => $month],
            ['closing_balance' => $balance]
        );
    }
}
```

---

## API Endpoints

### Transactions Endpoints

**GET /api/v1/transactions**
```
Query Parameters:
  - filter[account_id] - Filter by account
  - filter[category_id] - Filter by category
  - filter[type] - Filter by type (credit, debit)
  - filter[date_from] - From date
  - filter[date_to] - To date
  - filter[settled] - Filter by settlement (true/false)
  - sort - Sort by field
  - per_page - Items per page

Response:
{
  "data": [
    {
      "id": 1,
      "account_id": 1,
      "account_name": "Chase Checking",
      "category_id": 5,
      "category_name": "Groceries",
      "amount": 50.00,
      "type": "debit",
      "description": "Whole Foods",
      "transaction_date": "2026-01-15",
      "settled_date": "2026-01-16",
      "tags": [{"id": 1, "name": "Food", "color": "#FF5733"}],
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": { ... }
}
```

**POST /api/v1/transactions**
```
Request:
{
  "account_id": 1,
  "category_id": 5,
  "amount": 50.00,
  "type": "debit",
  "description": "Whole Foods",
  "transaction_date": "2026-01-15",
  "settled_date": "2026-01-16",
  "tag_ids": [1, 2]
}

Response (201 Created): Transaction resource with calculated balance
```

**GET /api/v1/transactions/{id}**
```
Response: Transaction with all details and current account balance
```

**PUT /api/v1/transactions/{id}**
```
Request: Update amount, category, tags, settled_date, description
Note: Cannot change account_id or type
Response (200 OK): Updated transaction
```

**DELETE /api/v1/transactions/{id}**
```
Response (204 No Content)
```

### Account Balance Endpoint

**GET /api/v1/accounts/{id}/balance**
```
Query Parameters:
  - date - Calculate balance as of date (default: today)

Response:
{
  "data": {
    "account_id": 1,
    "current_balance": 1050.00,
    "initial_balance": 1000.00,
    "balance_date": "2026-01-20",
    "total_credits": 200.00,
    "total_debits": 150.00
  }
}
```

---

## React Components

### TransactionList Component
- Display transactions in table format
- Columns: Date, Description, Category, Tags, Amount, Type, Status
- Filters: Account, Category, Type, Date range, Settlement status
- Sorting: Clickable column headers
- Actions: Edit, Delete, View
- Pagination
- Display current account balance

### TransactionForm Component
- Account dropdown (pre-selected if from account context)
- Description input (required)
- Amount input (required, decimal)
- Type radio buttons (credit, debit)
- Category dropdown (optional)
- Tags multi-select (optional)
- Transaction date picker
- Settled date picker (optional)
- Form validation
- Submit and cancel buttons

### TransactionBalanceDisplay Component
- Show current account balance
- Show balance trend (up/down)
- Display total credits and debits for period
- Color-coded (green for credits, red for debits)

---

## Form Requests

### StoreTransactionRequest
```
Validation Rules:
- account_id: required, integer, exists:accounts,id, owned by user
- category_id: nullable, integer, exists:categories,id, owned by user
- amount: required, numeric, min:0.01, max:999999.99
- type: required, in:credit,debit
- description: required, string, max:255
- transaction_date: required, date, not in future
- settled_date: nullable, date, >= transaction_date
- tag_ids: nullable, array
- tag_ids.*: integer, exists:tags,id, owned by user
```

### UpdateTransactionRequest
```
Same as Store, plus:
- Cannot change account_id
- Cannot change type
- Can update amount, category, tags, dates, description
```

---

## Factories & Seeders

### TransactionFactory
```php
Creates transactions with:
- Random accounts per user
- Random categories
- Random amounts (1-500)
- Mix of credit/debit types
- Random transaction dates
```

### TransactionSeeder
```php
Seeds 100-200 transactions per test user
Distributed across accounts and categories
```

---

## Testing Strategy

### PHPUnit Tests

**Feature Tests** (`tests/Feature/Api/Transactions/`):
```
- test_user_can_list_transactions()
- test_user_can_filter_by_account()
- test_user_can_filter_by_category()
- test_user_can_filter_by_type()
- test_user_can_filter_by_date_range()
- test_user_can_create_transaction()
- test_user_can_view_transaction()
- test_user_can_update_transaction()
- test_user_cannot_change_transaction_type()
- test_user_can_delete_transaction()
- test_user_cannot_view_other_users_transactions()
```

**Service Tests** (`tests/Unit/Services/AccountingService/`):
```
- test_record_transaction_creates_transaction()
- test_record_transaction_updates_monthly_balance()
- test_calculate_balance_with_credits()
- test_calculate_balance_with_debits()
- test_calculate_balance_with_snapshot()
- test_calculate_balance_without_snapshot()
- test_balance_calculation_uses_correct_formula()
- test_credit_card_balance_negative()
- test_bank_account_balance_positive()
```

**Unit Tests** (`tests/Unit/Models/Transaction/`):
```
- test_transaction_has_correct_relationships()
- test_transaction_scope_by_account()
- test_transaction_scope_by_type()
- test_transaction_scope_settled()
```

### Jest Tests
```
- test_transaction_list_renders()
- test_transaction_list_filters_by_account()
- test_transaction_form_validates_amount()
- test_transaction_form_calculates_balance()
- test_balance_display_shows_correct_value()
```

### Coverage Targets
- AccountingService: 90%+
- Transaction model: 85%+
- TransactionController: 85%+
- Overall: 80%+

---

## User Capabilities

After Phase 4 completion, users will be able to:

✅ **Transaction Recording**
- Record income (credit transactions)
- Record expenses (debit transactions)
- Attach categories to transactions
- Add multiple tags per transaction
- Track transaction dates vs settlement dates

✅ **Balance Management**
- View current account balance (calculated from transactions)
- Understand balance changes over time
- Distinguish between transaction and settled dates
- See monthly balance snapshots

✅ **Transaction Management**
- View all transactions for an account
- Filter by category, type, date range
- Edit transaction details
- Delete transactions
- View transaction history

✅ **Financial Insights**
- See total income vs expenses
- Track spending by category
- Analyze balance trends

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass, including AccountingService)
- ✅ Jest tests (all tests pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 80%+ overall, 90%+ for services

---

## Notes

- Transaction amounts always stored as positive (sign determined by type)
- Database transactions used for consistency
- Monthly balance snapshots prevent N+1 queries
- Personal finance logic (not double-entry accounting)
- Soft deletes preserve transaction history

---

## Previous Phase

[Phase 3: Categories and Tags](PHASE_03_CATEGORIES_AND_TAGS.md)

## Next Phase

[Phase 5: Analytics Dashboard](PHASE_05_ANALYTICS_DASHBOARD.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026
