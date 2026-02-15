# Setup Checklist for Reviewers

**Estimated time: 5 minutes**

## Prerequisites
- PHP 8.2+ installed (`php -v` to check)
- Composer installed (`composer -V` to check)
- Docker & Docker Compose (optional but recommended)

---

## RECOMMENDED: Docker Setup (Production-like)

### 1. Install Dependencies
```bash
composer install
```

### 2. Start Docker Database
```bash
docker-compose up -d
```
Starts MySQL 8.0 + Redis in containers.

### 3. Configure Environment
```bash
cp .env.docker .env
php artisan key:generate
```

### 4. Wait for Database (5-10 seconds)
```bash
docker-compose ps 
```

### 5. Run Migrations
```bash
php artisan migrate --seed
```

### 6. Start Server
```bash
php artisan serve
```

### 7. Test
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

**Cleanup when done:**
```bash
docker-compose down -v
```

---

## ⚡ ALTERNATIVE: SQLite Setup (Zero Config)

If you don't have Docker:

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Create Database
```bash
touch database/database.sqlite
```

### 4. Run Migrations
```bash
php artisan migrate --seed
```
This creates all tables and adds a test user with sample data.

### 5. Start Server
```bash
php artisan serve
```

### 6. Test the API
```bash
# Login to get token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Use the token from response to test other endpoints
```

## Test Credentials
- **Email**: test@example.com
- **Password**: password
- **Wallet Balance**: ₦100,000.00

## Verify Installation

Server running on http://localhost:8000  
Can login and get token  
Can view wallet balance  
Can get crypto rates  
Can execute trades  

## Run Tests
```bash
php artisan test
```

All tests should pass.

## Troubleshooting

### Issue: "Database file not found"
```bash
# Ensure you created the SQLite file:
touch database/database.sqlite
```

### Issue: "Class not found"
```bash
# Regenerate autoload files:
composer dump-autoload
```

### Issue: Tests fail
```bash
# Tests use in-memory SQLite by default
# If failing, check phpunit.xml has:
# <env name="DB_CONNECTION" value="sqlite"/>
# <env name="DB_DATABASE" value=":memory:"/>
```

## Alternative: MySQL Setup

If you prefer MySQL:

1. Create database:
```bash
mysql -u root -p -e "CREATE DATABASE pgoldapp_crypto;"
```

2. Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgoldapp_crypto
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. Run migrations:
```bash
php artisan migrate --seed
```

## What to Review

1. **Code Quality**: Check `app/Services/` for business logic
2. **Database Design**: Review migrations in `database/migrations/`
3. **API Design**: Test endpoints using Postman collection in `docs/`
4. **Testing**: Run `php artisan test` to see test coverage
5. **Documentation**: Read `README.md` for architecture decisions

## Support

If you encounter any issues, please check:
- PHP version is 8.2+
- All Composer dependencies installed
- Database file exists (SQLite) or connection works (MySQL)
- `.env` file is properly configured

---

**Total setup time**: ~5 minutes
**Ready to evaluate**: Yes 
