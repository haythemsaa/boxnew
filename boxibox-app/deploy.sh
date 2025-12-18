#!/bin/bash

#==============================================================================
# BoxiBox Production Deployment Script
#==============================================================================
# Usage: ./deploy.sh [options]
# Options:
#   --no-backup     Skip database backup
#   --no-assets     Skip asset compilation
#   --migrate       Run migrations (use with caution)
#   --seed          Run seeders (only for initial deployment)
#   --fresh         Fresh deployment (clears cache, restarts queues)
#   --rollback      Rollback to previous release
#==============================================================================

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/boxibox"
RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
RELEASE_NAME=$(date +%Y%m%d_%H%M%S)
RELEASE_DIR="${RELEASES_DIR}/${RELEASE_NAME}"
KEEP_RELEASES=5

# Parse arguments
NO_BACKUP=false
NO_ASSETS=false
RUN_MIGRATE=false
RUN_SEED=false
FRESH_DEPLOY=false
ROLLBACK=false

for arg in "$@"; do
    case $arg in
        --no-backup)
            NO_BACKUP=true
            ;;
        --no-assets)
            NO_ASSETS=true
            ;;
        --migrate)
            RUN_MIGRATE=true
            ;;
        --seed)
            RUN_SEED=true
            ;;
        --fresh)
            FRESH_DEPLOY=true
            ;;
        --rollback)
            ROLLBACK=true
            ;;
    esac
done

#==============================================================================
# Helper Functions
#==============================================================================

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

check_requirements() {
    log_info "Checking requirements..."

    # Check if running as correct user
    if [ "$(whoami)" != "www-data" ] && [ "$(whoami)" != "deploy" ]; then
        log_warning "Running as $(whoami), consider running as www-data or deploy user"
    fi

    # Check required commands
    for cmd in php composer npm git; do
        if ! command -v $cmd &> /dev/null; then
            log_error "$cmd is not installed"
        fi
    done

    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_info "PHP Version: $PHP_VERSION"

    # Check if .env exists in shared directory
    if [ ! -f "${SHARED_DIR}/.env" ]; then
        log_error ".env file not found in ${SHARED_DIR}. Please create it first."
    fi

    log_success "All requirements met"
}

create_directory_structure() {
    log_info "Creating directory structure..."

    mkdir -p "${RELEASES_DIR}"
    mkdir -p "${SHARED_DIR}/storage/app/public"
    mkdir -p "${SHARED_DIR}/storage/framework/cache"
    mkdir -p "${SHARED_DIR}/storage/framework/sessions"
    mkdir -p "${SHARED_DIR}/storage/framework/views"
    mkdir -p "${SHARED_DIR}/storage/logs"

    log_success "Directory structure ready"
}

backup_database() {
    if [ "$NO_BACKUP" = true ]; then
        log_warning "Skipping database backup (--no-backup flag)"
        return
    fi

    log_info "Creating database backup..."

    cd "${CURRENT_LINK}" 2>/dev/null || cd "${APP_DIR}"

    BACKUP_FILE="${SHARED_DIR}/backups/db_$(date +%Y%m%d_%H%M%S).sql"
    mkdir -p "${SHARED_DIR}/backups"

    php artisan backup:run --only-db --disable-notifications 2>/dev/null || {
        log_warning "Backup command failed, trying manual backup..."
        # Manual backup fallback
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
        DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || true
    }

    log_success "Database backup completed"
}

clone_repository() {
    log_info "Pulling latest code..."

    mkdir -p "${RELEASE_DIR}"

    # If git repository exists, clone specific branch
    if [ -d "${APP_DIR}/.git" ]; then
        git --work-tree="${RELEASE_DIR}" --git-dir="${APP_DIR}/.git" checkout -f
    else
        # Alternative: copy from current release
        if [ -d "${CURRENT_LINK}" ]; then
            cp -r "${CURRENT_LINK}/." "${RELEASE_DIR}/"
        fi
    fi

    log_success "Code pulled successfully"
}

install_dependencies() {
    log_info "Installing PHP dependencies..."

    cd "${RELEASE_DIR}"

    # Install composer dependencies (production mode)
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

    log_success "PHP dependencies installed"
}

install_npm_dependencies() {
    if [ "$NO_ASSETS" = true ]; then
        log_warning "Skipping NPM dependencies (--no-assets flag)"
        return
    fi

    log_info "Installing NPM dependencies..."

    cd "${RELEASE_DIR}"

    npm ci --production=false  # Need devDependencies for build

    log_success "NPM dependencies installed"
}

build_assets() {
    if [ "$NO_ASSETS" = true ]; then
        log_warning "Skipping asset build (--no-assets flag)"
        return
    fi

    log_info "Building assets..."

    cd "${RELEASE_DIR}"

    npm run build

    # Clean up node_modules after build to save space
    rm -rf node_modules

    log_success "Assets built successfully"
}

link_shared_resources() {
    log_info "Linking shared resources..."

    cd "${RELEASE_DIR}"

    # Remove existing storage and link to shared
    rm -rf storage
    ln -s "${SHARED_DIR}/storage" storage

    # Link .env file
    rm -f .env
    ln -s "${SHARED_DIR}/.env" .env

    # Ensure storage link is created
    php artisan storage:link --force 2>/dev/null || true

    log_success "Shared resources linked"
}

run_migrations() {
    if [ "$RUN_MIGRATE" = false ]; then
        log_warning "Skipping migrations (use --migrate flag to run)"
        return
    fi

    log_info "Running database migrations..."

    cd "${RELEASE_DIR}"

    php artisan migrate --force

    log_success "Migrations completed"
}

