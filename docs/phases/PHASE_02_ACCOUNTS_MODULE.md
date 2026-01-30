# Phase 2: Accounts Module

**Goal**: Enable users to manage their financial accounts

**Status**: Planned

**Estimated Effort**: 2-3 weeks

---

## Overview

Implement the core financial accounts system with support for multiple account types (bank, credit card, wallet, transitional) and balance tracking through monthly snapshots.

---

## Deliverables

### Models
- `Account` model with enums and relationships
- `AccountBalance` model for monthly balance snapshots
- Migrations for both tables with proper indexes

### API
- RESTful API Controller (`Api\V1\AccountController`)
- Pagination, filtering, and sorting support
- All CRUD endpoints (index, store, show, update, destroy)

### Web
- Web Controller (`AccountController`) for Inertia
- React components for account management

### Form Requests
- `StoreAccountRequest` - Validation for account creation
- `UpdateAccountRequest` - Validation for account updates

### Resources & Responses
- `AccountResource` - Formatted API responses
- `AccountBalanceResource` - Balance snapshot responses

### Database
- `create_accounts_table` migration
- `create_account_balances_table` migration
- Indexes on account_id, (account_id, year, month)

### Testing
- Factory: `AccountFactory`
- Seeder: `AccountSeeder`
- Comprehensive PHPUnit tests
- Jest tests for React components

---

## Models

### Account Model

```php
class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'initial_balance',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo { }
    public function balances(): HasMany { }
    public function transactions(): HasMany { }

    // Scopes
    public function scopeActive(Builder $query): Builder { }
    public function scopeByType(Builder $query, AccountType $type): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `user_id` - Foreign key to users
- `name` - Account name (e.g., "Chase Checking")
- `description` - Optional description
- `type` - AccountType enum: `bank`, `credit_card`, `wallet`, `transitional`
- `initial_balance` - Starting balance (decimal, never modified)
- `is_active` - Boolean flag
- `created_at`, `updated_at`, `deleted_at`

### AccountBalance Model

```php
class AccountBalance extends Model
{
    protected $fillable = [
        'account_id',
        'year',
        'month',
        'closing_balance',
    ];

    protected $casts = [
        'closing_balance' => 'decimal:2',
    ];

    // Relationships
    public function account(): BelongsTo { }

    // Scopes
    public function scopeForAccount(Builder $query, int $accountId): Builder { }
    public function scopeForMonth(Builder $query, int $year, int $month): Builder { }
}
```

**Attributes:**
- `id` - Primary key
- `account_id` - Foreign key to accounts
- `year` - Year (integer)
- `month` - Month (1-12)
- `closing_balance` - Balance at end of month (decimal)
- `created_at`, `updated_at`

---

## Database Schema

### accounts Table
```sql
CREATE TABLE accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('bank', 'credit_card', 'wallet', 'transitional') NOT NULL,
    initial_balance DECIMAL(15, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    UNIQUE INDEX idx_user_name (user_id, name)
);
```

### account_balances Table
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

## API Endpoints

### Accounts Endpoints

**GET /api/v1/accounts**
```
Query Parameters:
  - filter[type] - Filter by account type
  - filter[is_active] - Filter by active status
  - sort - Sort by field (e.g., -created_at)
  - per_page - Items per page (default: 15)

Response:
{
  "data": [
    {
      "id": 1,
      "name": "Chase Checking",
      "type": "bank",
      "initial_balance": 1000.00,
      "current_balance": 1050.00,
      "is_active": true,
      "created_at": "2026-01-15T10:30:00Z"
    }
  ],
  "meta": { ... },
  "links": { ... }
}
```

**POST /api/v1/accounts**
```
Request:
{
  "name": "Chase Checking",
  "description": "Primary checking account",
  "type": "bank",
  "initial_balance": 1000.00,
  "is_active": true
}

Response (201 Created):
{
  "data": {
    "id": 1,
    "name": "Chase Checking",
    "type": "bank",
    "initial_balance": 1000.00,
    "current_balance": 1000.00,
    "is_active": true,
    "created_at": "2026-01-15T10:30:00Z"
  }
}
```

**GET /api/v1/accounts/{id}**
```
Response:
{
  "data": {
    "id": 1,
    "name": "Chase Checking",
    "type": "bank",
    "initial_balance": 1000.00,
    "current_balance": 1050.00,
    "description": "Primary checking account",
    "is_active": true,
    "created_at": "2026-01-15T10:30:00Z",
    "updated_at": "2026-01-20T14:22:00Z"
  }
}
```

**PUT /api/v1/accounts/{id}**
```
Request:
{
  "name": "Chase Checking",
  "description": "Updated description",
  "is_active": true
}

