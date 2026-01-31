# TrueTrack Development Plan

## Project Overview

Cloud-based personal finance management system with double-entry accounting principles, built with Laravel, PostgreSQL, and Inertia.js/React. API-first architecture to support both web and future mobile applications.

---

## Technology Stack

- **Backend**: PHP 8.2+, Laravel 11.x
- **Database**: PostgreSQL 15+ with monthly balance snapshots  
- **Frontend**: Inertia.js with React 18+
- **Authentication**: Laravel Breeze (web) + Sanctum (API)
- **API**: RESTful (`/api/v1`) with token-based auth
- **Testing**: PHPUnit (backend 80%+ services), Jest (frontend)
- **Code Quality**: Laravel Pint, PHPStan/Larastan Level 5+
- **CI/CD**: GitHub Actions with strict gates
- **Deployment**: Docker (dev), AWS Aurora + Fargate (production)

See [.github/copilot-instructions.md](.github/copilot-instructions.md) for detailed coding standards and patterns.

---

## Development Phases

| Phase | Goal | Effort | Status | Documentation |
|-------|------|--------|--------|---|
| 1 | Project Foundation (Auth, CI/CD) | 2-3 wks | Planned | [PHASE_01_PROJECT_FOUNDATION.md](docs/phases/PHASE_01_PROJECT_FOUNDATION.md) |
| 2 | Accounts Module | 2-3 wks | Planned | [PHASE_02_ACCOUNTS_MODULE.md](docs/phases/PHASE_02_ACCOUNTS_MODULE.md) |
| 3 | Categories & Tags | 1-2 wks | Planned | [PHASE_03_CATEGORIES_AND_TAGS.md](docs/phases/PHASE_03_CATEGORIES_AND_TAGS.md) |
| 4 | Transactions (Core Logic) | 2-3 wks | Planned | [PHASE_04_TRANSACTIONS.md](docs/phases/PHASE_04_TRANSACTIONS.md) |
| 5 | Analytics Dashboard | 2-3 wks | Planned | [PHASE_05_ANALYTICS_DASHBOARD.md](docs/phases/PHASE_05_ANALYTICS_DASHBOARD.md) |
| 6 | Statement Import (OFX + XLSX) | 4-6 wks | Planned | [PHASE_06_STATEMENT_IMPORT.md](docs/phases/PHASE_06_STATEMENT_IMPORT.md) |
| 7 | Auto-Categorization with Learning | 2-3 wks | Planned | [PHASE_07_AUTO_CATEGORIZATION.md](docs/phases/PHASE_07_AUTO_CATEGORIZATION.md) |

**Total Duration**: 15-23 weeks (4-6 months)

### Phase 6 Sub-Documentation

Phase 6 has two detailed implementation guides:
- **[Phase 6.1-6.5: OFX/QFX Import](docs/phases/PHASE_06_OFX_IMPORT.md)** - Automatic statement parsing with fuzzy matching
- **[Phase 6.6-6.13: XLSX/CSV Import](docs/phases/PHASE_06_XLSX_CSV_IMPORT.md)** - Flexible column mapping with row-level duplicate detection

---

## Quick Start

### Documentation Navigation
- **→ [All Phase Documentation](docs/phases/README.md)** - Hub for all phases with navigation  
- **→ [Coding Standards](.github/copilot-instructions.md)** - Patterns, conventions, best practices
- **→ [Phase 1: Getting Started](docs/phases/PHASE_01_PROJECT_FOUNDATION.md)** - How to begin implementation

### For Implementation Teams
1. Start with [Phase 1](docs/phases/PHASE_01_PROJECT_FOUNDATION.md) for environment setup
2. Follow phases sequentially (dependencies required)
3. Each phase is 1-3 weeks, Phase 6 is 4-6 weeks
4. All code must pass quality gates before merging

### For Project Managers
- 6 sequential phases over 3-5 months
- Each phase has defined deliverables and user capabilities
- Quality gates enforced on every PR (with manual bypass option)
- Testing required: 80%+ services, 70%+ overall, all endpoints

### For Architects
- Personal finance balance logic (not traditional accounting)
- Monthly balance snapshots for performance
- API-first design with versioned endpoints (`/api/v1`)
- Role-based explicit permissions (no inheritance)
- Reusable background job infrastructure

---

## Key Architectural Patterns

### Personal Finance Balance Calculation
**CRITICAL**: TrueTrack uses personal finance logic, NOT traditional double-entry accounting.

```
Balance = Initial + Credits - Debits

Where:
- Credit = Income, deposits, refunds (INCREASES balance)
- Debit = Expenses, withdrawals, payments (DECREASES balance)
- Credit card balances are NEGATIVE (amount owed)
```

### Monthly Balance Snapshots
- One `account_balances` record per account per month
- Stores `closing_balance` at end of month
- Enables fast balance retrieval without summing all transactions
- Formula: `balance = snapshot + sum(transactions since snapshot)`

### API Design
- **Versioning**: `/api/v1` prefix on all routes
- **Response Format**: Data + metadata + pagination links
- **Filtering**: Query parameters (`?filter[type]=bank`)
- **Sorting**: Dash prefix for descending (`?sort=-created_at`)
- **Auth**: Token-based (Sanctum) for API, sessions for web

### Background Job Infrastructure
- Base `BaseProcessingJob` class with progress tracking
- Reusable for OFX import, XLSX import, bulk operations
- Redis queue with configurable concurrency limits
- Automatic cleanup with scheduled commands

### Role-Based Authorization
- Explicit permissions (no inheritance)
- Roles: admin, user
- Permissions: manage-users, manage-settings, manage-reconciliations
- Stored in `roles`, `permissions`, and pivot tables

---

## Database Overview

