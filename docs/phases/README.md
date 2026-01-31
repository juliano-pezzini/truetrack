# Development Phases Documentation

Complete implementation guides for TrueTrack development. Each phase builds upon previous phases to create a comprehensive personal finance management system.

---

## Phase Overview

| Phase | Goal | Status | Documentation |
|-------|------|--------|---|
| 1 | Project Foundation | Planned | [PHASE_01_PROJECT_FOUNDATION.md](PHASE_01_PROJECT_FOUNDATION.md) |
| 2 | Accounts Module | Planned | [PHASE_02_ACCOUNTS_MODULE.md](PHASE_02_ACCOUNTS_MODULE.md) |
| 3 | Categories and Tags | Planned | [PHASE_03_CATEGORIES_AND_TAGS.md](PHASE_03_CATEGORIES_AND_TAGS.md) |
| 4 | Transactions | Planned | [PHASE_04_TRANSACTIONS.md](PHASE_04_TRANSACTIONS.md) |
| 5 | Analytics Dashboard | Planned | [PHASE_05_ANALYTICS_DASHBOARD.md](PHASE_05_ANALYTICS_DASHBOARD.md) |
| 6 | Statement Import | Planned | [PHASE_06_STATEMENT_IMPORT.md](PHASE_06_STATEMENT_IMPORT.md) |
| 7 | Auto-Categorization with Learning | Planned | [PHASE_07_AUTO_CATEGORIZATION.md](PHASE_07_AUTO_CATEGORIZATION.md) |

---

## Phase 6 Sub-Phases

Phase 6 is split into detailed sub-phase documentation for easier management:

| Sub-Phase | Goal | Status | Documentation |
|-----------|------|--------|---|
| 6.1-6.5 | OFX/QFX Import with Fuzzy Matching | Planned | [PHASE_06_OFX_IMPORT.md](PHASE_06_OFX_IMPORT.md) |
| 6.6-6.13 | XLSX/CSV Import with Column Mapping | Planned | [PHASE_06_XLSX_CSV_IMPORT.md](PHASE_06_XLSX_CSV_IMPORT.md) |

---

## Quick Navigation

### Getting Started
1. Read [PHASE_01_PROJECT_FOUNDATION.md](PHASE_01_PROJECT_FOUNDATION.md) for initial setup
2. Follow phases in order (1 → 2 → 3 → 4 → 5 → 6 → 7)
3. Refer to [copilot-instructions.md](../../.github/copilot-instructions.md) for coding standards

### Phase Details
- **Deliverables** - What gets built
- **Models & Database** - Schema and relationships
- **API Endpoints** - Request/response examples
- **React Components** - UI implementation
- **Testing** - PHPUnit and Jest test strategies
- **User Capabilities** - Features available after phase completion

### For Each Phase You'll Find
```
Phase Overview
├── Deliverables (models, controllers, components, etc.)
├── Database Schema (tables, indexes, migrations)
├── API Endpoints (with examples)
├── React Components (with features)
├── Form Requests & Validation
├── Factories & Seeders
├── Testing Strategy (unit, feature, integration tests)
├── Coverage Targets
├── User Capabilities
└── Quality Gates
```

---

## Development Workflow

1. **Review Phase Documentation**
   - Understand deliverables and requirements
   - Check database schema
   - Review API endpoints and React components

2. **Implement in Order**
   - Database: Migrations and Models
   - Backend: Services, Controllers, Form Requests
   - Frontend: React Components, Pages
   - Testing: PHPUnit + Jest tests

3. **Quality Assurance**
   - Run `docker compose exec truetrack php artisan test`
   - Run `npm run test` (Jest tests)
   - Run `docker compose exec truetrack ./vendor/bin/pint`
   - Run `docker compose exec truetrack ./vendor/bin/phpstan analyse`
   - Verify coverage targets met

4. **PR Process**
   - All quality gates must pass
   - Tests must pass
   - Code coverage targets achieved
   - Code review required before merge

---

## Important Notes

### Phase 6 Unified Import Page
- **Single page at `/imports`** handles all file types (OFX, XLSX, CSV)
- File type detection based on extension
- Unified import history with type filtering
- Shared React components for common functionality

### Personal Finance Logic
- **NOT traditional accounting** - uses personal finance balance logic
- **Credit transactions** increase balance
- **Debit transactions** decrease balance
- **Credit card accounts** typically show negative balance (amount owed)

### Quality Standards
All code must pass:
- ✅ PHPUnit tests (all passing)
- ✅ Jest tests (all passing)
- ✅ Laravel Pint (no violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage thresholds (70%+ overall, 80%+ for services)

---

## Related Documentation

- **Coding Standards**: See [.github/copilot-instructions.md](../../.github/copilot-instructions.md)
- **Main Development Plan**: See [DEVELOPMENT_PLAN.md](../../DEVELOPMENT_PLAN.md)
- **API Reference**: Each phase documents its endpoints
- **Database Schema**: Each phase shows table structures

---

## Phase Dependencies

```
Phase 1: Project Foundation
    └─ Phase 2: Accounts Module
       └─ Phase 3: Categories and Tags
          └─ Phase 4: Transactions
             └─ Phase 5: Analytics Dashboard
                └─ Phase 6: Statement Import
                   ├─ Phase 6.1-6.5: OFX Import
                   └─ Phase 6.6-6.13: XLSX/CSV Import
                      └─ Phase 7: Auto-Categorization
```

Each phase builds on previous phases. Complete phases in order for dependencies to work correctly.

---

## Tips for Implementation

### Models First
- Create migrations with proper indexes
- Define relationships and casts
- Write factories for testing

### Tests Alongside Code
- Write tests as you implement features
- Use TDD (Test-Driven Development) approach when possible
- Aim for coverage targets from start

### Validate Early
- Use Form Requests for validation
- Test API responses with proper status codes
- Verify authorization and permissions

### Components Last
- Implement backend first
- React components use API endpoints
- Jest tests verify component behavior

---

## Questions or Issues?

Refer to:
1. Specific phase documentation for requirements
2. [copilot-instructions.md](../../.github/copilot-instructions.md) for coding standards
3. Code patterns in Common Patterns section
4. Test examples in Testing Strategy section

---

**Last Updated**: January 30, 2026
