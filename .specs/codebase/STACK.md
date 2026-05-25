# Tech Stack

**Analyzed:** 2025-05-25

## Core

- Framework: Laravel 12.x (`laravel/framework ^12.0`)
- Language: PHP 8.2+ (`php ^8.2`)
- Runtime: Docker (Laravel Sail `sail-8.5/app`)
- Package manager: Composer (PHP), npm (JS)
- Database: PostgreSQL 18 Alpine (`postgres:18-alpine`)
- Cache/Session: Redis 7 Alpine (`redis:7-alpine`)

## Frontend

- UI Framework: React 18.2 (`react ^18.2.0`)
- Server Integration: Inertia.js (`@inertiajs/react ^2.0.0`, `inertiajs/inertia-laravel ^2.0`)
- Styling: TailwindCSS 3.x (`tailwindcss ^3.2.1`) + `@tailwindcss/forms ^0.5.3`
- Charts: Recharts 3.x (`recharts ^3.6.0`)
- HTTP Client: Axios (`axios ^1.11.0`)
- Build: Vite 7.x (`vite ^7.0.7`, `@vitejs/plugin-react ^4.2.0`, `laravel-vite-plugin ^2.0.0`)
- Routing Helper: Ziggy (`tightenco/ziggy ^2.0`)

## Backend

- API Style: REST (`/api/v1`) + Inertia.js (web)
- ORM: Eloquent (via Laravel)
- Authentication: Laravel Breeze (`laravel/breeze ^2.3`) + Sanctum (`laravel/sanctum ^4.0`)
- Queue: Database driver (compose.yaml worker), Redis default in config
- File Import: OFX (`asgrim/ofxparser ^1.1`), XLSX/CSV (`maatwebsite/excel ^3.1`)
- REPL: Tinker (`laravel/tinker ^2.10.1`)
- Log tail: Pail (`laravel/pail ^1.2.2`)

## Testing

- Backend Unit/Feature: PHPUnit 11.x (`phpunit/phpunit ^11.5.3`)
- Backend Mocking: Mockery (`mockery/mockery ^1.6`)
- Frontend Unit: Jest 29.x (`jest ^29.7.0`, `jest-environment-jsdom ^29.7.0`)
- Frontend Testing Library: `@testing-library/react ^14.1.2`, `@testing-library/jest-dom ^6.1.5`, `@testing-library/user-event ^14.5.1`
- Frontend Transform: Babel (`@babel/core ^7.23.5`, `babel-jest ^29.7.0`)

## Code Quality

- Style fixer: Laravel Pint (`laravel/pint ^1.24`) — preset: `laravel`
- Static analysis: Larastan Level 5 (`larastan/larastan ^3.8`)
- Error page: Collision (`nunomaduro/collision ^8.6`)

## External Services

- Database: PostgreSQL 18 (Docker container `pgsql`)
- Cache/Queue: Redis 7 (Docker container `redis`)
- Queue Worker: Dedicated Docker container (`queue-worker`) running `php artisan queue:work database --tries=3 --timeout=300`

## Development Tools

- Container orchestration: Docker Compose (`compose.yaml`)
- Dev runner: `composer dev` (concurrently: server + queue + pail + vite)
- Faker: `fakerphp/faker ^1.23`
- CSS mocking: `identity-obj-proxy ^3.0.0`
