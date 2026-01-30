# Phase 1: Project Foundation

**Goal**: Bootstrap complete development environment with authentication

**Status**: Planned

**Estimated Effort**: 2-3 weeks

---

## Overview

Establish the complete development infrastructure with Docker containerization, authentication system, and CI/CD pipeline. This phase creates the foundation for all subsequent feature development.

---

## Deliverables

### Project Setup
- Laravel installation in workspace
- Sail configuration with PostgreSQL
- Breeze installation with Inertia/React stack
- API routes structure (`/api/v1`) with Sanctum

### Authentication & Authorization
- Working user registration/login (web interface)
- API authentication with Sanctum tokens
- JWT or token-based API authentication

### CI/CD & Quality Gates
- GitHub Actions workflow (`.github/workflows/tests.yml`) with:
  - PostgreSQL service container
  - PHPUnit tests execution
  - Jest for React components
  - Laravel Pint enforcement
  - PHPStan/Larastan analysis
  - Coverage reporting
- All quality gates configured and passing

### Documentation
- Project README with setup instructions
- Environment configuration guide (.env.example)
- First PR with all quality gates passing

---

## Key Files Created

### Configuration Files
- `compose.yaml` - Docker Compose configuration with all services
- `.github/workflows/tests.yml` - CI/CD pipeline
- `phpunit.xml` - PHPUnit configuration
- `jest.config.js` - Jest configuration
- `pint.json` - Code style configuration
- `phpstan.neon` - Static analysis configuration
- `.env.example` - Environment variables template

### Application Files
- `routes/api.php` - API v1 routes
- `routes/web.php` - Web routes
- `routes/auth.php` - Authentication routes
- `app/Providers/AppServiceProvider.php` - Service provider
- `app/Providers/AuthServiceProvider.php` - Auth service provider

### Frontend Files
- `resources/js/Layouts/AppLayout.jsx` - Main layout
- `resources/js/Pages/Auth/Login.jsx` - Login page
- `resources/js/Pages/Auth/Register.jsx` - Registration page
- `resources/js/Pages/Dashboard.jsx` - Dashboard page (placeholder)
- `resources/css/app.css` - Tailwind CSS entry point

---

## Database Schema

### Users Table
```
users
├── id (PK)
├── name
├── email (unique)
├── password (hashed)
├── email_verified_at (nullable)
├── remember_token (nullable)
├── created_at
└── updated_at
```

### API Tokens (Sanctum)
```
personal_access_tokens
├── id (PK)
├── tokenable_type
├── tokenable_id
├── name
├── token (hashed)
├── abilities (JSON)
├── last_used_at (nullable)
├── created_at
└── updated_at
```

---

## API Endpoints

### Authentication (Web)
- `GET /register` - Registration form
- `POST /register` - Register new user
- `GET /login` - Login form
- `POST /login` - Authenticate user
- `POST /logout` - Logout user

### Authentication (API)
- `POST /api/v1/auth/register` - API user registration
- `POST /api/v1/auth/login` - API user login
- `POST /api/v1/auth/logout` - API user logout
- `GET /api/v1/auth/me` - Get authenticated user

### Health Check
- `GET /api/v1/health` - API health status
- `GET /api/v1/version` - API version

---

## React Components

### Authentication Components
- **`Login`** - Login form component
  - Email input
  - Password input
  - Remember me checkbox
  - Submit button
  - Register link

- **`Register`** - Registration form component
  - Name input
  - Email input
  - Password input
  - Password confirmation input
  - Submit button
  - Login link

### Layout Components
- **`AppLayout`** - Main application layout
  - Navigation bar
  - User menu dropdown
  - Logo
  - Responsive sidebar

- **`GuestLayout`** - Guest page layout (for login/register)
  - Centered container
  - Minimal styling

### Pages
- **`Dashboard`** - Placeholder dashboard page
  - Welcome message
  - Quick links to features

---

## Factories & Seeders

### Factories
```
database/factories/
└── UserFactory.php - Generate test user data
```

### Seeders
```
database/seeders/
├── DatabaseSeeder.php - Main seeder entry point
└── UserSeeder.php - Seed test users
```

---

## Testing Strategy

### PHPUnit Tests (Backend)

**Feature Tests** (`tests/Feature/Auth/`):
```
- test_user_can_register()
- test_user_cannot_register_with_duplicate_email()
- test_user_can_login()
- test_user_cannot_login_with_wrong_password()
- test_user_can_logout()
- test_guest_cannot_access_dashboard()
- test_authenticated_user_can_access_dashboard()
```

**API Tests** (`tests/Feature/Api/Auth/`):
```
- test_user_can_register_via_api()
- test_user_can_login_via_api()
- test_user_can_logout_via_api()
- test_user_can_get_profile_via_api()
- test_api_requires_valid_token()
- test_api_returns_401_for_invalid_token()
```

**Unit Tests** (`tests/Unit/`):
```
- test_user_has_correct_attributes()
```

### Jest Tests (Frontend)

**Authentication Tests** (`resources/js/__tests__/`):
```
- test_login_form_renders()
- test_login_form_submits_with_valid_data()
- test_login_form_shows_validation_errors()
- test_register_form_renders()
- test_register_form_submits_with_valid_data()
- test_register_form_shows_validation_errors()
```

### Coverage Targets
- Overall: 70%+
- Controllers: 80%+
- Models: 75%+
- React Components: 65%+ (focus on form logic)

---

## Testing Fixtures

None required for Phase 1.

---

## User Capabilities

After Phase 1 completion, users will be able to:

✅ **Account Management**
- Register new account with email and password
- Login with email and password
- Logout from account
- Access protected dashboard pages

✅ **API Access**
- Generate API tokens for programmatic access
- Authenticate API requests with tokens
- Access `/api/v1` endpoints with proper authentication

✅ **Development**
- Developers can run local development environment with Docker
- Run tests with `docker compose exec truetrack php artisan test`
- Verify code quality with Pint and PHPStan
- Deploy with confidence via GitHub Actions

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass)
- ✅ Jest tests (all tests pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 70%+ overall, 80%+ for controllers

---

## Notes

- Use Laravel Breeze default styling (Tailwind CSS)
- Enforce strict type declarations in all files
- All controllers return JSON responses
- Use Form Requests for validation
- Database transactions are NOT required yet (single user context)

---

## Next Phase

[Phase 2: Accounts Module](PHASE_02_ACCOUNTS_MODULE.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026
