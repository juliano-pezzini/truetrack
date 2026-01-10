# GitHub Copilot Instructions for TrueTrack

## Project Overview

TrueTrack is a cloud-based personal finance management system built with **Laravel 11.x**, **PostgreSQL**, **Inertia.js**, and **React 18+**. The system follows **double-entry accounting principles** and uses an **API-first architecture** to support both web and future mobile applications.

## Core Technology Stack

- **Backend**: PHP 8.2+, Laravel 11.x
- **Database**: PostgreSQL 15+
- **Frontend**: React 18+ with Inertia.js
- **Authentication**: Laravel Breeze with Sanctum for API
- **Testing**: PHPUnit (backend), Jest (React)
- **Code Quality**: Laravel Pint, PHPStan/Larastan
- **Development Environment**: Docker Compose

## Docker/Sail Container Usage

**CRITICAL**: TrueTrack runs in Docker containers. All Laravel-related commands (artisan, composer, PHPUnit, Pint, PHPStan, etc.) MUST be executed inside the Docker container.

### Running Commands in Containers

1. **Always navigate to the `workspace` directory first** before running Docker commands
2. **Command Format**: `docker compose exec truetrack <command>`
3. **Instance Name**: The application container is named `truetrack`

### Common Command Examples

```bash
# Navigate to workspace directory
cd workspace

# Run artisan commands
docker compose exec truetrack php artisan migrate
docker compose exec truetrack php artisan db:seed
docker compose exec truetrack php artisan make:controller AccountController

# Run composer commands
docker compose exec truetrack composer install
docker compose exec truetrack composer update
docker compose exec truetrack composer require package/name

# Run tests
docker compose exec truetrack php artisan test
docker compose exec truetrack ./vendor/bin/phpunit

# Run code quality tools
docker compose exec truetrack ./vendor/bin/pint
docker compose exec truetrack ./vendor/bin/phpstan analyse

# Database commands
docker compose exec truetrack php artisan migrate:fresh --seed
docker compose exec truetrack php artisan migrate:rollback

# Access PHP container shell
docker compose exec truetrack bash
```

### Important Notes

- **NEVER** run Laravel/PHP commands directly on the host system
- **ALWAYS** prefix commands with `docker compose exec truetrack`
- **Frontend/Node commands** (npm, jest) can run on the host or in containers as needed
- When in doubt, run the command through Docker
- Ensure Docker containers are running before executing commands: `docker compose up -d`

## Code Style & Conventions

### PHP/Laravel Standards

1. **Follow Laravel conventions strictly**:
   - Use Eloquent models with proper relationships
   - Follow RESTful resource controller patterns
   - Use Form Request classes for validation
   - Implement Service classes for complex business logic
   - Use Repository pattern only when necessary

2. **Code Style**:
   - Follow Laravel Pint configuration in `pint.json`
   - Use strict types: `declare(strict_types=1);`
   - Type hint all parameters and return types
   - Use PHP 8.2+ features (readonly properties, enums, etc.)
   - Prefer named arguments for clarity when needed

3. **Naming Conventions**:
   - Controllers: Singular resource name + `Controller` (e.g., `AccountController`)
   - Models: Singular (e.g., `Account`, `Transaction`)
   - Services: Purpose + `Service` (e.g., `AccountingService`, `ReconciliationService`)
   - Form Requests: Action + Resource + `Request` (e.g., `StoreAccountRequest`)
   - Database tables: Plural snake_case (e.g., `accounts`, `transactions`)
   - Pivot tables: Alphabetically ordered singular names (e.g., `reconciliation_transaction`)

4. **Models**:
   - Always use `$fillable` or `$guarded`
   - Define `$casts` for dates, booleans, enums
   - Use soft deletes where appropriate
   - Define relationships with proper type hints
   - Keep models thin; move complex logic to Services

5. **Controllers**:
   - Keep controllers thin; delegate to Services
   - Use dependency injection
   - Return appropriate HTTP status codes
   - Use API Resources for formatting responses
   - Implement consistent error handling

### JavaScript/React Standards

1. **React Conventions**:
   - Use functional components with hooks
   - Use destructuring for props
   - Keep components focused and single-purpose
   - Extract reusable logic into custom hooks
   - Use proper PropTypes or TypeScript types if migrated

2. **Inertia.js Patterns**:
   - Use `usePage()` hook for accessing shared data
   - Use `useForm()` hook for form handling
   - Handle errors consistently with Inertia's error bag
   - Use `router` methods for navigation
   - Preserve scroll position where appropriate

3. **File Organization**:
   - Components in `resources/js/Components/`
   - Pages in `resources/js/Pages/`
   - Layouts in `resources/js/Layouts/`
   - Shared utilities in appropriate subdirectories

4. **Naming**:
   - Components: PascalCase (e.g., `AccountForm.jsx`)
   - Hooks: camelCase with `use` prefix (e.g., `useAccountBalance.js`)
   - Utilities: camelCase (e.g., `formatCurrency.js`)

