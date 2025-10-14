# 🚀 AutoFill Copilot - Backend Service

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php" alt="PHP">
<img src="https://img.shields.io/badge/PostgreSQL-15-4169E1?logo=postgresql" alt="PostgreSQL">
<img src="https://img.shields.io/badge/Redis-7-DC382D?logo=redis" alt="Redis">
</p>

## About This Service

This is the backend API service for AutoFill Copilot, built with Laravel 11. It provides a RESTful API for:

-   **User Authentication** - JWT-based authentication with Laravel Sanctum
-   **Profile Management** - JSON-based user profile storage and management
-   **AutoFill Engine** - Intelligent form field analysis and mapping
-   **AI-Powered Analysis** - Integration with AI models for enhanced form understanding
-   **Form Mappings** - Domain-specific form learning and optimization
-   **API Security** - Input validation, rate limiting, and secure data handling

## Key Features

-   🔐 **JWT Authentication** with Laravel Sanctum
-   👤 **Flexible Profile System** with JSON data storage
-   🧠 **Smart Form Analysis** with pattern-based field mapping
-   🤖 **AI Integration** with Together.xyz API for advanced form analysis
-   📊 **Usage Analytics** for form mapping optimization
-   🛡️ **Security First** with comprehensive input validation
-   🚀 **Docker Ready** with optimized container setup

## 🏗️ Project Structure

```
app/
├── Http/Controllers/Api/     # API Controllers
│   ├── AuthController.php    # Authentication endpoints
│   ├── UserController.php    # User management
│   ├── UserProfileController.php  # Profile CRUD
│   ├── FormMappingController.php  # Form mappings
│   └── AutoFillController.php     # AutoFill engine
├── Models/                   # Eloquent Models
│   ├── User.php             # User model
│   ├── UserProfile.php      # Profile data model
│   └── FormMapping.php      # Form mapping model
├── Services/                # Business Logic
│   └── TogetherAIService.php        # AI model integration
└── Providers/               # Service Providers
    └── AppServiceProvider.php
```

## 🗄️ Database Schema

### Users Table

-   Basic user authentication data
-   Linked to Laravel Sanctum for JWT tokens

### User Profiles Table

-   Flexible JSON-based profile storage
-   Support for multiple profiles per user (personal, business, etc.)
-   Profile types and activation status

### Form Mappings Table

-   Domain-specific form field mappings
-   Usage tracking and analytics
-   Learning optimization data

## 🚀 Local Development

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed sample data (optional)
php artisan db:seed

# Start development server
php artisan serve
```

## 🐳 Docker Development

The service is containerized and runs with docker-compose:

```bash
# Build and start
docker-compose up -d

# Run artisan commands
docker-compose exec backend-service php artisan migrate

# View logs
docker-compose logs -f backend-service
```

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter AuthTest
```

## 📚 API Documentation

See the main project README for complete API documentation. Key endpoints:

-   `POST /api/auth/login` - User authentication
-   `GET /api/user-profiles` - Profile management
-   `POST /api/autofill` - Form analysis and filling
-   `POST /api/autofill/analyze-ai` - AI-powered form analysis
-   `GET /api/health` - Health check

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
