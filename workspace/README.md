# TrueTrack - Personal Finance Management System

A modern personal finance management system built with Laravel 11, PostgreSQL, Inertia.js, and React 18+. Supports bank statement imports (OFX/XLSX), intelligent transaction matching, and comprehensive financial analytics.

## 🚀 Features

- ✅ Multi-account management (bank, credit card, wallet, transitional)
- ✅ Transaction tracking with double-entry accounting principles
- ✅ Bank statement import (OFX/QFX and XLSX/CSV formats)
- ✅ Intelligent fuzzy matching with Levenshtein distance
- ✅ Financial analytics dashboard with charts and projections
- ✅ Hierarchical categories and flexible tagging
- ✅ Role-based permissions system
- ✅ Background job processing for imports
- ✅ Monthly balance snapshots for performance

## 📋 Requirements

- Docker Desktop (for local development)
- PHP 8.2+ (for production)
- PostgreSQL 15+
- Redis 7+
- Node.js 20+ (for asset compilation)

---

## 🏠 Development Environment

### First-Time Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd truetrack2/workspace
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Start development environment**
   ```bash
   docker compose up -d
   ```

4. **Install dependencies**
   ```bash
   docker compose exec truetrack composer install
   docker compose exec truetrack npm install
   ```

5. **Generate application key**
   ```bash
   docker compose exec truetrack php artisan key:generate
   ```

6. **Run migrations and seeders**
   ```bash
   docker compose exec truetrack php artisan migrate --seed
   ```

7. **Build frontend assets**
   ```bash
   docker compose exec truetrack npm run dev
   ```

8. **Access the application**
   - Web: http://localhost
   - PostgreSQL: localhost:5432
   - Redis: localhost:6379

### Daily Development Commands

```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f

# Run tests
docker compose exec truetrack php artisan test
docker compose exec truetrack npm run test

# Code quality
docker compose exec truetrack ./vendor/bin/pint
docker compose exec truetrack ./vendor/bin/phpstan analyse

# Database operations
docker compose exec truetrack php artisan migrate
docker compose exec truetrack php artisan migrate:fresh --seed
```

---

## 🏭 Production Environment (Local Testing)

Test production configurations locally before deploying to cloud infrastructure.

### Quick Start

**Windows (PowerShell):**
```powershell
# First-time setup
.\deploy-local-prod.ps1 init

# View status
.\deploy-local-prod.ps1 status

# View logs
.\deploy-local-prod.ps1 logs
```

**Linux/macOS (Bash):**
```bash
# Make script executable
chmod +x deploy-local-prod.sh

# First-time setup
./deploy-local-prod.sh init

# View status
./deploy-local-prod.sh status
```

### Production Environment Details

- **Web Interface:** http://localhost:8080
- **PostgreSQL:** localhost:5433
- **Redis:** localhost:6380
- **Separate data volumes** (won't affect development data)
- **Production optimizations** (OPcache, caching, etc.)
- **Background workers** (queue, scheduler)

### Production Commands

```bash
# Initialize (first time only)
./deploy-local-prod.ps1 init

# Update after code changes
./deploy-local-prod.ps1 update

# Restart services
./deploy-local-prod.ps1 restart

# Stop production environment
./deploy-local-prod.ps1 down

# View logs
./deploy-local-prod.ps1 logs

# Check status and health
./deploy-local-prod.ps1 status
```

### Configuration

Edit `.env.production` for production settings:
- Database credentials
- Redis configuration
- Mail settings
- Application settings
- Performance tuning

---

## 🧪 Testing

### Backend Tests (PHPUnit)

```bash
# Run all tests
docker compose exec truetrack php artisan test

# Run specific test suite
docker compose exec truetrack php artisan test --testsuite=Feature
docker compose exec truetrack php artisan test --testsuite=Unit

# Run with coverage
docker compose exec truetrack php artisan test --coverage
```

### Frontend Tests (Jest)

```bash
# Run all tests
docker compose exec truetrack npm run test

# Run in watch mode
docker compose exec truetrack npm run test:watch

# Run with coverage
docker compose exec truetrack npm run test:coverage
```

### Code Quality

```bash
# Laravel Pint (code style)
docker compose exec truetrack ./vendor/bin/pint

# PHPStan (static analysis)
docker compose exec truetrack ./vendor/bin/phpstan analyse
```

---

## 📦 Project Structure

```
workspace/
├── app/                    # Laravel application
│   ├── Http/
│   │   ├── Controllers/   # API and web controllers
│   │   ├── Requests/      # Form validation
│   │   └── Resources/     # API resources
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic
│   ├── Jobs/              # Background jobs
│   └── Policies/          # Authorization
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories
├── resources/
│   ├── js/                # React components
│   │   ├── Components/   # Reusable components
│   │   ├── Pages/        # Inertia pages
│   │   └── Layouts/      # Page layouts
│   └── css/              # Stylesheets
├── tests/
│   ├── Feature/          # Feature tests
│   ├── Unit/             # Unit tests
│   └── fixtures/         # Test fixtures (OFX, XLSX)
└── routes/
    ├── api.php           # API routes
    ├── web.php           # Web routes
    └── auth.php          # Authentication routes
```

---

## 🔐 Security

- Role-based access control (RBAC)
- Sanctum API authentication
- CSRF protection
- SQL injection prevention via Eloquent
- XSS protection
- Input validation on all endpoints
- Secure password hashing (bcrypt)
- Environment-based configuration

---

## 📚 Documentation

- [Development Plan](../DEVELOPMENT_PLAN.md)
- [Phase Documentation](../docs/phases/README.md)
- [Copilot Instructions](../.github/copilot-instructions.md)

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Commit Conventions

- `feat:` New feature
- `fix:` Bug fix
- `test:` Test additions/modifications
- `refactor:` Code restructuring
- `docs:` Documentation updates
- `chore:` Maintenance tasks

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.

---

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- Frontend powered by [React](https://react.dev) and [Inertia.js](https://inertiajs.com)
- Styled with [Tailwind CSS](https://tailwindcss.com)
- OFX parsing by [asgrim/ofxparser](https://github.com/asgrim/ofxparser)
- Excel import/export by [Maatwebsite/Laravel-Excel](https://laravel-excel.com)
