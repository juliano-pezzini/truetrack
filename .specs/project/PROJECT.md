# TrueTrack

**Vision:** A cloud-based personal finance management system that uses personal finance balance logic (not traditional double-entry accounting) to provide accurate insights, forecasting, and automated categorization of financial transactions.
**For:** Individual users managing personal bank accounts, credit cards, wallets, and investments.
**Solves:** Lack of clear, accurate financial visibility — tracking income vs expenses, projecting cash flow, reconciling bank statements, and auto-categorizing transactions.

## Goals

- Accurate balance tracking using monthly snapshots and personal finance logic (credit increases, debit decreases)
- API-first architecture supporting both web (Inertia.js/React) and future mobile clients
- Automated statement import (OFX/XLSX/CSV) with fuzzy duplicate detection
- Intelligent auto-categorization with rule-based and learned pattern matching
- 80%+ test coverage on services, 70%+ overall, enforced via CI quality gates

## Tech Stack

**Core:**

- Framework: Laravel 12.x (PHP 8.4)
- Database: PostgreSQL 18 (Alpine)
- Frontend: React 18 + Inertia.js + TailwindCSS

**Key dependencies:**

- Laravel Breeze + Sanctum (auth — sessions for web, tokens for API)
- PHPUnit + Jest (testing)
- Laravel Pint + PHPStan/Larastan Level 5+ (code quality)
- Docker Compose (development environment, container name: `truetrack`)
- Maatwebsite/Laravel-Excel (XLSX/CSV import)

## Scope

**v1 includes:**

- Account management (bank, credit card, wallet, transitional)
- Hierarchical categories (revenue/expense) and tags
- Transaction recording with atomic balance updates and monthly snapshots
- Analytics dashboard (revenue vs expenses, cash flow projection, credit card tracking)
- Statement import — OFX/QFX (auto-parse) and XLSX/CSV (column mapping)
- Auto-categorization with explicit rules, learned patterns, and correction learning
- Bank reconciliation

**Explicitly out of scope:**

- Mobile applications (API ready, but no native app in v1)
- Multi-currency support
- Budgeting / budget planning module
- Shared/household accounts (multi-user collaboration)
- Investment portfolio tracking beyond basic account balances

## Constraints

- All Laravel/PHP commands must run inside Docker (`docker compose exec truetrack ...`)
- Personal finance balance logic — credits always increase balance, debits always decrease (not traditional accounting)
- API versioned at `/api/v1`; all endpoints paginated with consistent JSON structure
- Sequential phase delivery — each phase depends on the previous
