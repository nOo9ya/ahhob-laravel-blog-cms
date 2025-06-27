#!/bin/bash

# Ahhob Laravel Blog CMS Production Setup Script
# This script configures the application for production deployment

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/src/.env"
ENV_PRODUCTION_FILE="$PROJECT_ROOT/src/.env.production"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

generate_secure_key() {
    openssl rand -base64 32
}

generate_jwt_secret() {
    openssl rand -base64 32
}

setup_production_env() {
    log_info "Setting up production environment configuration..."
    
    # Create production .env file
    cat > "$ENV_PRODUCTION_FILE" << 'EOF'
# Ahhob Laravel Blog CMS - Production Configuration
# Generated on $(date)

# Application
APP_NAME="Ahhob Blog CMS"
APP_ENV=production
APP_KEY=base64:REPLACE_WITH_GENERATED_KEY
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=REPLACE_WITH_APP_URL

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

# JWT Authentication - Production Settings
JWT_SECRET=REPLACE_WITH_JWT_SECRET
JWT_TTL=15
JWT_REFRESH_TTL=1440
JWT_ALGO=HS256
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=0

# Logging
LOG_CHANNEL=daily
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database - Production (MySQL/PostgreSQL recommended)
DB_CONNECTION=mysql
DB_HOST=REPLACE_WITH_DB_HOST
DB_PORT=3306
DB_DATABASE=REPLACE_WITH_DB_DATABASE
DB_USERNAME=REPLACE_WITH_DB_USERNAME
DB_PASSWORD=REPLACE_WITH_DB_PASSWORD

# Alternative PostgreSQL Configuration
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=ahhob_blog_production
# DB_USERNAME=ahhob_user
# DB_PASSWORD=REPLACE_WITH_DB_PASSWORD

# Session - Production Settings
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=REPLACE_WITH_SESSION_DOMAIN
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE=strict

# Broadcasting & Queues
BROADCAST_CONNECTION=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis

# Cache - Redis for Production
CACHE_STORE=redis
CACHE_PREFIX=ahhob_blog_prod

# Blog Caching - Production Optimized
BLOG_CACHE_ENABLED=true
BLOG_CACHE_PREFIX=ahhob_blog_prod
BLOG_CACHE_USE_TAGS=true
BLOG_CACHE_WARMUP=true
BLOG_CACHE_DEBUG=false
BLOG_CACHE_LOG_HITS=false
BLOG_CACHE_LOG_MISSES=false

# Cache TTL Settings - Production (longer TTL)
BLOG_CACHE_POSTS_TTL=7200
BLOG_CACHE_CATEGORIES_TTL=14400
BLOG_CACHE_TAGS_TTL=7200
BLOG_CACHE_PAGES_TTL=14400
BLOG_CACHE_COMMENTS_TTL=3600
BLOG_CACHE_STATS_TTL=3600
BLOG_CACHE_STATIC_TTL=86400
BLOG_CACHE_SEARCH_TTL=1800

# Image Optimization - Production
IMAGE_OPTIMIZATION_ENABLED=true
IMAGE_MAX_WIDTH=2000
IMAGE_MAX_HEIGHT=2000
IMAGE_DEFAULT_QUALITY=85

# WebP Conversion
IMAGE_CONVERT_TO_WEBP=true
IMAGE_WEBP_QUALITY=80

# Thumbnail Generation
IMAGE_GENERATE_THUMBNAILS=true
THUMBNAIL_SIZE_WIDTH=150
THUMBNAIL_SIZE_HEIGHT=150
THUMBNAIL_QUALITY=80

SMALL_SIZE_WIDTH=300
SMALL_SIZE_HEIGHT=200
SMALL_QUALITY=85

MEDIUM_SIZE_WIDTH=600
MEDIUM_SIZE_HEIGHT=400
MEDIUM_QUALITY=85

LARGE_SIZE_WIDTH=1200
LARGE_SIZE_HEIGHT=800
LARGE_QUALITY=80

# Image Cache
IMAGE_CACHE_ENABLED=true
IMAGE_CACHE_TTL=86400

# Image Security - Production
IMAGE_AUTO_CLEANUP=true
IMAGE_SCAN_MALWARE=false
IMAGE_STRIP_EXIF=true
IMAGE_WATERMARK_ENABLED=false
IMAGE_WATERMARK_TEXT=""
IMAGE_WATERMARK_OPACITY=50

# Redis - Production
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REPLACE_WITH_REDIS_PASSWORD
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2

# Mail - Production SMTP
MAIL_MAILER=smtp
MAIL_HOST=REPLACE_WITH_MAIL_HOST
MAIL_PORT=587
MAIL_USERNAME=REPLACE_WITH_MAIL_USERNAME
MAIL_PASSWORD=REPLACE_WITH_MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=REPLACE_WITH_MAIL_FROM_ADDRESS
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 - Production File Storage
AWS_ACCESS_KEY_ID=REPLACE_WITH_AWS_KEY
AWS_SECRET_ACCESS_KEY=REPLACE_WITH_AWS_SECRET
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=ahhob-blog-production
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_ENDPOINT=
AWS_URL=https://ahhob-blog-production.s3.amazonaws.com

# Vite
VITE_APP_NAME="${APP_NAME}"

# Analytics & Tracking - Production
GA_ENABLED=true
GA_MEASUREMENT_ID=G-XXXXXXXXXX
GA_ANONYMIZE_IP=true
GA_COOKIE_DOMAIN=REPLACE_WITH_GA_COOKIE_DOMAIN
GA_COOKIE_EXPIRES=63072000

GTM_ENABLED=true
GTM_ID=GTM-XXXXXXX

ADSENSE_ENABLED=true
ADSENSE_CLIENT_ID=ca-pub-XXXXXXXXXXXXXXXX
ADSENSE_AUTO_ADS=true

# Analytics Privacy & Consent
ANALYTICS_PRIVACY_MODE=true
ANALYTICS_REQUIRE_CONSENT=true
ANALYTICS_CONSENT_COOKIE=analytics_consent
ANALYTICS_CONSENT_DURATION=365

# Analytics Production
ANALYTICS_DEBUG_MODE=false
ANALYTICS_TEST_MODE=false

# Security Configuration - Production Hardened
XSS_PROTECTION_ENABLED=true
CSP_ENABLED=true

# CSRF Protection
CSRF_PROTECTION_ENABLED=true
CSRF_TOKEN_LIFETIME=120
CSRF_VERIFY_REFERER=true
CSRF_VERIFY_ORIGIN=true

# Password Policy - Strict
PASSWORD_POLICY_ENABLED=true
PASSWORD_MIN_LENGTH=12
PASSWORD_MAX_LENGTH=128
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SYMBOLS=true

# Password History
PASSWORD_HISTORY_ENABLED=true
PASSWORD_HISTORY_COUNT=10

# Password Expiration
PASSWORD_EXPIRATION_ENABLED=true
PASSWORD_EXPIRATION_DAYS=90
PASSWORD_EXPIRATION_WARNING_DAYS=7

# Account Lockout - Strict
PASSWORD_LOCKOUT_ENABLED=true
PASSWORD_MAX_ATTEMPTS=3
PASSWORD_LOCKOUT_DURATION=30
PASSWORD_ATTEMPTS_DECAY=60

# Password Confirmation
PASSWORD_CONFIRMATION_ENABLED=true
PASSWORD_CONFIRMATION_TIMEOUT=3600

# Session Security - Production
SESSION_SECURITY_ENABLED=true
SESSION_REGENERATE_INTERVAL=300
SESSION_VERIFY_IP=true
SESSION_IP_CHANGE_ACTION=logout
SESSION_VERIFY_USER_AGENT=true
SESSION_CONCURRENT_LIMIT_ENABLED=true
SESSION_MAX_CONCURRENT=2
SESSION_CONCURRENT_ACTION=logout_oldest

# Input Validation
INPUT_VALIDATION_ENABLED=true
FILE_SCAN_MALWARE=true
FILE_MAX_SIZE=5242880

# Rate Limiting - Production Strict
RATE_LIMITING_ENABLED=true
LOGIN_MAX_ATTEMPTS=3
LOGIN_ATTEMPTS_DECAY=60
LOGIN_LOCKOUT_DURATION=300
WEB_RATE_LIMIT=1000
WEB_RATE_DECAY=1
API_RATE_LIMIT=60
API_RATE_DECAY=1

# Security Headers - Production
SECURITY_HEADERS_ENABLED=true
HSTS_ENABLED=true
HSTS_MAX_AGE=63072000
HSTS_INCLUDE_SUBDOMAINS=true
HSTS_PRELOAD=true
X_FRAME_OPTIONS=DENY
X_CONTENT_TYPE_OPTIONS=nosniff
X_XSS_PROTECTION="1; mode=block"
REFERRER_POLICY="strict-origin-when-cross-origin"

# Security Monitoring
SECURITY_MONITORING_ENABLED=true
SECURITY_LOG_LEVEL=warning
SECURITY_NOTIFICATIONS_ENABLED=true
SECURITY_REPORTS_ENABLED=true
SECURITY_REPORT_FREQUENCY=daily
SECURITY_REPORT_RECIPIENTS="admin@yourdomain.com"
EOF

    # Prompt for user input
    read -p "Enter your application URL (e.g., https://yourdomain.com): " APP_URL_INPUT
    read -p "Enter your database host (e.g., 127.0.0.1 or your_db_host): " DB_HOST_INPUT
    read -p "Enter your database name: " DB_DATABASE_INPUT
    read -p "Enter your database username: " DB_USERNAME_INPUT
    read -s -p "Enter your database password: " DB_PASSWORD_INPUT
    echo
    read -s -p "Enter your Redis password (leave blank if none): " REDIS_PASSWORD_INPUT
    echo
    read -p "Enter your mail host (e.g., smtp.mailgun.org): " MAIL_HOST_INPUT
    read -p "Enter your mail username (e.g., postmaster@yourdomain.com): " MAIL_USERNAME_INPUT
    read -s -p "Enter your mail password: " MAIL_PASSWORD_INPUT
    echo
    read -p "Enter your mail from address (e.g., noreply@yourdomain.com): " MAIL_FROM_ADDRESS_INPUT
    read -p "Enter your AWS Access Key ID (leave blank if not using S3): " AWS_KEY_INPUT
    read -s -p "Enter your AWS Secret Access Key (leave blank if not using S3): " AWS_SECRET_INPUT
    echo
    read -p "Enter your session domain (e.g., .yourdomain.com): " SESSION_DOMAIN_INPUT
    read -p "Enter your Google Analytics cookie domain (e.g., .yourdomain.com): " GA_COOKIE_DOMAIN_INPUT

    # Generate secure keys
    APP_KEY=$(generate_secure_key)
    JWT_SECRET=$(generate_jwt_secret)
    
    # Replace placeholders with generated values and user input
    sed -i.bak "s|REPLACE_WITH_GENERATED_KEY|$APP_KEY|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_JWT_SECRET|$JWT_SECRET|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_APP_URL|$APP_URL_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_DB_HOST|$DB_HOST_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_DB_DATABASE|$DB_DATABASE_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_DB_USERNAME|$DB_USERNAME_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_DB_PASSWORD|$DB_PASSWORD_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_REDIS_PASSWORD|$REDIS_PASSWORD_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_MAIL_HOST|$MAIL_HOST_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_MAIL_USERNAME|$MAIL_USERNAME_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_MAIL_PASSWORD|$MAIL_PASSWORD_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_MAIL_FROM_ADDRESS|$MAIL_FROM_ADDRESS_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_AWS_KEY|$AWS_KEY_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_AWS_SECRET|$AWS_SECRET_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_SESSION_DOMAIN|$SESSION_DOMAIN_INPUT|g" "$ENV_PRODUCTION_FILE"
    sed -i.bak "s|REPLACE_WITH_GA_COOKIE_DOMAIN|$GA_COOKIE_DOMAIN_INPUT|g" "$ENV_PRODUCTION_FILE"
    
    # Clean up backup file
    rm -f "$ENV_PRODUCTION_FILE.bak"
    
    log_success "Production environment file created: $ENV_PRODUCTION_FILE"
}