Response (200 OK):
{
  "data": { ... }
}
```

**DELETE /api/v1/accounts/{id}**
```
Response (204 No Content)
```

---

## React Components

### AccountList Component
```jsx
export default function AccountList({ accounts, onEdit, onDelete })
```

**Features:**
- Display accounts in table format
- Columns: Name, Type, Initial Balance, Current Balance, Status, Actions
- Filters: Account type, active status
- Sorting: Clickable column headers
- Actions: Edit, Delete buttons
- "Create Account" button
- Loading state while fetching
- Empty state message

### AccountForm Component
```jsx
export default function AccountForm({ account, onSubmit })
```

**Features:**
- Name input (required, max 100)
- Description textarea (optional, max 500)
- Type dropdown (bank, credit_card, wallet, transitional)
- Initial balance input (required, decimal with 2 places)
- Active status toggle
- Form validation with error messages
- Submit button
- Cancel button
- Loading state during submission

### AccountBalance Component
```jsx
export default function AccountBalance({ account })
```

**Features:**
- Display current account balance
- Show monthly balance history (chart)
- Compare to initial balance
- Display balance trend

---

## Form Requests

### StoreAccountRequest
```
Validation Rules:
- name: required, string, max:100, unique per user
- description: nullable, string, max:500
- type: required, in:bank,credit_card,wallet,transitional
- initial_balance: required, numeric, min:0
- is_active: boolean
```

### UpdateAccountRequest
```
Validation Rules:
- name: required, string, max:100, unique per user (except current)
- description: nullable, string, max:500
- is_active: boolean

Note: Cannot update initial_balance or type after creation
```

---

## Factories & Seeders

### AccountFactory
```php
Factory::define(Account::class, function (Faker $faker) {
    return [
        'user_id' => User::factory(),
        'name' => $faker->bothify('?# ?????'),
        'description' => $faker->sentence,
        'type' => fake()->randomElement(AccountType::cases()),
        'initial_balance' => $faker->numberBetween(100, 10000),
        'is_active' => true,
    ];
});
```

### AccountSeeder
```php
Seeds 5 accounts per user with various types and balances
```

---

## Testing Strategy

### PHPUnit Tests

**Feature Tests** (`tests/Feature/Api/Accounts/`):
```
- test_user_can_list_accounts()
- test_user_can_filter_accounts_by_type()
- test_user_can_create_account()
- test_user_cannot_create_account_with_invalid_type()
- test_user_can_view_account_details()
- test_user_can_update_account()
- test_user_cannot_update_account_type()
- test_user_cannot_update_initial_balance()
- test_user_can_delete_account()
- test_user_cannot_view_other_users_accounts()
- test_user_cannot_modify_other_users_accounts()
```

**Unit Tests** (`tests/Unit/Models/`):
```
- test_account_has_correct_relationships()
- test_account_scope_active()
- test_account_scope_by_type()
- test_account_balance_calculation()
```

### Jest Tests
```
- test_account_list_renders_with_data()
- test_account_list_filters_by_type()
- test_account_form_renders()
- test_account_form_validates_input()
- test_account_form_submits_create_request()
- test_account_form_submits_update_request()
```

### Coverage Targets
- Overall: 75%+
- AccountController: 85%+
- Account model: 80%+
- React components: 70%+

---

## User Capabilities

After Phase 2 completion, users will be able to:

✅ **Account Management**
- Create multiple financial accounts (bank, credit card, wallet, transitional)
- Set initial balance for each account
- View list of all their accounts
- View account details
- Edit account name and description
- Deactivate accounts (soft delete)
- Delete accounts

✅ **Account Types**
- Bank accounts (for checking/savings)
- Credit card accounts (for credit cards)
- Wallet accounts (for cash on hand)
- Transitional accounts (for transfers between accounts)

✅ **Balance Tracking**
- Track initial balance set at account creation
- View current balance (calculated from transactions)
- See balance history over time

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass)
- ✅ Jest tests (all tests pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 75%+ overall, 85%+ for controllers

---

## Notes

- Account balances calculated from transactions (not stored directly)
- Monthly snapshots store closing balance for performance
- Users can only view/modify their own accounts (enforced via User policy)
- Soft deletes preserve account history for reporting

---

## Previous Phase

[Phase 1: Project Foundation](PHASE_01_PROJECT_FOUNDATION.md)

## Next Phase

[Phase 3: Categories and Tags](PHASE_03_CATEGORIES_AND_TAGS.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026
