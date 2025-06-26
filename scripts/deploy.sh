#!/bin/bash

# Ahhob Laravel Blog CMS Deployment Script
# This script handles the deployment of the Laravel Blog CMS to various environments

set -e  # Exit on any error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DOCKER_COMPOSE_FILE="$PROJECT_ROOT/docker-compose.yml"
ENV_FILE="$PROJECT_ROOT/src/.env"
BACKUP_DIR="$PROJECT_ROOT/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
ENVIRONMENT="development"
SKIP_BACKUP=false
SKIP_TESTS=false
FORCE_REBUILD=false
VERBOSE=false

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

show_help() {
    cat << EOF
Ahhob Laravel Blog CMS Deployment Script

Usage: $0 [OPTIONS]

Options:
    -e, --env ENVIRONMENT      Set deployment environment (development, staging, production)
                               Default: development
    -s, --skip-backup          Skip database backup before deployment
    -t, --skip-tests           Skip running tests before deployment
    -f, --force-rebuild        Force rebuild of Docker containers
    -v, --verbose              Enable verbose output
    -h, --help                 Show this help message

Environments:
    development                Local development environment
    staging                    Staging/testing environment
    production                 Production environment

Examples:
    $0                         Deploy to development environment
    $0 -e production          Deploy to production
    $0 -e staging -s          Deploy to staging without backup
    $0 -f -v                  Force rebuild with verbose output

EOF
}

parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -e|--env)
                ENVIRONMENT="$2"
                shift 2
                ;;
            -s|--skip-backup)
                SKIP_BACKUP=true
                shift
                ;;
            -t|--skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            -f|--force-rebuild)
                FORCE_REBUILD=true
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                ;;
        esac
    done
}

validate_environment() {
    case $ENVIRONMENT in
        development|staging|production)
            log_info "Deploying to $ENVIRONMENT environment"
            ;;
        *)
            log_error "Invalid environment: $ENVIRONMENT. Must be one of: development, staging, production"
            ;;
    esac
}

check_prerequisites() {
    log_info "Checking prerequisites..."
    
    # Check if Docker is installed and running
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed or not in PATH"
    fi
    
    if ! docker info &> /dev/null; then
        log_error "Docker is not running or not accessible"
    fi
    
    # Check if docker-compose is available
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        log_error "Docker Compose is not installed or not in PATH"
    fi
    
    # Check if project files exist
    if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
        log_error "Docker Compose file not found: $DOCKER_COMPOSE_FILE"
    fi
    
    if [[ ! -d "$PROJECT_ROOT/src" ]]; then
        log_error "Source directory not found: $PROJECT_ROOT/src"
    fi
    
    log_success "Prerequisites check passed"
}

setup_environment() {
    log_info "Setting up environment configuration..."
    
    # Create .env file if it doesn't exist
    if [[ ! -f "$ENV_FILE" ]]; then
        if [[ -f "$PROJECT_ROOT/src/.env.example" ]]; then
            log_info "Creating .env file from .env.example"
            cp "$PROJECT_ROOT/src/.env.example" "$ENV_FILE"
        else
            log_error ".env.example file not found"
        fi
    fi
    
    # Set environment-specific configurations
    case $ENVIRONMENT in
        development)
            sed -i.bak "s/APP_ENV=.*/APP_ENV=local/g" "$ENV_FILE"
            sed -i.bak "s/APP_DEBUG=.*/APP_DEBUG=true/g" "$ENV_FILE"
            sed -i.bak "s/LOG_LEVEL=.*/LOG_LEVEL=debug/g" "$ENV_FILE"
            ;;
        staging)
            sed -i.bak "s/APP_ENV=.*/APP_ENV=staging/g" "$ENV_FILE"
            sed -i.bak "s/APP_DEBUG=.*/APP_DEBUG=true/g" "$ENV_FILE"
            sed -i.bak "s/LOG_LEVEL=.*/LOG_LEVEL=info/g" "$ENV_FILE"
            ;;
        production)
            sed -i.bak "s/APP_ENV=.*/APP_ENV=production/g" "$ENV_FILE"
            sed -i.bak "s/APP_DEBUG=.*/APP_DEBUG=false/g" "$ENV_FILE"
            sed -i.bak "s/LOG_LEVEL=.*/LOG_LEVEL=error/g" "$ENV_FILE"
            ;;
    esac
    
    # Clean up backup files
    rm -f "$ENV_FILE.bak"
    
    log_success "Environment configuration completed"
}

create_backup() {
    if [[ "$SKIP_BACKUP" == true ]]; then
        log_info "Skipping backup as requested"
        return 0
    fi
    
    log_info "Creating backup..."
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.tar.gz"
    
    # Create backup of database and important files
    cd "$PROJECT_ROOT"
    
    # Check if containers are running
    if docker-compose ps | grep -q "Up"; then
        # Backup SQLite database
        if [[ -f "src/database/database.sqlite" ]]; then
            log_info "Backing up SQLite database"
            docker-compose exec -T php sqlite3 /var/www/database/database.sqlite ".backup /var/www/storage/app/backup_$TIMESTAMP.sqlite"
        fi
        
        # Backup uploaded files
        log_info "Creating backup archive"
        tar -czf "$BACKUP_FILE" \
            --exclude='src/node_modules' \
            --exclude='src/vendor' \
            --exclude='src/storage/logs' \
            --exclude='src/storage/framework/cache' \
            --exclude='src/storage/framework/sessions' \
            --exclude='src/storage/framework/views' \
            src/database/ \
            src/storage/app/ \
            src/.env 2>/dev/null || true
        
        log_success "Backup created: $BACKUP_FILE"
    else
        log_warning "Containers not running, skipping database backup"
    fi
}

