# TrueTrack Development Plan

## Overview

Cloud-based personal finance management system with double-entry accounting principles, built with Laravel, PostgreSQL, and Inertia.js/React. API-first architecture to support both web and future mobile applications.

## Technology Stack

- **Backend**: PHP 8.2+ with Laravel 11.x
- **Database**: PostgreSQL 15+ (AWS Aurora Serverless v2 in production)
- **Development Environment**: Laravel Sail (Docker)
- **Frontend**: Inertia.js with React 18+
- **Authentication**: Laravel Breeze with Sanctum for API
- **API**: RESTful with versioning (`/api/v1`)
- **Testing**: PHPUnit (backend), Jest (React components)
- **CI/CD**: GitHub Actions with strict quality gates

## Quality Standards

All Pull Requests must pass:
- ✅ PHPUnit tests (Feature, Unit, Integration)
- ✅ Jest tests for complex React components
- ✅ Laravel Pint (code style)
- ✅ PHPStan/Larastan (static analysis)
- ✅ Coverage thresholds (configurable)
- ⚠️ Manual approval bypass available for exceptional situations

## Development Phases

### Phase 1: Project Foundation
**Goal**: Bootstrap complete development environment with authentication

**Deliverables**:
- Laravel installation in workspace
- Sail configuration with PostgreSQL
- Breeze installation with Inertia/React stack
- API routes structure (`/api/v1`) with Sanctum
- GitHub Actions workflow (`.github/workflows/tests.yml`) with:
  - PostgreSQL service container
  - PHPUnit tests
  - Jest for React components
  - Laravel Pint enforcement
  - PHPStan/Larastan analysis
  - Coverage reporting
- Working user registration/login (web + API)
- First PR with all quality gates passing

**Key Files**:
- `docker-compose.yml` (Sail)
- `.github/workflows/tests.yml`
- `routes/api.php` (v1 routes)
- `phpunit.xml`
- `jest.config.js`
- `pint.json`
- `phpstan.neon`

---

### Phase 2: Accounts Module
**Goal**: Enable users to manage their financial accounts

**Deliverables**:
- `Account` model with attributes:
  - `type` enum: bank, credit_card, wallet, transitional
  - `name`, `description`
  - `balance` (decimal)
  - `is_active` (boolean)
  - Timestamps, soft deletes
- Database migration with indexes
- API Controller (`Api\V1\AccountController`) with:
  - Pagination support
  - Filtering (by type, active status)
  - Sorting capabilities
  - RESTful endpoints (index, store, show, update, destroy)
- Web Controller (`AccountController`) for Inertia
- React components:
  - `AccountList` (with filters)
  - `AccountForm` (create/edit)
- Form Request validation
- Factory and Seeder
- PHPUnit tests:
  - Feature tests (API endpoints)
  - Feature tests (Inertia responses)
  - Unit tests (model methods)
- Jest tests for forms
- All quality gates passing

**User Capability**: Create, view, edit, and delete financial accounts with current balances via web interface and API.

---

### Phase 3: Categories and Tags
**Goal**: Provide financial taxonomy for organizing transactions

**Deliverables**:
- `Category` model:
  - `name`, `description`
  - `type` enum: revenue, expense
  - `parent_id` (for subcategories)
  - Timestamps, soft deletes
- `Tag` model:
  - `name`, `color`
  - Timestamps
- Database migrations with indexes
- Many-to-many relationship (Transaction-Tag pivot)
- API Controllers with pagination/filtering
- Web Controllers for Inertia
- React components:
  - `CategoryList` and `CategoryForm`
  - `TagList` and `TagForm`
- Form Request validation
- Factories and Seeders
- PHPUnit tests (comprehensive)
- Jest tests for complex forms
- All quality gates passing

**User Capability**: Organize complete financial structure with revenue/expense categories and flexible tags via web and API.

---

### Phase 4: Transactions with Double-Entry Accounting
**Goal**: Record financial transactions with accounting integrity

**Deliverables**:
- `Transaction` model:
  - `account_id` (foreign key)
  - `category_id` (foreign key)
  - `amount` (decimal)
  - `description`
  - `transaction_date` (date)
  - `settled_date` (date, nullable)
  - `type` enum: debit, credit
  - Timestamps, soft deletes
