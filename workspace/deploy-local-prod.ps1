# Deploy TrueTrack to local production environment (PowerShell version)
# Usage: .\deploy-local-prod.ps1 [init|update|restart|down|logs|status]

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('init','update','restart','down','stop','logs','status')]
    [string]$Command
)

$ErrorActionPreference = "Stop"

# Configuration
$COMPOSE_FILES = "-f compose.production.yaml"
$ENV_FILE = ".env.production"

# Functions
function Print-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Print-Warning {
    param([string]$Message)
    Write-Host "⚠ $Message" -ForegroundColor Yellow
}

function Print-Error {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Print-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "======================================" -ForegroundColor Cyan
    Write-Host "  $Message" -ForegroundColor Cyan
    Write-Host "======================================" -ForegroundColor Cyan
}

# Check if Docker is running
function Check-Docker {
    try {
        $null = docker ps 2>&1
        if ($LASTEXITCODE -ne 0) {
            Print-Error "Docker is not running!"
            Write-Host ""
            Write-Host "Please start Docker Desktop and try again." -ForegroundColor Yellow
            Write-Host "You can start Docker Desktop from the Windows Start menu." -ForegroundColor Yellow
            exit 1
        }
    }
    catch {
        Print-Error "Docker is not installed or not running!"
        Write-Host ""
        Write-Host "Please ensure Docker Desktop is installed and running." -ForegroundColor Yellow
        exit 1
    }
}

# Check if .env.production exists
function Check-EnvFile {
    if (-not (Test-Path $ENV_FILE)) {
        Print-Error ".env.production file not found!"
        Write-Host "Creating from .env.production.example..."
        Copy-Item .env.production.example .env.production
        Print-Warning "Please edit .env.production with your production settings"
        exit 1
    }
}

# Initialize production environment
function Initialize-Production {
    Print-Header "Initializing Production Environment"

    Check-Docker
    Check-EnvFile

    # Build images
    Write-Host "Building production Docker images..."
    docker compose $COMPOSE_FILES.Split() build --no-cache truetrack-prod queue-worker-prod scheduler-prod
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to build Docker images"
        exit 1
    }
    Print-Success "Images built"

    # Start services
    Write-Host "Starting production services..."
    docker compose $COMPOSE_FILES.Split() up -d
    Print-Success "Services started"

    # Wait for database
    Write-Host "Waiting for database to be ready..."
    Start-Sleep -Seconds 5

    # Generate application key if needed
    $envContent = Get-Content .env.production -Raw
    if ($envContent -match "GENERATE_WITH_php_artisan_key:generate") {
        Write-Host "Generating application key..."
        docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan key:generate --force
        Print-Success "Application key generated"
    }

    # Run migrations
    Write-Host "Running database migrations..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan migrate --force
    Print-Success "Migrations completed"

    # Optimize for production
    Write-Host "Optimizing for production..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan config:cache
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan route:cache
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan view:cache
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan event:cache
    Print-Success "Optimization complete"

    # Create storage link
    Write-Host "Creating storage symlink..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan storage:link
    Print-Success "Storage linked"

    Print-Success "Production environment initialized successfully!"
    Write-Host ""
    Write-Host "Access your production environment at: http://localhost:8080" -ForegroundColor Cyan
    Write-Host "PostgreSQL is available at: localhost:5433" -ForegroundColor Cyan
    Write-Host "Redis is available at: localhost:6380" -ForegroundColor Cyan
}

# Update production environment
function Update-Production {
    Print-Header "Updating Production Environment"

    Check-Docker
    Check-EnvFile

    # Rebuild images
    Write-Host "Rebuilding Docker images..."
    docker compose $COMPOSE_FILES.Split() build --no-cache truetrack-prod queue-worker-prod scheduler-prod
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to build Docker images. Check the errors above."
        exit 1
    }
    Print-Success "Images rebuilt"

    # Restart services
    Write-Host "Restarting services..."
    docker compose $COMPOSE_FILES.Split() up -d --force-recreate
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to restart services"
        exit 1
    }
    Print-Success "Services restarted"

    # Wait for services
    Start-Sleep -Seconds 5

    # Run migrations
    Write-Host "Running migrations..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan migrate --force
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to run migrations"
        exit 1
    }
    Print-Success "Migrations completed"

    # Clear and recache
    Write-Host "Clearing caches..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan cache:clear
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan config:clear
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan route:clear
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan view:clear

    Write-Host "Rebuilding caches..."
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan config:cache
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to rebuild config cache"
        exit 1
    }
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan route:cache
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan view:cache
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan event:cache
    Print-Success "Caches rebuilt"

    Print-Success "Production environment updated successfully!"
}

# Restart services
function Restart-Services {
    Print-Header "Restarting Production Services"
    Check-Docker
    docker compose $COMPOSE_FILES.Split() restart
    if ($LASTEXITCODE -ne 0) {
        Print-Error "Failed to restart services"
        exit 1
    }
    Print-Success "Services restarted"
}

# Stop services
function Stop-Services {
    Print-Header "Stopping Production Services"
    Check-Docker
    docker compose $COMPOSE_FILES.Split() down
    Print-Success "Services stopped"
}

# Show logs
function Show-Logs {
    Print-Header "Production Logs"
    Check-Docker
    docker compose $COMPOSE_FILES.Split() logs -f --tail=100
}

# Show status
function Show-Status {
    Print-Header "Production Environment Status"
    Check-Docker
    docker compose $COMPOSE_FILES.Split() ps
    Write-Host ""
    Write-Host "Health Checks:"
    docker compose $COMPOSE_FILES.Split() exec truetrack-prod php artisan health:check
}

# Main script
if (-not $Command) {
    Write-Host "TrueTrack Production Deployment Script" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Usage: .\deploy-local-prod.ps1 [command]"
    Write-Host ""
    Write-Host "Commands:"
    Write-Host "  init      - Initialize production environment (first time setup)"
    Write-Host "  update    - Update production environment (rebuild & migrate)"
    Write-Host "  restart   - Restart all production services"
    Write-Host "  down      - Stop all production services"
    Write-Host "  logs      - Show production logs (live tail)"
    Write-Host "  status    - Show production services status"
    Write-Host ""
    Write-Host "Examples:"
    Write-Host "  .\deploy-local-prod.ps1 init       # First time setup"
    Write-Host "  .\deploy-local-prod.ps1 update     # Deploy updates"
    Write-Host "  .\deploy-local-prod.ps1 logs       # View logs"
    exit 0
}

switch ($Command) {
    'init'    { Initialize-Production }
    'update'  { Update-Production }
    'restart' { Restart-Services }
    'down'    { Stop-Services }
    'stop'    { Stop-Services }
    'logs'    { Show-Logs }
    'status'  { Show-Status }
}