run_tests() {
    if [[ "$SKIP_TESTS" == true ]]; then
        log_info "Skipping tests as requested"
        return 0
    fi
    
    log_info "Running tests..."
    
    # Ensure containers are running
    docker-compose up -d
    
    # Wait for containers to be ready
    sleep 10
    
    # Run PHPUnit tests
    log_info "Running PHP unit tests"
    if ! docker-compose exec -T php vendor/bin/phpunit; then
        log_error "PHP tests failed. Deployment aborted."
    fi
    
    # Run specific JWT tests
    log_info "Running JWT authentication tests"
    if ! docker-compose exec -T php vendor/bin/phpunit --testsuite=Unit --filter=Jwt; then
        log_warning "Some JWT unit tests failed, but continuing deployment"
    fi
    
    log_success "Tests completed successfully"
}

build_containers() {
    log_info "Building Docker containers..."
    
    cd "$PROJECT_ROOT"
    
    if [[ "$FORCE_REBUILD" == true ]]; then
        log_info "Force rebuilding containers"
        docker-compose build --no-cache
    else
        docker-compose build
    fi
    
    log_success "Docker containers built successfully"
}

deploy_application() {
    log_info "Deploying application..."
    
    cd "$PROJECT_ROOT"
    
    # Stop existing containers
    log_info "Stopping existing containers"
    docker-compose down
    
    # Start containers
    log_info "Starting containers"
    docker-compose up -d
    
    # Wait for containers to be ready
    log_info "Waiting for containers to be ready"
    sleep 15
    
    # Run database migrations
    log_info "Running database migrations"
    docker-compose exec -T php php artisan migrate --force
    
    # Clear and optimize caches
    log_info "Optimizing application"
    docker-compose exec -T php php artisan config:clear
    docker-compose exec -T php php artisan route:clear
    docker-compose exec -T php php artisan view:clear
    
    if [[ "$ENVIRONMENT" == "production" ]]; then
        log_info "Caching configuration for production"
        docker-compose exec -T php php artisan config:cache
        docker-compose exec -T php php artisan route:cache
        docker-compose exec -T php php artisan view:cache
    fi
    
    # Create storage symlink
    docker-compose exec -T php php artisan storage:link
    
    # Set proper permissions
    log_info "Setting file permissions"
    docker-compose exec -T php chown -R noo9ya:noo9ya /var/www/storage
    docker-compose exec -T php chown -R noo9ya:noo9ya /var/www/bootstrap/cache
    
    log_success "Application deployed successfully"
}

verify_deployment() {
    log_info "Verifying deployment..."
    
    # Check if containers are running
    if ! docker-compose ps | grep -q "Up"; then
        log_error "Some containers are not running"
    fi
    
    # Check if web server is responding
    log_info "Checking web server response"
    sleep 5
    if curl -f -s http://localhost >/dev/null; then
        log_success "Web server is responding"
    else
        log_warning "Web server is not responding on port 80"
    fi
    
    # Check database connection
    log_info "Checking database connection"
    if docker-compose exec -T php php artisan migrate:status >/dev/null; then
        log_success "Database connection is working"
    else
        log_warning "Database connection issues detected"
    fi
    
    log_success "Deployment verification completed"
}

show_deployment_info() {
    log_info "Deployment Information:"
    echo "  Environment: $ENVIRONMENT"
    echo "  Project Root: $PROJECT_ROOT"
    echo "  Web URL: http://localhost"
    echo "  Admin URL: http://localhost/admin"
    echo "  API URL: http://localhost/api"
    echo ""
    log_info "Container Status:"
    docker-compose ps
    echo ""
    log_info "Application Logs:"
    echo "  docker-compose logs -f php    # PHP application logs"
    echo "  docker-compose logs -f nginx  # Web server logs"
    echo "  docker-compose logs -f redis  # Redis logs"
    echo ""
    log_success "Deployment completed successfully!"
}

cleanup() {
    log_info "Running cleanup..."
    
    # Remove old backups (keep last 10)
    if [[ -d "$BACKUP_DIR" ]]; then
        find "$BACKUP_DIR" -name "backup_*.tar.gz" -type f | sort | head -n -10 | xargs rm -f
    fi
    
    # Clean up Docker resources
    docker system prune -f >/dev/null 2>&1 || true
    
    log_success "Cleanup completed"
}

# Main deployment flow
main() {
    log_info "Starting deployment of Ahhob Laravel Blog CMS"
    log_info "================================================"
    
    parse_arguments "$@"
    validate_environment
    check_prerequisites
    setup_environment
    create_backup
    build_containers
    run_tests
    deploy_application
    verify_deployment
    show_deployment_info
    cleanup
    
    log_success "Deployment process completed successfully!"
}

# Set verbose mode if requested
if [[ "$VERBOSE" == true ]]; then
    set -x
fi

# Run main function with all arguments
main "$@"
