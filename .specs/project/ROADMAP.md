# Roadmap

**Current Milestone:** Post-v1 — All core phases complete
**Status:** Complete

---

## Milestone 1: Foundation & Core Data

**Goal:** Bootstrapped environment with auth, account management, and financial taxonomy
**Target:** Phases 1-3

### Features

**Authentication & CI/CD** - COMPLETE

- Laravel Breeze registration/login with Sanctum API tokens
- Docker Compose dev environment
- GitHub Actions quality gates (Pint, PHPStan, PHPUnit, Jest)

**Accounts Module** - COMPLETE

- CRUD for bank, credit card, wallet, transitional accounts
- Initial balance tracking, soft deletes
- RESTful API with filtering, sorting, pagination

**Categories & Tags** - COMPLETE

- Hierarchical categories (revenue/expense with parent-child)
- Freeform tags with many-to-many transaction tagging
- Category and tag API endpoints

---

## Milestone 2: Transactions & Insights

**Goal:** Core transaction engine with balance snapshots and analytics dashboard
**Target:** Phases 4-5

### Features

**Transaction Engine** - COMPLETE

- Transaction recording with atomic balance updates
- Monthly balance snapshots (`account_balances` table)
- Personal finance balance logic (credit increases, debit decreases)
- Transaction/settlement date tracking

**Analytics Dashboard** - COMPLETE

- Revenue vs expenses analysis
- Cash flow projection
- Credit card bill tracking
- Profit/loss reporting

---

## Milestone 3: Import & Intelligence

**Goal:** Automated statement import and intelligent categorization
**Target:** Phases 6-7

### Features

**OFX/QFX Statement Import** - COMPLETE

- Automatic OFX file parsing
- Fuzzy duplicate detection
- Reconciliation workflow

**XLSX/CSV Statement Import** - COMPLETE

- Flexible column mapping with intelligent detection
- Row-level duplicate detection (SHA-256 hashing)
- Background processing via queue jobs
- Saved mapping configurations per account
- Template generation and error reporting

**Auto-Categorization** - COMPLETE

- Explicit priority-ordered rules (pattern → category)
- Keyword-based learned patterns with confidence scoring
- Correction learning with audit trail
- Analytics logging for all suggestions
- Conflict detection for overlapping patterns

---

## Future Considerations

- Mobile application (API already supports token-based auth)
- Multi-currency support with exchange rates
- Budget planning and tracking module
- Recurring transaction templates
- Shared/household account collaboration
- Investment portfolio tracking and performance analytics
- Notification system (balance alerts, bill reminders)
- Data export (PDF reports, CSV downloads)