## API Design Principles

### Versioning & Structure

1. **API Base Path**: Always use `/api/v1` prefix
2. **Resource Endpoints**: Follow RESTful conventions:
   - `GET /api/v1/accounts` - List accounts
   - `POST /api/v1/accounts` - Create account
   - `GET /api/v1/accounts/{id}` - Show account
   - `PUT/PATCH /api/v1/accounts/{id}` - Update account
   - `DELETE /api/v1/accounts/{id}` - Delete account

3. **Response Format**:
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

4. **Filtering & Sorting**:
   - Use query parameters: `?filter[type]=bank&filter[is_active]=1`
   - Sorting: `?sort=-created_at` (prefix `-` for descending)
   - Field selection: `?fields=id,name,balance`
   - Always implement pagination for list endpoints

5. **Status Codes**:
   - `200 OK` - Successful GET, PUT, PATCH
   - `201 Created` - Successful POST
   - `204 No Content` - Successful DELETE
   - `400 Bad Request` - Validation errors
   - `401 Unauthorized` - Authentication required
   - `403 Forbidden` - Insufficient permissions
   - `404 Not Found` - Resource not found
   - `422 Unprocessable Entity` - Validation failed
   - `500 Internal Server Error` - Server errors

### Authentication

1. **Web Routes**: Use session-based authentication (Laravel Breeze)
2. **API Routes**: Use token-based authentication (Sanctum)
3. Always validate authentication in controllers/middleware
4. Protect sensitive endpoints appropriately

## Database Conventions

### Migrations

1. **Naming**: Use descriptive names with timestamp prefix
2. **Structure**:
   - Always add indexes for foreign keys
   - Use `$table->foreignId()` for foreign keys
   - Include `->constrained()->cascadeOnDelete()` when appropriate
   - Add soft deletes: `$table->softDeletes()`
   - Always include timestamps: `$table->timestamps()`

3. **Data Types**:
   - Use `decimal(15, 2)` for monetary values
   - Use enums for fixed sets of values
   - Use `boolean` over `tinyInteger` for flags
   - Use `date` for transaction dates, `timestamp` for system events

### Models & Relationships

1. **Relationships**:
   - Use proper relationship methods (hasMany, belongsTo, belongsToMany)
   - Always type hint return types: `public function account(): BelongsTo`
   - Use eager loading to prevent N+1 queries
   - Define inverse relationships

2. **Scopes**:
   - Create query scopes for common filters
   - Use descriptive scope names (e.g., `scopeActive`, `scopeByType`)

3. **Accessors & Mutators**:
   - Use attribute casting over accessors when possible
   - Format data for presentation in API Resources, not accessors

## Testing Requirements

### PHPUnit (Backend)

1. **Test Structure**:
   - Feature tests in `tests/Feature/`
   - Unit tests in `tests/Unit/`
   - Use `RefreshDatabase` trait in feature tests
   - Use factories for test data generation

2. **Coverage Requirements**:
   - Services: 80%+ coverage
   - Controllers: Test all endpoints
   - Models: Test custom methods and scopes
   - Overall: 70%+ coverage

3. **Test Patterns**:
   ```php
   public function test_can_create_account(): void
   {
       $user = User::factory()->create();
       
       $response = $this->actingAs($user)
           ->postJson('/api/v1/accounts', [
               'name' => 'Test Account',
               'type' => 'bank',
               'balance' => 1000.00,
           ]);
       
       $response->assertStatus(201)
           ->assertJsonStructure(['data' => ['id', 'name', 'type', 'balance']]);
       
       $this->assertDatabaseHas('accounts', [
           'name' => 'Test Account',
           'type' => 'bank',
       ]);
   }
   ```

4. **Testing Guidelines**:
   - Test happy paths and edge cases
   - Test validation rules
   - Test authorization/permissions
   - Test business logic in Services
   - Mock external dependencies

### Jest (Frontend)

1. **Component Testing**:
   - Test complex forms (validation, submission)
   - Test business logic calculations
   - Test user interactions
   - Focus on behavior, not implementation details

2. **Coverage**:
   - Focus on components with business logic
   - Don't test purely presentational components
   - Test custom hooks thoroughly

## Business Logic: Double-Entry Accounting

### Core Principles

1. **Every transaction must have matching debits and credits**
2. **Account balance updates must be atomic**
3. **Transaction reversals must be properly handled**

### Accounting Rules

1. **Account Types**:
   - `bank`: Standard checking/savings accounts
   - `credit_card`: Credit card accounts (negative balance = debt)
   - `wallet`: Cash on hand
   - `transitional`: Temporary accounts for transfers

2. **Transaction Types**:
   - `debit`: Increases bank/wallet, decreases credit card debt
   - `credit`: Decreases bank/wallet, increases credit card debt

3. **Balance Calculations**:
   - Always use database transactions for balance updates
   - Lock affected account rows during updates
   - Validate balance integrity before committing

### Service Layer Pattern

