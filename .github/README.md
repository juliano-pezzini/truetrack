# GitHub Actions CI/CD

This directory contains the CI/CD workflows for TrueTrack.

## Workflows

### Tests Workflow (`tests.yml`)

Runs on every push and pull request to `main` and `develop` branches.

**What it does:**
- Sets up PHP 8.4 with required extensions
- Sets up Node.js 20
- Installs Composer and NPM dependencies
- Runs database migrations on PostgreSQL 18
- Executes all quality checks:
  - ✅ Laravel Pint (code style)
  - ✅ PHPStan (static analysis)
  - ✅ PHPUnit (backend tests with 70% coverage minimum)
  - ✅ Jest (frontend tests)
- Uploads coverage reports as artifacts

**Quality Gates:**
- All checks must pass for the PR to be mergeable
- Manual approval can bypass quality gates for exceptional cases
- Coverage reports are available in the Actions artifacts

## Local Testing

Before pushing, you can run the same checks locally:

```bash
# Code style
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse

# Backend tests
php artisan test --coverage --min=70

# Frontend tests
npm test -- --coverage
```

## Troubleshooting

### If tests fail in CI but pass locally:
1. Check the PostgreSQL version (CI uses PostgreSQL 18)
2. Verify environment variables in `.env.example`
3. Check if migrations are up to date

### Manual Quality Gate Bypass:
If you need to merge despite failing checks:
1. The workflow will show a warning
2. A repository admin can manually approve and merge
3. Create an issue to fix the failing checks ASAP
