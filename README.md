# TrueTrack

[![Tests](https://github.com/YOUR_USERNAME/truetrack/workflows/Tests/badge.svg)](https://github.com/YOUR_USERNAME/truetrack/actions)

True Track My Money is a cloud-based personal finance management solution that uses double-entry accounting principles to provide accurate insights about your finances.

## Features

### Core Capabilities
- 💰 **Account Management** - Track bank accounts, credit cards, wallets, and transitional accounts
- 📊 **Financial Insights** - Revenue vs expenses analysis with profit/loss reporting
- 🔮 **Cash Flow Projection** - Monthly income and expense forecasting
- 💳 **Credit Card Management** - Track bills and ensure sufficient funds for payment
- 📈 **Investment Tracking** - Monitor investment returns and performance
- 🏷️ **Flexible Organization** - Categorize transactions and use tags for custom grouping
- ⚖️ **Bank Reconciliation** - Verify transactions against statements
- 🔒 **API-First Architecture** - RESTful API with token authentication for web and future mobile apps

### Technical Highlights
- Double-entry accounting principles
- Transaction and settlement date tracking
- Hierarchical category structure (revenue/expense)
- Automatic balance calculations
- Monthly credit card closure workflows

## Tech Stack

- **Backend**: PHP 8.4, Laravel 12.x
- **Database**: PostgreSQL 18 (Alpine)
- **Frontend**: React 18, Inertia.js, TailwindCSS
- **Authentication**: Laravel Breeze (web), Sanctum (API)
- **Development**: Docker (Laravel Sail)
- **Testing**: PHPUnit, Jest
- **Quality**: Laravel Pint, PHPStan/Larastan
- **CI/CD**: GitHub Actions

## Getting Started

### Prerequisites
- Docker Desktop
- Git

### Installation

**Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/truetrack.git
cd truetrack/workspace
```

**Install Composer (create .vendor folder, needed to create the containers)**
```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" -w /app composer:latest composer install --ignore-platform-reqs
docker compose up -d # Create the containers, first time will delay a lot
docker compose exec truetrack php artisan migrate # Create the database
docker compose exec truetrack php artisan key:generate
docker compose exec truetrack npm install
docker compose exec truetrack npm run build
```

**Access the application**
- Web: http://localhost
- API: http://localhost/api/v1

## Development

### Running in Development Mode

```bash
# Start Sail containers
docker compose up -d

# Watch frontend changes
docker compose exec truetrack npm run dev
```

### Code Quality Tools

```bash
# Fix code style
docker compose exec truetrack ./vendor/bin/pint

# Run static analysis
docker compose exec truetrack ./vendor/bin/phpstan analyse

# Run backend tests
docker compose exec truetrack php artisan test

# Run frontend tests
docker compose exec truetrack npm test
```

### Running Tests with Coverage

```bash
# Backend tests with coverage (70% minimum)
docker compose exec truetrack php artisan test --coverage --min=70

# Frontend tests with coverage
docker compose exec truetrack npm test -- --coverage
```

## API Documentation

### Authentication Endpoints

**Register**
```bash
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Login**
```bash
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Get User** (Protected)
```bash
GET /api/v1/user
Authorization: Bearer YOUR_TOKEN_HERE
```

**Logout** (Protected)
```bash
POST /api/v1/logout
Authorization: Bearer YOUR_TOKEN_HERE
```

## Project Structure

```
truetrack/
├── .github/
│   └── workflows/
│       └── tests.yml          # CI/CD pipeline
├── workspace/                  # Laravel application root
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/V1/    # API v1 controllers
│   │   └── Models/
│   ├── database/
│   ├── resources/
│   │   └── js/                # React components
│   ├── routes/
│   │   ├── api.php            # API routes (/api/v1)
│   │   └── web.php            # Web routes
│   ├── tests/
│   ├── compose.yaml           # Docker Compose config
│   ├── phpunit.xml            # PHPUnit config
│   ├── phpstan.neon           # PHPStan config
│   ├── pint.json              # Pint config
│   └── jest.config.js         # Jest config
└── DEVELOPMENT_PLAN.md        # Detailed development phases
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'feat: Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

**All PRs must pass:**
- ✅ Code style (Pint)
- ✅ Static analysis (PHPStan)
- ✅ Backend tests (PHPUnit - 70% coverage)
- ✅ Frontend tests (Jest)

## Functional Requirements

Below are the techniques/structures used to achieve the objectives:

* All expenses and revenues are recorded as entries, assigned to "Revenue/Expense Categories", and also to "Transaction Accounts" (which can be bank accounts, credit cards, personal wallets, or even transit accounts). Transactions can also be assigned to "Tags," which are a way to group specific transactions for future management/monitoring.

Finally, transactions are also identified by the dates they occurred and the dates they were actually settled (paid/received).

* Credit card accounts need to be zeroed out monthly against another transaction account (meaning payment of the credit card bill).

* Bank statements (bank account type accounts) and credit card bills should be reconciled periodically.