```php
class AccountingService
{
    public function recordTransaction(array $data): Transaction
    {
        DB::beginTransaction();
        
        try {
            // Validate double-entry rules
            $this->validateDoubleEntry($data);
            
            // Create transaction
            $transaction = Transaction::create($data);
            
            // Update account balances
            $this->updateAccountBalance($transaction);
            
            DB::commit();
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

## Security Best Practices

1. **Input Validation**:
   - Always use Form Requests for validation
   - Validate all user input before processing
   - Use Laravel's built-in validation rules
   - Sanitize input when necessary

2. **Authorization**:
   - Use policies for resource authorization
   - Check authorization in controllers
   - Use gates for non-resource permissions

3. **SQL Injection Prevention**:
   - Always use Eloquent or Query Builder
   - Use parameter binding for raw queries
   - Never concatenate user input into queries

4. **Mass Assignment Protection**:
   - Define `$fillable` or `$guarded` on all models
   - Be explicit about fillable fields

5. **API Security**:
   - Rate limit API endpoints
   - Use CORS properly
   - Validate API tokens on every request

## Error Handling

### Backend

1. **Use appropriate exceptions**:
   - `ValidationException` for validation errors
   - `ModelNotFoundException` for missing resources
   - `AuthorizationException` for permission errors
   - Custom exceptions for business logic errors

2. **Log errors appropriately**:
   - Log all 500 errors
   - Log security-related issues
   - Use proper log levels

### Frontend

1. **Handle Inertia errors**:
   - Display validation errors next to form fields
   - Show global errors in a consistent location
   - Provide user-friendly error messages

2. **API Error Handling**:
   - Handle network failures gracefully
   - Show loading states during requests
   - Provide retry mechanisms when appropriate

## Performance Considerations

1. **Database**:
   - Use eager loading to prevent N+1 queries
   - Add indexes for frequently queried columns
   - Use pagination for large datasets
   - Consider database transactions for consistency

2. **Caching**:
   - Cache expensive calculations
   - Use Redis for session and cache storage in production
   - Implement cache invalidation strategies

3. **Frontend**:
   - Lazy load components when appropriate
   - Optimize images and assets
   - Minimize bundle size
   - Use React.memo for expensive components

## Documentation Standards

1. **PHPDoc Blocks**:
   - Document all public methods
   - Include `@param` and `@return` tags
   - Add `@throws` for exceptions
   - Document complex logic with inline comments

2. **API Documentation**:
   - Document endpoints with examples
   - Include request/response samples
   - Document query parameters and filters
   - Keep documentation updated with code

3. **README Updates**:
   - Update setup instructions when dependencies change
   - Document environment variables
   - Include deployment procedures

## Git Commit Conventions

- `feat:` New feature
- `fix:` Bug fix
- `test:` Test additions/modifications
- `refactor:` Code restructuring
- `docs:` Documentation updates
- `chore:` Maintenance tasks
- `perf:` Performance improvements

## Quality Gates

Every PR must pass:
- ✅ PHPUnit tests (all must pass)
- ✅ Jest tests (all must pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan/Larastan Level 5+ (no errors)
- ✅ Coverage thresholds (70%+ overall, 80%+ for services)

## Common Patterns to Follow

### Controller Pattern
```php
public function index(Request $request): JsonResponse
{
    $accounts = Account::query()
        ->when($request->filter['type'] ?? null, fn($q, $type) => $q->where('type', $type))
        ->when($request->sort ?? null, fn($q, $sort) => $q->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc'))
        ->paginate($request->per_page ?? 15);
    
    return AccountResource::collection($accounts);
}
```

### Service Pattern
```php
class AccountingService
{
    public function __construct(
        private TransactionRepository $transactions,
        private AccountRepository $accounts
    ) {}
    
    public function recordTransaction(array $data): Transaction
    {
        // Business logic here
    }
}
```

### React Component Pattern
```jsx
export default function AccountForm({ account, onSubmit }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: account?.name ?? '',
        type: account?.type ?? 'bank',
        balance: account?.balance ?? 0,
    });
    
    const handleSubmit = (e) => {
        e.preventDefault();
        account ? put(route('accounts.update', account)) : post(route('accounts.store'));
    };
    
    return (
        <form onSubmit={handleSubmit}>
            {/* Form fields */}
        </form>
    );
}
```

## Avoiding Common Pitfalls

1. **Don't bypass validation** - Always validate input
2. **Don't skip tests** - Write tests as you code
3. **Don't ignore static analysis** - Fix PHPStan errors immediately
4. **Don't commit commented code** - Remove or document properly
5. **Don't hardcode values** - Use configuration files
6. **Don't forget error handling** - Handle exceptions properly
7. **Don't skip database transactions** - Use transactions for data integrity
8. **Don't forget authorization checks** - Verify permissions
9. **Don't use raw SQL** - Use Eloquent/Query Builder
10. **Don't ignore N+1 queries** - Use eager loading

---

**Remember**: This is a financial application. Data integrity, security, and accuracy are paramount. Always validate accounting logic thoroughly.