create_production_docker_compose() {
    log_info "Creating production Docker Compose configuration..."
    
    cat > "$PROJECT_ROOT/docker-compose.production.yml" << 'EOF'
version: '3.8'

services:
  nginx:
    container_name: nginx-prod
    build:
      context: .
      dockerfile: ./.docker/nginx/Dockerfile
      args:
        - UID=${UID:-1000}
        - GID=${GID:-1000}
        - TZ=${TZ:-UTC}
    restart: unless-stopped
    ports:
      - '80:80'
      - '443:443'
    volumes:
      - ./.docker/nginx/conf/nginx.prod.conf:/etc/nginx/nginx.conf
      - ./.docker/nginx/sites/prod.default.conf:/etc/nginx/conf.d/default.conf
      - ./.docker/nginx/ssl:/etc/nginx/ssl
      - ./src:/var/www/
      - ./.logs/nginx:/var/log/nginx
    depends_on:
      - php
    networks:
      - app-network
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M

  redis:
    container_name: redis-prod
    image: redis:7-alpine
    restart: unless-stopped
    volumes:
      - ./.docker/redis/redis.prod.conf:/usr/local/redis/redis.conf
      - redis_data:/data
      - ./.logs/redis:/var/log/redis
    ports:
      - '127.0.0.1:6379:6379'
    command:
      - redis-server
      - /usr/local/redis/redis.conf
      - --requirepass ${REDIS_PASSWORD}
    networks:
      - app-network
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 256M
        reservations:
          cpus: '0.25'
          memory: 128M

  php:
    container_name: php-prod
    build:
      context: .
      dockerfile: ./.docker/php/8.3/Dockerfile
      args:
        - APP_ENV=prod
        - WORK_DIR=/var/www
        - UID=${UID:-1000}
        - GID=${GID:-1000}
        - TZ=${TZ:-UTC}
    restart: unless-stopped
    volumes:
      - ./src:/var/www/
      - app_storage:/var/www/storage
      - ./.logs/php:/var/log/php
    environment:
      APP_ENV: production
      WORK_DIR: /var/www
    depends_on:
      - redis
    networks:
      - app-network
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 1G
        reservations:
          cpus: '1.0'
          memory: 512M

  queue:
    container_name: queue-prod
    build:
      context: .
      dockerfile: ./.docker/php/8.3/Dockerfile
      args:
        - APP_ENV=prod
        - WORK_DIR=/var/www
        - UID=${UID:-1000}
        - GID=${GID:-1000}
        - TZ=${TZ:-UTC}
    restart: unless-stopped
    volumes:
      - ./src:/var/www/
      - app_storage:/var/www/storage
      - ./.logs/queue:/var/log/queue
    environment:
      APP_ENV: production
      WORK_DIR: /var/www
    depends_on:
      - redis
      - php
    networks:
      - app-network
    command: ['php', '/var/www/artisan', 'queue:work', '--sleep=3', '--tries=3', '--timeout=90']
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M

  scheduler:
    container_name: scheduler-prod
    build:
      context: .
      dockerfile: ./.docker/php/8.3/Dockerfile
      args:
        - APP_ENV=prod
        - WORK_DIR=/var/www
        - UID=${UID:-1000}
        - GID=${GID:-1000}
        - TZ=${TZ:-UTC}
    restart: unless-stopped
    volumes:
      - ./src:/var/www/
      - app_storage:/var/www/storage
      - ./.logs/scheduler:/var/log/scheduler
    environment:
      APP_ENV: production
      WORK_DIR: /var/www
    depends_on:
      - redis
      - php
    networks:
      - app-network
    entrypoint: ['sh', '-c', 'while true; do php /var/www/artisan schedule:run && sleep 60; done']
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 256M
        reservations:
          cpus: '0.25'
          memory: 128M

volumes:
  redis_data:
    driver: local
  app_storage:
    driver: local

networks:
  app-network:
    driver: bridge
EOF

    log_success "Production Docker Compose file created"
}