### Core Tables by Phase
```
Phase 1: users, personal_access_tokens
Phase 2: accounts, account_balances
Phase 3: categories, tags, tag_transaction
Phase 4: transactions (uses previous)
Phase 5: (no new tables, uses previous)
Phase 6: roles, permissions, permission_role, role_user
        settings, setting_changes
        reconciliations, reconciliation_transaction
        ofx_imports, xlsx_imports, xlsx_column_mappings, xlsx_transaction_hashes
Phase 7: auto_category_rules, auto_category_corrections,
        learned_category_patterns, auto_category_suggestions_log
```

### Key Tables
- **`account_balances`** - Monthly snapshots for balance calculation performance
- **`settings`** - Configurable system values with validation and audit trail
- **`ofx_imports`** / **`xlsx_imports`** - Import tracking with status and progress
- **`roles`** / **`permissions`** - Explicit role-based access control

See individual phase docs for complete schema details.

---

## Quality Standards

### Code Quality Gates
All PRs must pass (or receive manual bypass):
- ✅ **PHPUnit**: All tests pass, 80%+ coverage for services, 70%+ overall
- ✅ **Jest**: All tests pass for components with business logic
- ✅ **Laravel Pint**: No style violations
- ✅ **PHPStan/Larastan**: Level 5+ with zero errors
- ✅ **Code Review**: Human approval required

### Testing Strategy
- **Unit**: Services, models, business logic (80%+ coverage)
- **Feature**: API endpoints, Inertia responses (80%+ coverage)
- **Integration**: Multi-model workflows, reconciliation (70%+)
- **E2E**: Critical user journeys (Jest for components)

---

## Getting Started

### Environment Setup
```bash
cd workspace
docker compose up -d
docker compose exec truetrack composer install
docker compose exec truetrack npm install
docker compose exec truetrack php artisan migrate
docker compose exec truetrack npm run build
```

### Running Tests
```bash
# Backend tests
docker compose exec truetrack php artisan test

# Frontend tests
npm run test

# Code quality
docker compose exec truetrack ./vendor/bin/pint
docker compose exec truetrack ./vendor/bin/phpstan analyse
```

### Git Workflow
1. Create feature branch: `git checkout -b feature/my-feature`
2. Implement with tests
3. Push and create PR to `develop`
4. Wait for GitHub Actions (all checks must pass)
5. Merge to `develop`
6. Periodic releases to `main`

### Commit Convention
- `feat:` New feature
- `fix:` Bug fix
- `test:` Test additions
- `refactor:` Code restructuring
- `docs:` Documentation
- `chore:` Maintenance

---

## Phase Dependencies

```
Phase 1 (Foundation)
    ↓
Phase 2 (Accounts) ← Phase 3 (Categories)
    ↓                   ↓
Phase 4 (Transactions)
    ↓
Phase 5 (Analytics)
    ↓
Phase 6 (Imports)
    ↓
Phase 7 (Auto-Categorization)
```

**Sequential Implementation Required**: Each phase builds on previous infrastructure.

---

## Production Deployment

### AWS Infrastructure
- **Aurora PostgreSQL Serverless v2** - Database with auto-scaling
- **ECS Fargate** - Containerized Laravel application
- **S3** - File storage (OFX, XLSX imports)
- **CloudFront** - CDN for static assets
- **RDS** - Database backups and replication
- **CloudWatch** - Monitoring and logging

### CI/CD Pipeline
- GitHub Actions automatically runs on PR
- All quality gates must pass before merge
- Deployment on merge to `main` (with approval)

---

## Documentation Structure

All detailed implementation documentation is in `docs/phases/`:

```
docs/phases/
├── README.md (Navigation hub)
├── PHASE_01_PROJECT_FOUNDATION.md
├── PHASE_02_ACCOUNTS_MODULE.md
├── PHASE_03_CATEGORIES_AND_TAGS.md
├── PHASE_04_TRANSACTIONS.md
├── PHASE_05_ANALYTICS_DASHBOARD.md
├── PHASE_06_STATEMENT_IMPORT.md (Core framework)
├── PHASE_06_OFX_IMPORT.md (Detailed OFX implementation)
├── PHASE_06_XLSX_CSV_IMPORT.md (Detailed XLSX implementation)
└── PHASE_07_AUTO_CATEGORIZATION.md (Auto-category rules + learning)
```

Each phase document includes:
- ✅ Detailed deliverables with line counts
- ✅ Database schema and migrations
- ✅ API endpoints with JSON examples
- ✅ React component specifications
- ✅ Form request validation rules
- ✅ Service layer patterns
- ✅ Job/background processing details
- ✅ Complete test strategy (PHPUnit + Jest)
- ✅ Coverage targets (80%+ services, 70%+ overall)
- ✅ User capabilities after completion

---

## Future Considerations

### Post-Phase 6
1. **Multi-Currency Support** - Currency fields + exchange rates
2. **Mobile App** - React Native consuming existing `/api/v1`
3. **Advanced Features** - Recurring transactions, budgets, goals
4. **Bank APIs** - Direct bank integrations beyond OFX
5. **Reporting** - Export to PDF, Excel, custom reports
6. **Multi-Tenancy** - Family accounts with shared access

---

## Support & Questions

### Finding Answers
1. Check the specific phase documentation
2. Review coding standards in `copilot-instructions.md`
3. Look at test examples in phase docs
4. Review patterns section in this file

### Asking for Help
- For architecture questions: Review "Key Architectural Patterns"
- For coding questions: See `copilot-instructions.md`
- For implementation details: Check individual phase docs
- For testing strategy: See "Quality Standards" and individual phases

---

**Last Updated**: January 2026  
**Status**: Planning Phase - Phase Documentation Complete  
**Version**: 1.0
