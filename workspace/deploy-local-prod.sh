#!/bin/bash
# Deploy TrueTrack to local production environment
# Usage: ./deploy-local-prod.sh [init|update|restart|down|logs|status]

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
COMPOSE_FILES="-f compose.production.yaml"
ENV_FILE=".env.production"

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_header() {
    echo ""
    echo "======================================"
    echo "  $1"
    echo "======================================"
}

# Check if Docker is running
check_docker() {
    if ! docker ps >/dev/null 2>&1; then
        print_error "Docker is not running!"
        echo ""
        echo "Please start Docker Desktop and try again."
        exit 1
    fi
}

# Check if .env.production exists
check_env_file() {
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env.production file not found!"
        echo "Creating from .env.production.example..."
        cp .env.production.example .env.production
        print_warning "Please edit .env.production with your production settings"
        exit 1
    fi
}

# Initialize production environment
init_production() {
    print_header "Initializing Production Environment"

    check_docker
    check_env_file

    # Build images
    echo "Building production Docker images..."
    docker compose $COMPOSE_FILES build --no-cache truetrack-prod queue-worker-prod scheduler-prod
    print_success "Images built"

    # Start services
    echo "Starting production services..."
    docker compose $COMPOSE_FILES up -d
    print_success "Services started"

    # Wait for database
    echo "Waiting for database to be ready..."
    sleep 5

    # Generate application key if needed
    if grep -q "GENERATE_WITH_php_artisan_key:generate" .env.production; then
        echo "Generating application key..."
        docker compose $COMPOSE_FILES exec truetrack-prod php artisan key:generate --force
        print_success "Application key generated"
    fi

    # Run migrations
    echo "Running database migrations..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan migrate --force
    print_success "Migrations completed"

    # Seed database (optional - uncomment if needed)
    # echo "Seeding database..."
    # docker compose $COMPOSE_FILES exec truetrack-prod php artisan db:seed --force
    # print_success "Database seeded"

    # Optimize for production
    echo "Optimizing for production..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan config:cache
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan route:cache
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan view:cache
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan event:cache
    print_success "Optimization complete"

    # Create storage link
    echo "Creating storage symlink..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan storage:link
    print_success "Storage linked"

    print_success "Production environment initialized successfully!"
    echo ""
    echo "Access your production environment at: http://localhost:8080"
    echo "PostgreSQL is available at: localhost:5433"
    echo "Redis is available at: localhost:6380"
}

# Update production environment
update_production() {
    print_header "Updating Production Environment"

    check_docker
    check_env_file

    # Pull latest code (if using git)
    # echo "Pulling latest code..."
    # git pull
    # print_success "Code updated"

    # Rebuild images
    echo "Rebuilding Docker images..."
    docker compose $COMPOSE_FILES build truetrack-prod queue-worker-prod scheduler-prod
    if [ $? -ne 0 ]; then
        print_error "Failed to build Docker images. Check the errors above."
        exit 1
    fi
    print_success "Images rebuilt"

    # Restart services
    echo "Restarting services..."
    docker compose $COMPOSE_FILES up -d --force-recreate
    if [ $? -ne 0 ]; then
        print_error "Failed to restart services"
        exit 1
    fi
    print_success "Services restarted"

    # Wait for services
    sleep 5

    # Run migrations
    echo "Running migrations..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan migrate --force
    if [ $? -ne 0 ]; then
        print_error "Failed to run migrations"
        exit 1
    fi
    print_success "Migrations completed"

    # Clear and recache
    echo "Clearing caches..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan cache:clear
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan config:clear
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan route:clear
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan view:clear

    echo "Rebuilding caches..."
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan config:cache
    if [ $? -ne 0 ]; then
        print_error "Failed to rebuild config cache"
        exit 1
    fi
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan route:cache
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan view:cache
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan event:cache
    print_success "Caches rebuilt"

    print_success "Production environment updated successfully!"
}

# Restart services
restart_services() {
    print_header "Restarting Production Services"
    check_docker
    docker compose $COMPOSE_FILES restart
    print_success "Services restarted"
}

# Stop services
stop_services() {
    print_header "Stopping Production Services"
    check_docker
    docker compose $COMPOSE_FILES down
    print_success "Services stopped"
}

# Show logs
show_logs() {
    print_header "Production Logs"
    check_docker
    docker compose $COMPOSE_FILES logs -f --tail=100
}

# Show status
show_status() {
    print_header "Production Environment Status"
    check_docker
    docker compose $COMPOSE_FILES ps
    echo ""
    echo "Health Checks:"
    docker compose $COMPOSE_FILES exec truetrack-prod php artisan health:check || true
}

# Main script
case "${1:-}" in
    init)
        init_production
        ;;
    update)
        update_production
        ;;
    restart)
        restart_services
        ;;
    down|stop)
        stop_services
        ;;
    logs)
        show_logs
        ;;
    status)
        show_status
        ;;
    *)
        echo "TrueTrack Production Deployment Script"
        echo ""
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  init      - Initialize production environment (first time setup)"
        echo "  update    - Update production environment (rebuild & migrate)"
        echo "  restart   - Restart all production services"
        echo "  down      - Stop all production services"
        echo "  logs      - Show production logs (live tail)"
        echo "  status    - Show production services status"
        echo ""
        echo "Examples:"
        echo "  $0 init       # First time setup"
        echo "  $0 update     # Deploy updates"
        echo "  $0 logs       # View logs"
        exit 1
        ;;
esac
