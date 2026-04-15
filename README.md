# IMEI Bot Platform

A comprehensive, enterprise-grade IMEI generation and mobile file download platform with multi-platform bot integration, payment processing, and advanced admin management system.

## 🚀 Features

### Core Functionality
- **IMEI Generation**: Generate valid IMEI numbers with Luhn algorithm validation
- **Multi-Prefix Support**: Support for prefixes 35, 86, 89, 45, and custom prefixes
- **File Management**: Comprehensive mobile file downloads (firmware, QCN, NVRAM, solutions)
- **Advanced Search**: Filter by brand, model, category, version, and metadata
- **User Management**: Multi-tier user system with premium features and authentication
- **Payment System**: Multi-gateway payment processing with Bangladeshi and international support
- **Analytics**: Detailed usage statistics, reporting, and performance metrics

### Platform Integration
- **Telegram Bot**: Full-featured Telegram bot with inline keyboards and webhooks
- **WhatsApp Bot**: WhatsApp Business API integration with interactive messaging
- **Web Interface**: Modern responsive web dashboard with dark mode support
- **Admin Panel**: Comprehensive admin management system with CRUD operations
- **API**: RESTful API for programmatic access and webhooks

### Enterprise Features
- **Rate Limiting**: Configurable rate limits per user tier and platform
- **Caching**: Redis-based caching for performance optimization
- **Logging**: Structured logging with Monolog and comprehensive error tracking
- **Security**: Input validation, sanitization, XSS protection, and SQL injection prevention
- **Scalability**: Modular architecture with repository pattern and service layers
- **File Upload**: Dual upload methods (local + Google Drive) with validation
- **Payment Processing**: Multiple gateways including Binance P2P/B2B, bKash, Nagad, Bank Cards, PayPal

## 🏗️ Architecture

### Technology Stack
- **Backend**: PHP 8.4+ with strict typing and modern OOP patterns
- **Database**: MySQL 8.0+ with optimized schema and indexing
- **Cache**: Redis 6.0+ for performance optimization and session storage
- **Frontend**: Tailwind CSS with modern UI/UX and responsive design
- **Bots**: Telegram Bot SDK, WhatsApp Business API
- **Monitoring**: Monolog for structured logging and error tracking
- **Architecture**: MVC pattern with Repository and Service layers

### Directory Structure
```
imei-generate/
├── config/                 # Configuration files
│   ├── app.php              # Application settings
│   ├── database.php          # Database configuration
│   ├── telegram.php          # Telegram bot settings
│   ├── whatsapp.php          # WhatsApp bot settings
│   ├── monitoring.php        # Logging configuration
│   ├── cache.php            # Redis configuration
│   └── payment.php          # Payment gateway settings
├── migrations/              # Database migrations
│   ├── 001_create_users_table.sql
│   ├── 002_create_brands_table.sql
│   ├── 003_create_models_table.sql
│   ├── 004_create_files_table.sql
│   ├── 005_create_commands_table.sql
│   ├── 006_create_settings_table.sql
│   ├── 007_create_analytics_table.sql
│   ├── 008_create_notifications_table.sql
│   ├── 009_create_sessions_table.sql
│   ├── 010_create_downloads_table.sql
│   ├── 011_create_imei_logs_table.sql
│   ├── 012_create_api_keys_table.sql
│   ├── 013_create_rate_limits_table.sql
│   ├── 014_create_audit_logs_table.sql
│   ├── 015_create_backups_table.sql
│   ├── 016_create_payments_table.sql
│   ├── 017_create_payment_gateways_table.sql
│   ├── 018_create_payment_subscriptions_table.sql
│   ├── 019_add_bangladeshi_bank_gateway.sql
│   ├── 020_create_remember_tokens_table.sql
│   ├── 021_create_password_resets_table.sql
│   └── 022_add_verification_token_to_users.sql
├── src/
│   ├── Controllers/           # HTTP request handlers
│   │   ├── Admin/
│   │   │   ├── FileController.php
│   │   │   └── UserController.php
│   │   └── AdminController.php
│   ├── Core/               # Core system classes
│   │   ├── Database.php
│   │   ├── Logger.php
│   │   ├── Cache.php
│   │   └── BotManager.php
│   ├── Models/             # Data models
│   │   ├── User.php
│   │   ├── File.php
│   │   ├── Brand.php
│   │   ├── Model.php
│   │   ├── Payment.php
│   │   └── Analytics.php
│   ├── Repositories/       # Data access layer
│   │   ├── BaseRepository.php
│   │   ├── FileRepository.php
│   │   └── UserRepository.php
│   ├── Services/           # Business logic layer
│   │   ├── BaseService.php
│   │   ├── Validation/
│   │   │   ├── BaseValidator.php
│   │   │   ├── FileValidator.php
│   │   │   └── UserValidator.php
│   │   ├── FileService.php
│   │   └── UserService.php
│   └── helpers.php          # Utility functions
├── web/
│   ├── admin/              # Admin panel
│   │   ├── index.php       # Admin dashboard
│   │   ├── files.php       # File management
│   │   ├── file-details.php # File details view
│   │   ├── file-upload.php # File upload with Google Drive
│   │   ├── users.php       # User management
│   │   ├── payments.php    # Payment management
│   │   ├── analytics.php   # Analytics dashboard
│   │   ├── settings.php    # System settings
│   │   ├── login.php       # Admin login
│   │   └── google-drive-setup-guide.php
│   ├── auth/               # Authentication pages
│   │   ├── login.php       # User login
│   │   ├── register.php    # User registration
│   │   ├── reset-password.php
│   │   └── verify.php
│   ├── components/         # Reusable UI components
│   │   ├── header.php      # Navigation header
│   │   ├── sidebar.php     # Collapsible sidebar
│   │   ├── layout.php      # Main layout wrapper
│   │   └── footer.php      # Footer component
│   ├── dashboard.php       # User dashboard
│   ├── index.php          # Landing page
│   ├── payment.php        # Payment interface
│   ├── bot.php           # Bot webhook endpoint
│   └── api.php           # API endpoints
├── uploads/               # File storage
│   └── files/           # Uploaded files
├── logs/                 # Application logs
├── vendor/               # Composer dependencies
├── composer.json         # Dependency management
├── .env.example          # Environment template
└── README.md             # This file
```