- `AccountingService`:
  - Validate double-entry rules (debit = credit)
  - Update account balances
  - Handle transaction reversals
  - Unit tests for business logic
- API endpoints with pagination/filtering:
  - By date range
  - By account
  - By category
  - By tags
  - By settlement status
- Web Controller for Inertia
- React components:
  - `TransactionList` (with filters)
  - `TransactionForm` (with account/category/tag dropdowns)
  - Balance display
- Form Request validation
- Factory and Seeder
- PHPUnit tests:
  - Feature tests (CRUD operations)
  - Integration tests (balance updates)
  - Unit tests (AccountingService)
  - Test double-entry integrity
- Jest tests for form logic
- Coverage thresholds met
- All quality gates passing

**User Capability**: Record income and expenses with automatic balance calculations, track transaction vs settlement dates, categorize and tag entries via web and API.

---

### Phase 5: Analytics Dashboard
**Goal**: Provide comprehensive financial insights and projections

**Deliverables**:
- `ReportingService`:
  - Calculate period profit/loss (revenue - expenses)
  - Generate monthly cash flow projections
  - Identify spending patterns by category
  - Track investment returns
  - Alert on accounts at risk (negative balance)
  - Alert on credit card balances vs available funds
  - Unit tests for all calculations
- API endpoints:
  - `/api/v1/reports/period-summary` (with date filters)
  - `/api/v1/reports/cash-flow-projection`
  - `/api/v1/reports/spending-by-category`
  - `/api/v1/reports/investment-returns`
  - `/api/v1/reports/alerts`
- Web Controller for dashboard
- React components:
  - `Dashboard` (main layout)
  - `PeriodSummaryCard` (profit/loss)
  - `CashFlowChart` (projections)
  - `SpendingChart` (by category)
  - `AlertsList` (risk notifications)
  - `InvestmentSummary`
- Charts library integration (e.g., Recharts)
- Jest tests for complex visualizations
- PHPUnit tests for ReportingService
- Static analysis passing
- All quality gates passing

**User Capability**: View real-time financial status, analyze spending patterns, track investment returns, receive alerts for accounts at risk and credit card payment requirements via dashboard.

---

### Phase 6: Bank and Credit Card Reconciliation
**Goal**: Verify transactions against actual statements

**Deliverables**:
- `Reconciliation` model:
  - `account_id` (foreign key)
  - `statement_date` (date)
  - `statement_balance` (decimal)
  - `status` enum: pending, completed
  - `reconciled_at` (timestamp)
  - `reconciled_by` (user_id)
  - Timestamps
- `ReconciliationService`:
  - Match transactions with statement entries
  - Calculate discrepancies
  - Handle credit card monthly closure
  - Create offsetting transactions for payment
  - Unit tests for matching algorithms
- Pivot table: `reconciliation_transaction`
- API endpoints with pagination:
  - Create reconciliation
  - Add/remove transaction matches
  - Complete reconciliation
  - Credit card closure workflow
- Web Controllers for Inertia
- React components:
  - `ReconciliationList`
  - `ReconciliationForm` (statement upload)
  - `TransactionMatcher` (pairing interface)
  - `CreditCardClosure` (monthly workflow)
  - `DiscrepancyReport`
- Form validation
- Factories and Seeders
- PHPUnit tests:
  - Feature tests (reconciliation CRUD)
  - Integration tests (matching logic)
  - Unit tests (ReconciliationService)
  - Test credit card closure workflow
- Jest tests for complex matching UI
- All quality gates passing

**User Capability**: Upload bank and credit card statements, match transactions, identify discrepancies, and perform monthly credit card closures via web interface and API.

---

## Database Schema Overview

### Core Tables
- `users` - User accounts (from Breeze)
- `accounts` - Financial accounts (bank, credit card, wallet, transitional)
- `categories` - Revenue/Expense categories (hierarchical)
- `tags` - Flexible transaction grouping
- `transactions` - Financial entries with double-entry support
- `reconciliations` - Bank/credit card statement reconciliation records

### Pivot Tables
- `reconciliation_transaction` - Links transactions to reconciliations
- `tag_transaction` - Many-to-many (transactions and tags)

### Key Constraints
- Foreign keys with cascading rules
- Check constraints for accounting integrity
- Unique indexes for performance
- Soft deletes for audit trail

