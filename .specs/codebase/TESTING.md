# Testing Infrastructure

## Test Frameworks

**Backend:** PHPUnit 11.x (`phpunit/phpunit ^11.5.3`) + Mockery (`mockery/mockery ^1.6`)
**Frontend:** Jest 29.x (`jest ^29.7.0`) + Testing Library (`@testing-library/react ^14.1.2`)
**Coverage:** PHPUnit HTML + text output (`coverage/html/`); Jest `--coverage`

## Test Organization

**Backend location:** `tests/` — split into `Feature/` and `Unit/`
**Frontend location:** `resources/js/__tests__/`
**Naming:** `[Subject]Test.php` (PHP), `[Component].test.jsx` (JS)
**Helpers:** `tests/Helpers/XlsxTestHelper.php`
**Fixtures:** `tests/fixtures/` (XLSX, CSV test files)

## Testing Patterns

### Feature Tests (Controllers/Endpoints)

**Traits:** `RefreshDatabase`
**Setup:** Creates user in `setUp()`, uses `actingAs($this->user)` for auth
**Pattern:**
```php
class AccountControllerTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_store_account(): void {
        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), $accountData);
        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [...]);
    }
}
```
**Assertions:** `assertStatus`, `assertRedirect`, `assertSessionHas`, `assertDatabaseHas`, `assertSessionHasErrors`

### API Tests

**Location:** `tests/Feature/Api/V1/`
**Pattern:** `postJson`/`getJson`/`putJson`/`deleteJson` with `assertStatus`, `assertJsonStructure`
**Auth:** `actingAs($user)` with Sanctum

### Unit Tests (Services)

**Traits:** `RefreshDatabase` (services interact with DB)
**Setup:** Resolves service from container: `$this->service = app(AutoCategorizationService::class)`
**Pattern:** Creates factories for test data, calls service methods, asserts results
**Assertions:** `assertEquals`, `assertNull`, `assertTrue`, `assertCount`

### Model Tests

**Location:** `tests/Unit/Models/`
**Pattern:** Tests relationships, scopes, casts, custom methods

### Frontend Tests (Jest)

**Location:** `resources/js/__tests__/`
**Setup:** `jest.setup.js` mocks Ziggy `route()` helper globally, imports `@testing-library/jest-dom`
**Environment:** `jsdom`
**Module aliases:** `@/` → `resources/js/`, CSS → `identity-obj-proxy`
**Transform:** Babel with `@babel/preset-env` + `@babel/preset-react`

## Test Base Class

`tests/TestCase.php` — creates fake Vite manifest in `setUp()`, removes it in `tearDown()`. Disables CSRF middleware for web route tests.

## Test Execution

**Backend:**
```bash
docker compose exec truetrack php artisan test                    # All tests
docker compose exec truetrack php artisan test --testsuite=Unit   # Unit only
docker compose exec truetrack php artisan test --testsuite=Feature # Feature only
docker compose exec truetrack php artisan test --parallel --processes=4  # Parallel
docker compose exec truetrack php artisan test --coverage --min=70      # With coverage
```

**Frontend:**
```bash
npm test                # All Jest tests
npm run test:watch      # Watch mode
npm run test:coverage   # With coverage
```

**Code quality:**
```bash
docker compose exec truetrack ./vendor/bin/pint          # Fix style
docker compose exec truetrack ./vendor/bin/phpstan analyse  # Static analysis
```

## Coverage Targets

- Services: 80%+
- Overall: 70%+
- All controller endpoints tested
- Enforcement: CI quality gates

## Test Coverage Matrix

| Code Layer | Required Test Type | Location Pattern | Run Command |
| --- | --- | --- | --- |
| Models | Unit | `tests/Unit/Models/*Test.php` | `php artisan test --testsuite=Unit` |
| Services | Unit | `tests/Unit/Services/*Test.php` | `php artisan test --testsuite=Unit` |
| Jobs | Unit | `tests/Unit/Jobs/*Test.php` | `php artisan test --testsuite=Unit` |
| Resources | Unit | `tests/Unit/Resources/*Test.php` | `php artisan test --testsuite=Unit` |
| Web Controllers | Feature | `tests/Feature/*ControllerTest.php` | `php artisan test --testsuite=Feature` |
| API Controllers | Feature | `tests/Feature/Api/V1/*Test.php` | `php artisan test --testsuite=Feature` |
| Observers | Integration | `tests/Feature/Integration/*Test.php` | `php artisan test --testsuite=Feature` |
| Commands | Feature | `tests/Feature/Console/*Test.php` | `php artisan test --testsuite=Feature` |
| React Components | Unit (Jest) | `resources/js/__tests__/**/*.test.jsx` | `npm test` |

## Parallelism Assessment

| Test Type | Parallel-Safe? | Isolation Model | Evidence |
| --- | --- | --- | --- |
| PHPUnit (all) | Yes | Per-test DB via `RefreshDatabase` trait; separate `testing` database created by Sail init SQL | `compose.yaml`: `create-testing-database.sql`, `phpunit.xml`: `DB_DATABASE=testing` |
| Jest | Yes | No shared state; all components rendered in isolated jsdom; route helper mocked globally | `jest.setup.js`: global mock, no DB access |

## Gate Check Commands

| Gate Level | When to Use | Command |
| --- | --- | --- |
| Quick | After unit-level changes | `docker compose exec truetrack php artisan test --testsuite=Unit` |
| Full | After feature/endpoint changes | `docker compose exec truetrack php artisan test` |
| Build | After phase completion | `docker compose exec truetrack ./vendor/bin/pint --test && docker compose exec truetrack ./vendor/bin/phpstan analyse && docker compose exec truetrack php artisan test --coverage --min=70 && npm test` |