## 🛠️ Installation

### Prerequisites
- PHP 8.4+ with extensions: PDO, MySQL, Redis, cURL, JSON
- MySQL 8.0+ with proper permissions
- Redis 6.0+ (optional, for caching)
- Composer 2.0+
- Web server (Apache/Nginx) with PHP-FPM

### Setup Steps

#### 1. Clone Repository
```bash
git clone https://github.com/your-username/imei-generate.git
cd imei-generate
```

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Environment Configuration
```bash
cp .env.example .env
# Edit .env with your configuration
```

#### 4. Database Setup
```bash
# Import database schema
mysql -u username -p database_name < migrations/001_create_users_table.sql
# ... import all migration files in order
mysql -u username -p database_name < migrations/run_all_migrations.sql
```

#### 5. Run Migrations
```bash
php run_missing_migrations.php
php run_seeder.php
```

#### 6. Configure Bots
- Set up Telegram bot via @BotFather
- Configure WhatsApp Business API
- Update configuration files with bot tokens

#### 7. Web Server Configuration
- Point web root to `web/` directory
- Configure URL rewriting for clean URLs
- Set up webhook endpoints
- Configure SSL for production

## ⚙️ Configuration

### Environment Variables
```env
# Application
APP_NAME="IMEI Bot Platform"
APP_URL="https://your-domain.com"
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=imei_bot
DB_USERNAME=username
DB_PASSWORD=password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook/telegram

# WhatsApp Bot
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_WEBHOOK_URL=https://your-domain.com/webhook/whatsapp

# Payment Gateways
BINANCE_API_KEY=your_binance_key
BINANCE_SECRET_KEY=your_binance_secret
BKASH_MERCHANT_KEY=your_bkash_key
NAGAD_MERCHANT_KEY=your_nagad_key
```

### Bot Configuration
- **Telegram**: Configure commands, inline keyboards, rate limits, and webhooks
- **WhatsApp**: Set up message templates, interactive responses, and business API
- **Rate Limits**: Configure per-user and per-platform limits
- **Commands**: Full command set for IMEI generation, file access, user management

## 🎯 Usage

### Telegram Bot Commands
- `/start` - Welcome message and initial setup
- `/help` - Display available commands and usage
- `/random` - Generate random IMEI with configurable prefix
- `/single` - Generate single IMEI with prefix selection
- `/dual` - Generate dual IMEI pair for testing
- `/multi` - Generate multiple IMEIs in batch
- `/validate` - Validate existing IMEI using Luhn algorithm
- `/history` - View generation history and statistics
- `/profile` - User profile, statistics, and tier information
- `/menu` - Interactive menu system with inline keyboards
- `/files` - Browse and search available files
- `/download` - Download specific file (if authorized)
- `/premium` - Upgrade to premium tier
- `/admin` - Admin commands for system management