---

## API Design Principles

### Versioning
- Base path: `/api/v1`
- Version in URL (not headers)
- Maintain backward compatibility within version

### Response Format
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

### Filtering & Sorting
- Query parameters: `?filter[type]=bank&sort=-created_at`
- Cursor pagination for large datasets
- Field selection: `?fields=id,name,balance`

### Authentication
- Web: Session-based (Laravel Breeze)
- API: Token-based (Sanctum)
- Scopes for granular permissions (future)

---

## Testing Strategy

### Backend (PHPUnit)
- **Unit Tests**: Services, models, business logic
- **Feature Tests**: API endpoints, Inertia responses
- **Integration Tests**: Multi-model operations (accounting, reconciliation)
- **Database**: RefreshDatabase trait, factories
- **Coverage Target**: 80%+ for services, 70%+ overall

### Frontend (Jest)
- **Component Tests**: Complex forms (AccountForm, TransactionForm)
- **Logic Tests**: Calculations, validations
- **Integration Tests**: Form submission flows
- **Coverage Target**: Focus on business logic, not presentational components

### CI/CD Pipeline
```yaml
# .github/workflows/tests.yml
- PostgreSQL service container
- PHP 8.2+ with extensions
- Node.js for frontend
- Composer install
- NPM install & build
- Database migrations
- PHPUnit execution
- Jest execution
- Laravel Pint check
- PHPStan/Larastan analysis
- Coverage report generation
- Quality gates enforcement (with manual bypass option)
```

---

## Future Considerations

### Post-Phase 6 Features
1. **AWS Deployment**
   - Create `.github/workflows/deploy.yml`
   - Aurora PostgreSQL Serverless v2 setup
   - EC2 or Fargate for application
   - S3 for file storage
   - CloudFront CDN

2. **Multi-Currency Support**
   - Add currency fields to accounts/transactions
   - Exchange rate tracking
   - Multi-currency reports

3. **Mobile Application**
   - React Native app
   - Consume existing `/api/v1` endpoints
   - Offline support with sync

4. **Advanced Features**
   - Recurring transactions
   - Budget planning
   - Financial goals tracking
   - Import from banks (OFX/CSV)
   - Export reports (PDF, Excel)
   - Multi-tenancy (family accounts)

5. **Performance Optimization**
   - Redis for caching and queues
   - Database query optimization
   - Background job processing
   - CDN for static assets

6. **Monitoring & Observability**
   - Laravel Telescope (local)
   - Error tracking (Sentry/Bugsnag)
   - Application performance monitoring
   - Database query monitoring

---

## Development Workflow

### Branch Strategy
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/*` - Individual features
- `hotfix/*` - Critical fixes

### PR Process
1. Create feature branch from `develop`
2. Implement feature with tests
3. Run local quality checks
4. Push and create PR to `develop`
5. GitHub Actions runs all checks
6. Code review required
7. All quality gates must pass (or manual bypass)
8. Merge to `develop`
9. Periodic releases to `main`

### Commit Conventions
- `feat:` New feature
- `fix:` Bug fix
- `test:` Test additions/modifications
- `refactor:` Code restructuring
- `docs:` Documentation updates
- `chore:` Maintenance tasks

---

## Getting Started

### Prerequisites
- Docker Desktop
- Git
- Composer
- Node.js 20+

### Initial Setup (Phase 1)
```bash
# Clone repository
git clone <repo-url>
cd truetrack

# Install Laravel
composer create-project laravel/laravel . --prefer-dist

# Install Sail
php artisan sail:install --with=pgsql

# Start Sail
./vendor/bin/sail up -d

# Install Breeze with Inertia/React
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail artisan breeze:install react
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Run migrations
./vendor/bin/sail artisan migrate

# Install testing tools
./vendor/bin/sail composer require --dev laravel/pint phpstan/phpstan nunomaduro/larastan

# Configure GitHub Actions
# (See Phase 1 deliverables)
```

---

## Maintenance Notes

- Database backups: Daily automated backups in production
- Security updates: Monthly dependency updates
- Performance review: Quarterly optimization sprints
- User feedback: Continuous collection and prioritization

---

**Last Updated**: January 7, 2026
**Status**: Planning Phase
**Next Phase**: Phase 1 - Project Foundation