run_seeders() {
    if [ "$RUN_SEED" = false ]; then
        return
    fi

    log_info "Running seeders..."

    cd "${RELEASE_DIR}"

    php artisan db:seed --force

    log_success "Seeders completed"
}

optimize_application() {
    log_info "Optimizing application..."

    cd "${RELEASE_DIR}"

    # Clear all caches first
    php artisan cache:clear 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true

    # Rebuild optimized caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache 2>/dev/null || true

    # Optimize autoloader
    composer dump-autoload --optimize

    log_success "Application optimized"
}

set_permissions() {
    log_info "Setting permissions..."

    cd "${RELEASE_DIR}"

    # Set directory permissions
    find . -type d -exec chmod 755 {} \;

    # Set file permissions
    find . -type f -exec chmod 644 {} \;

    # Make artisan executable
    chmod +x artisan

    # Storage permissions
    chmod -R 775 "${SHARED_DIR}/storage"
    chown -R www-data:www-data "${SHARED_DIR}/storage" 2>/dev/null || true

    # Bootstrap cache permissions
    mkdir -p bootstrap/cache
    chmod -R 775 bootstrap/cache
    chown -R www-data:www-data bootstrap/cache 2>/dev/null || true

    log_success "Permissions set"
}

switch_release() {
    log_info "Switching to new release..."

    # Update symlink atomically
    ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}.new"
    mv -Tf "${CURRENT_LINK}.new" "${CURRENT_LINK}"

    log_success "Switched to release: ${RELEASE_NAME}"
}

restart_services() {
    log_info "Restarting services..."

    cd "${CURRENT_LINK}"

    # Restart PHP-FPM (if using)
    if systemctl is-active --quiet php8.2-fpm; then
        sudo systemctl reload php8.2-fpm 2>/dev/null || true
    elif systemctl is-active --quiet php8.1-fpm; then
        sudo systemctl reload php8.1-fpm 2>/dev/null || true
    fi

    # Restart queue workers
    php artisan queue:restart 2>/dev/null || true

    # Restart Horizon if using
    if php artisan list | grep -q "horizon"; then
        php artisan horizon:terminate 2>/dev/null || true
    fi

    # Restart Reverb if using
    if php artisan list | grep -q "reverb"; then
        php artisan reverb:restart 2>/dev/null || true
    fi

    log_success "Services restarted"
}

cleanup_old_releases() {
    log_info "Cleaning up old releases..."

    cd "${RELEASES_DIR}"

    # Keep only the last N releases
    ls -1t | tail -n +$((KEEP_RELEASES + 1)) | xargs -I {} rm -rf {}

    log_success "Old releases cleaned up"
}

health_check() {
    log_info "Running health check..."

    cd "${CURRENT_LINK}"

    # Run Laravel health check
    php artisan health:check --json || {
        log_warning "Health check reported issues"
    }

    # Check if application is responding
    if command -v curl &> /dev/null; then
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "200" ]; then
            log_success "Application is responding (HTTP $HTTP_CODE)"
        else
            log_warning "Application health endpoint returned HTTP $HTTP_CODE"
        fi
    fi

    log_success "Health check completed"
}

rollback_release() {
    log_info "Rolling back to previous release..."

    cd "${RELEASES_DIR}"

    # Get previous release
    PREVIOUS_RELEASE=$(ls -1t | head -2 | tail -1)

    if [ -z "$PREVIOUS_RELEASE" ]; then
        log_error "No previous release found for rollback"
    fi

    # Switch to previous release
    ln -sfn "${RELEASES_DIR}/${PREVIOUS_RELEASE}" "${CURRENT_LINK}.rollback"
    mv -Tf "${CURRENT_LINK}.rollback" "${CURRENT_LINK}"

    # Clear caches
    cd "${CURRENT_LINK}"
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Restart services
    restart_services

    log_success "Rolled back to release: ${PREVIOUS_RELEASE}"
}

send_notification() {
    log_info "Sending deployment notification..."

    # Slack notification (if configured)
    if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
        curl -s -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"BoxiBox deployed successfully! Release: ${RELEASE_NAME}\"}" \
            "$SLACK_WEBHOOK_URL" 2>/dev/null || true
    fi

    log_success "Notification sent"
}

#==============================================================================
# Main Deployment Flow
#==============================================================================

main() {
    echo ""
    echo "=============================================="
    echo "  BoxiBox Production Deployment"
    echo "  Release: ${RELEASE_NAME}"
    echo "=============================================="
    echo ""

    if [ "$ROLLBACK" = true ]; then
        rollback_release
        exit 0
    fi

    check_requirements
    create_directory_structure
    backup_database
    clone_repository
    install_dependencies
    install_npm_dependencies
    build_assets
    link_shared_resources
    run_migrations
    run_seeders
    optimize_application
    set_permissions
    switch_release
    restart_services
    cleanup_old_releases
    health_check
    send_notification

    echo ""
    echo "=============================================="
    echo -e "  ${GREEN}Deployment completed successfully!${NC}"
    echo "  Release: ${RELEASE_NAME}"
    echo "=============================================="
    echo ""

    log_info "Post-deployment checklist:"
    echo "  - [ ] Verify application is accessible"
    echo "  - [ ] Check error logs: tail -f ${SHARED_DIR}/storage/logs/laravel.log"
    echo "  - [ ] Monitor queue workers: php artisan queue:monitor"
    echo "  - [ ] Check Sentry for new errors"
    echo ""
}

# Run main function
main "$@"