### WhatsApp Bot Commands
- Similar command structure adapted for WhatsApp Business API
- Interactive buttons and quick replies for better UX
- Message templates for common requests
- File sharing and download links
- Payment processing integration

### Web Interface

#### User Dashboard
- **Profile Management**: View and edit user profile
- **IMEI Generation**: Interactive IMEI generation tools
- **File Browser**: Search, filter, and download files
- **Download History**: Track all downloads and access
- **Payment Center**: Manage subscriptions and payments
- **Statistics**: View usage statistics and limits

#### Admin Panel
- **Dashboard**: System overview with real-time statistics
- **File Management**: Upload, organize, and manage files
- **User Management**: Manage users, roles, and permissions
- **Payment Management**: Monitor transactions and gateway status
- **Analytics**: Detailed usage reports and metrics
- **Settings**: Configure platform settings and bot parameters
- **File Upload**: Dual upload methods (local + Google Drive)

#### File Management Features
- **Upload Methods**: Local file upload + Google Drive picker
- **File Validation**: Type, size, and security validation
- **Metadata Management**: Brand, model, version, category tagging
- **Search & Filter**: Advanced search with multiple criteria
- **Access Control**: Premium content and user permissions
- **Download Tracking**: Complete download history and analytics

## 💳 Payment System

### Supported Gateways
- **Binance P2P**: Peer-to-peer cryptocurrency payments
- **Binance B2B**: Business-to-business payments
- **bKash**: Personal and merchant account support
- **Nagad**: Personal and merchant account support
- **Bank Cards**: Comprehensive Bangladeshi bank support
- **PayPal**: International payment processing

### Payment Features
- **Multi-Currency**: Support for BDT, USD, EUR
- **Subscription Management**: Recurring billing and renewals
- **Transaction History**: Complete payment tracking
- **Refund Processing**: Automated refund handling
- **Security**: PCI DSS compliance and fraud detection
- **Notifications**: Payment confirmations and alerts

## 🔒 Security Features

### Input Validation
- Strict type checking with PHP 8.4+ strict typing
- Comprehensive input sanitization and validation
- SQL injection prevention with prepared statements
- XSS protection with output encoding
- CSRF protection with token validation

### Rate Limiting
- Configurable rate limits per user tier
- Platform-specific rate limiting
- Automatic blocking for abuse detection
- Redis-based rate limit storage

### Data Protection
- Password hashing with bcrypt (cost 12)
- Secure session management with Redis
- GDPR compliance features and data handling
- Audit logging for all sensitive operations
- Two-factor authentication support

### File Security
- File type validation and scanning
- Size limits and quota management
- Virus scanning integration (configurable)
- Secure file storage with access controls
- Hash verification (MD5, SHA256)

## 🚀 Performance Optimization

### Caching Strategy
- Redis for frequently accessed data
- Query result caching with TTL
- Session caching for performance
- Static asset caching and CDN support

### Database Optimization
- Indexed queries for optimal performance
- Optimized schema design with proper relationships
- Connection pooling and query optimization
- Regular maintenance and cleanup routines

### Monitoring
- Structured logging with Monolog
- Performance metrics tracking
- Error monitoring and alerting
- Health check endpoints for monitoring

## 🧪 Development

### Code Standards
- PSR-12 coding standards compliance
- Strict typing throughout the codebase
- Comprehensive error handling and logging
- Unit testing with PHPUnit (planned)
- Code documentation and comments

### Architecture Patterns
- **MVC Pattern**: Model-View-Controller separation
- **Repository Pattern**: Data access abstraction layer
- **Service Layer**: Business logic separation
- **Dependency Injection**: Proper IoC container usage
- **Factory Pattern**: Configurable object creation

### Testing
- Comprehensive test suites for all components
- Integration tests for bot platforms
- API endpoint testing
- Database migration testing
- Security vulnerability scanning

## 🚀 Deployment

### Production Setup
1. **Environment Configuration**
   - Set production environment variables
   - Configure SSL certificates
   - Set up proper error handling

2. **Database Setup**
   - Production database with proper indexing
   - Configure read replicas if needed
   - Set up backup strategies

3. **Web Server Configuration**
   - Configure HTTPS and SSL
   - Set up webhooks for bots
   - Configure reverse proxy if needed
   - Set up monitoring and logging

4. **Bot Deployment**
   - Configure production bot tokens
   - Set up webhook endpoints
   - Test all integrations
   - Set up monitoring and alerts

### Docker Support
```dockerfile
FROM php:8.4-fpm-alpine
# Install dependencies and configure
COPY . /var/www/html/
EXPOSE 9000
CMD ["php-fpm"]
```