create_nginx_production_config() {
    log_info "Creating production Nginx configuration..."
    
    mkdir -p "$PROJECT_ROOT/.docker/nginx/sites"
    
    cat > "$PROJECT_ROOT/.docker/nginx/sites/prod.default.conf" << 'EOF'
# Production Nginx Configuration for Ahhob Laravel Blog CMS

# Rate limiting
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;

# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_proxied any;
gzip_comp_level 6;
gzip_types
    text/plain
    text/css
    text/xml
    text/javascript
    application/json
    application/javascript
    application/xml+rss
    application/atom+xml
    image/svg+xml;

server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google-analytics.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://www.google-analytics.com;" always;
    
    # Hide server information
    server_tokens off;
    
    # Client max body size
    client_max_body_size 10M;
    
    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;
    
    # Static file handling with caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        gzip_static on;
    }
    
    # API routes with rate limiting
    location ^~ /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Auth routes with strict rate limiting
    location ^~ /api/auth/ {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Admin routes
    location ^~ /admin/ {
        limit_req zone=general burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ ~$ {
        deny all;
    }
    
    location ~* \.(env|log|htaccess)$ {
        deny all;
    }
    
    # PHP handling
    location ~ \.php$ {
        limit_req zone=general burst=10 nodelay;
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Laravel routing
    location / {
        limit_req zone=general burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Deny access to vendor directory
    location ^~ /vendor/ {
        deny all;
        return 404;
    }
    
    # Robots.txt
    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }
}
EOF

    log_success "Production Nginx configuration created"
}

create_ssl_certificate_script() {
    log_info "Creating SSL certificate generation script..."
    
    cat > "$PROJECT_ROOT/scripts/generate-ssl.sh" << 'EOF'
#!/bin/bash

# SSL Certificate Generation Script
# This script generates self-signed certificates for development
# For production, use Let's Encrypt or a commercial CA

SSL_DIR="$(dirname "$0")/../.docker/nginx/ssl"
mkdir -p "$SSL_DIR"

echo "Generating SSL certificate for development..."

# Generate private key
openssl genrsa -out "$SSL_DIR/key.pem" 2048

# Generate certificate signing request
openssl req -new -key "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.csr" -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"

# Generate self-signed certificate
openssl x509 -req -days 365 -in "$SSL_DIR/cert.csr" -signkey "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.pem"

# Clean up CSR
rm "$SSL_DIR/cert.csr"

echo "SSL certificate generated successfully!"
echo "Certificate: $SSL_DIR/cert.pem"
echo "Private Key: $SSL_DIR/key.pem"
echo ""
echo "For production, replace with certificates from a trusted CA or Let's Encrypt."
EOF

    chmod +x "$PROJECT_ROOT/scripts/generate-ssl.sh"
    
    log_success "SSL certificate generation script created"
}

create_production_checklist() {
    log_info "Creating production deployment checklist..."
    
    cat > "$PROJECT_ROOT/PRODUCTION_CHECKLIST.md" << 'EOF'
# Production Deployment Checklist

## Pre-Deployment

### Security
- [ ] Update all passwords and secrets in `.env.production`
- [ ] Enable HTTPS with valid SSL certificates
- [ ] Configure firewall rules
- [ ] Set up fail2ban or similar intrusion prevention
- [ ] Review and harden security settings
- [ ] Enable security monitoring and alerts

### Infrastructure
- [ ] Set up production database (MySQL/PostgreSQL)
- [ ] Configure Redis for caching and sessions
- [ ] Set up email service (SMTP)
- [ ] Configure AWS S3 or similar for file storage
- [ ] Set up backup strategy
- [ ] Configure monitoring and logging

### Application
- [ ] Run all tests and ensure they pass
- [ ] Build and optimize assets for production
- [ ] Configure analytics (Google Analytics, GTM)
- [ ] Set up error tracking (Sentry, Bugsnag)
- [ ] Configure CDN for static assets
- [ ] Set up domain and DNS

## Deployment Steps

1. **Backup Current System**
   ```bash
   ./scripts/deploy.sh --env production --skip-tests
   ```

2. **Deploy Application**
   ```bash
   docker-compose -f docker-compose.production.yml up -d
   ```

3. **Run Database Migrations**
   ```bash
   docker-compose exec php php artisan migrate --force
   ```

4. **Clear and Cache Configurations**
   ```bash
   docker-compose exec php php artisan config:cache
   docker-compose exec php php artisan route:cache
   docker-compose exec php php artisan view:cache
   ```

5. **Set Permissions**
   ```bash
   docker-compose exec php chown -R www-data:www-data storage bootstrap/cache
   docker-compose exec php chmod -R 775 storage bootstrap/cache
   ```

## Post-Deployment

### Verification
- [ ] Verify website loads correctly
- [ ] Test user registration and login
- [ ] Test admin panel access
- [ ] Verify API endpoints
- [ ] Check SSL certificate installation
- [ ] Test email functionality
- [ ] Verify file uploads work
- [ ] Check analytics tracking

### Monitoring
- [ ] Set up uptime monitoring
- [ ] Configure performance monitoring
- [ ] Set up log aggregation
- [ ] Configure backup verification
- [ ] Set up security monitoring alerts

### Optimization
- [ ] Enable and test caching
- [ ] Optimize database queries
- [ ] Configure image optimization
- [ ] Set up CDN
- [ ] Enable compression
- [ ] Monitor and optimize performance

## Security Hardening

### Server Level
- [ ] Update server packages
- [ ] Configure automatic security updates
- [ ] Set up intrusion detection
- [ ] Configure log monitoring
- [ ] Implement rate limiting
- [ ] Set up DDoS protection

### Application Level
- [ ] Review and update security headers
- [ ] Implement Content Security Policy
- [ ] Configure CORS properly
- [ ] Review file upload restrictions
- [ ] Implement API rate limiting
- [ ] Set up security audit logging

## Maintenance

### Regular Tasks
- [ ] Set up automated backups
- [ ] Configure log rotation
- [ ] Set up dependency updates
- [ ] Plan security updates
- [ ] Monitor performance metrics
- [ ] Review error logs regularly

### Backup Strategy
- [ ] Database backups (daily)
- [ ] File storage backups (weekly)
- [ ] Configuration backups (before changes)
- [ ] Test backup restoration
- [ ] Off-site backup storage

## Emergency Procedures

### Rollback Plan
- [ ] Document rollback procedures
- [ ] Test rollback process
- [ ] Prepare emergency contacts
- [ ] Set up incident response plan

### Contact Information
- Technical Lead: _______________
- System Administrator: _______________
- Database Administrator: _______________
- Emergency Contact: _______________

EOF

    log_success "Production checklist created: $PROJECT_ROOT/PRODUCTION_CHECKLIST.md"
}

show_summary() {
    log_info "Production Setup Summary"
    echo "======================================="
    echo ""
    echo "Files created:"
    echo "  ✓ $ENV_PRODUCTION_FILE"
    echo "  ✓ $PROJECT_ROOT/docker-compose.production.yml"
    echo "  ✓ $PROJECT_ROOT/.docker/nginx/sites/prod.default.conf"
    echo "  ✓ $PROJECT_ROOT/scripts/generate-ssl.sh"
    echo "  ✓ $PROJECT_ROOT/PRODUCTION_CHECKLIST.md"
    echo ""
    log_warning "Important Next Steps:"
    echo "  1. Review and update the production environment file"
    echo "  2. Replace placeholder values with actual credentials"
    echo "  3. Set up production database and Redis"
    echo "  4. Configure SSL certificates"
    echo "  5. Review the production checklist"
    echo ""
    log_warning "Security Reminders:"
    echo "  • Never commit production .env files to version control"
    echo "  • Use strong, unique passwords for all services"
    echo "  • Enable HTTPS with valid SSL certificates"
    echo "  • Regularly update dependencies and monitor security"
    echo "  • Set up monitoring and backup strategies"
    echo ""
    log_success "Production setup completed successfully!"
}

# Main execution
main() {
    log_info "Setting up Ahhob Laravel Blog CMS for Production"
    log_info "================================================"
    
    setup_production_env
    create_production_docker_compose
    create_nginx_production_config
    create_ssl_certificate_script
    create_production_checklist
    show_summary
}

main "$@"