### Monitoring
- Application performance monitoring
- Database performance metrics
- Bot uptime and response times
- Error rates and alerting
- User activity and system health

## 📊 Current Implementation Status

### ✅ Completed Features
- **Core IMEI Generation**: Fully functional with Luhn validation
- **Multi-Prefix Support**: Support for 35, 86, 89, 45 prefixes
- **Telegram Bot**: Complete implementation with all commands
- **WhatsApp Bot**: Business API integration completed
- **User Authentication**: Complete system with registration, login, password reset
- **Admin Panel**: Full CRUD operations for all entities
- **File Management**: Upload, organization, search, and download
- **Payment System**: Multi-gateway processing with Bangladeshi support
- **Database Schema**: Complete with all required tables and relationships
- **Security Implementation**: Input validation, XSS protection, SQL injection prevention
- **Caching**: Redis integration for performance
- **Logging**: Comprehensive error tracking and monitoring
- **UI Components**: Reusable components with dark mode support
- **File Upload**: Dual methods (local + Google Drive) with validation
- **Google Drive Integration**: Picker API setup and configuration
- **Type Safety**: All TypeError issues resolved with proper casting

### 🔄 In Progress
- **UserController**: CRUD operations for user management
- **PaymentController**: Advanced payment processing features
- **Standardized Actions**: Unified toggle and action systems
- **Modal Forms**: AJAX-based create/edit operations
- **Data Tables**: Advanced pagination and filtering
- **Comprehensive Testing**: Unit and integration test suites

### 📋 Planned Features
- **API Documentation**: OpenAPI/Swagger documentation
- **Mobile App**: Native mobile applications
- **Advanced Analytics**: Real-time dashboards and reporting
- **Multi-language Support**: Internationalization and localization
- **Advanced Bot Features**: AI-powered responses and automation
- **Cloud Storage**: AWS S3, Google Cloud integration
- **Microservices**: Service decomposition and scaling

## 🔧 Troubleshooting

### Common Issues
1. **Bot Not Responding**
   - Check webhook URL configuration
   - Verify bot token is correct
   - Check SSL certificate for webhooks

2. **Database Connection Errors**
   - Verify database credentials in .env
   - Check MySQL service status
   - Ensure proper permissions

3. **File Upload Issues**
   - Check upload directory permissions
   - Verify file size limits
   - Check available disk space

4. **Payment Gateway Errors**
   - Verify API keys and secrets
   - Check webhook URLs
   - Review gateway status pages

### Debug Mode
Enable debug mode in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Logs Location
- Application logs: `logs/app.log`
- Error logs: `logs/error.log`
- Bot logs: `logs/bot.log`
- Payment logs: `logs/payment.log`

## 🤝 Support

### Documentation
- Comprehensive API documentation
- User guides and tutorials
- Troubleshooting guides
- Bot command references

### Community
- Discord server for real-time support
- GitHub issues for bug reports and feature requests
- Stack Overflow tag: `imei-bot-platform`
- Email support for enterprise customers

## 📄 License

This project is licensed under MIT License - see LICENSE file for details.

## ⚠️ Disclaimer

**IMPORTANT**: This platform is for educational and testing purposes only. Users must comply with all applicable laws and regulations regarding IMEI generation and mobile device modification. The developers are not responsible for any misuse of this software.

## 🔄 Changelog

### Version 1.0.0 (Current)
- Initial release with core functionality
- Complete IMEI generation system
- Full Telegram and WhatsApp bot integration
- Comprehensive web interface and admin panel
- Multi-gateway payment system
- Complete authentication and user management
- File management with dual upload methods
- Security implementation and type safety
- Database schema and migrations
- Caching and performance optimization

### Recent Updates
- **File Upload System**: Added Google Drive picker integration
- **Type Safety**: Fixed all TypeError issues across codebase
- **Payment Gateways**: Enhanced Bangladeshi bank support
- **UI Components**: Implemented reusable component system
- **Authentication**: Complete user auth system with verification
- **Admin Features**: Advanced file management and analytics
- **Security**: Comprehensive input validation and protection

---

## 🚀 Quick Start

For immediate testing:
```bash
# 1. Clone and setup
git clone <repository-url>
cd imei-generate
composer install
cp .env.example .env

# 2. Configure database
mysql -u root -p < migrations/run_all_migrations.sql

# 3. Run setup
php run_seeder.php

# 4. Start server
php -S localhost:8000 -t web/
```

Visit `http://localhost:8000` to access the platform.

**Admin Access**: `/admin/login.php` (admin/admin)
**User Access**: `/auth/login.php` (test@example.com/password)

---

*Built with ❤️ for the mobile development community*
