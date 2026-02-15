# PGoldapp Crypto Trading API

A production-grade REST API for cryptocurrency trading platform built with Laravel.

## Features

- Secure user authentication with Laravel Sanctum
- Naira wallet management with complete transaction tracking
- Cryptocurrency trading (BTC, ETH, USDT)
- Real-time pricing from CoinGecko API
- Configurable trading fees
- Complete transaction history with filtering and pagination
- Comprehensive test coverage
- Production-ready security and error handling

## System Architecture

### Core Design Decisions

#### 1. **Service Layer Pattern**
- **WalletService**: Handles all Naira wallet operations (deposits, withdrawals, balance checks)
- **CryptoTradeService**: Manages buy/sell transactions with atomic operations
- **CoinGeckoService**: Integrates with external API with caching and fallback handling
- **FeeCalculator**: Centralized fee calculation logic

**Why?** Separates business logic from controllers, makes testing easier, promotes reusability.

#### 2. **Database Design**

**Users Table**: Standard Laravel auth users

**Wallets Table**: 
- One-to-one with users
- `naira_balance` stored as INTEGER (kobo) for precision
- Soft deletes for audit trail

**Transactions Table**:
- Polymorphic design handles both wallet and trade transactions
- Immutable records (no updates, only inserts)
- Tracks: type, amount, status, metadata

**Crypto Trades Table**:
- Links to transactions table
- Stores: crypto_type, amount, rate_at_trade, fee_amount, trade_type (buy/sell)
- Complete audit trail of every trade

**Why this design?**
- Financial data requires immutability for audit compliance
- Using integers for currency avoids floating-point errors
- Polymorphic transactions allow flexible querying
- Indexes on foreign keys and frequently queried fields

#### 3. **Fee Structure**

**Implementation:**
```
Buy Fee: 1.5% of transaction amount
Sell Fee: 1.0% of transaction amount
Minimum Transaction: ₦1,000
```

**Calculation:**
- Fees calculated BEFORE trade execution
- Buy: User pays (crypto_cost + fee) from wallet
- Sell: User receives (naira_value - fee) to wallet
- All fees rounded using HALF_UP strategy

**Why these percentages?**
- Industry standard is 1-2%
- Higher buy fee discourages speculation
- Transparent, easy to understand
- Profitable at scale

#### 4. **CoinGecko Integration**

**Strategy:**
- Cache rates for 2 minutes (balance freshness vs API limits)
- Free tier: 10-30 calls/minute
- Graceful degradation if API fails
- HTTP client with 10s timeout
- Retry logic with exponential backoff

**Fallback Handling:**
- Return cached data if API unavailable
- Log failures for monitoring
- Return error to user if no cache available

**Why 2-minute cache?**
- Crypto prices change rapidly but not every second
- Reduces API calls by ~90%
- Still provides "fresh enough" pricing
- Falls within free tier limits

#### 5. **Transaction Integrity**

**Critical Guarantees:**
```php
DB::transaction(function () {
    // 1. Lock user's wallet (FOR UPDATE)
    // 2. Validate sufficient balance
    // 3. Deduct/credit wallet
    // 4. Create transaction record
    // 5. Create trade record
    // All or nothing - atomic operation
});
```

**Race Condition Prevention:**
- Pessimistic locking on wallet operations
- Database-level constraints (CHECK constraints for balance >= 0)
- Serializable isolation level for critical operations

**Why this matters?**
- Prevents double-spending
- Ensures data consistency
- No negative balances possible
- Audit trail is always accurate

#### 6. **Security Implementation**

- **Authentication**: Laravel Sanctum with token-based auth
- **Authorization**: Policy-based access control (users only access their own wallets)
- **Rate Limiting**: 60 requests/minute per user, 10/minute for trades
- **Input Validation**: Form Requests with comprehensive rules
- **SQL Injection**: Eloquent ORM with parameter binding
- **XSS Prevention**: API responses are JSON (no HTML rendering)

## Setup Instructions

### Requirements
- PHP 8.2+
- Composer
- **Option A**: Docker & Docker Compose (Recommended)
- **Option B**: SQLite (comes with PHP)
- **Option C**: MySQL/PostgreSQL

### Quick Start Options

---

### Option A: Docker (Recommended - Production-like)

**Why Docker?** Clean isolated database, same environment for everyone, easy cleanup.

1. **Clone and install dependencies**
```bash
git clone <repository-url>
cd pgoldapp-crypto-api
composer install
```

2. **Start Docker containers**
```bash
docker-compose up -d
```
This starts MySQL 8.0 and Redis (optional caching).

3. **Configure environment**
```bash
cp .env.docker .env
php artisan key:generate
```

4. **Wait for database to be ready (5-10 seconds)**
```bash
# Check if MySQL is ready
docker-compose ps
```

5. **Run migrations**
```bash
php artisan migrate --seed
```

6. **Start Laravel**
```bash
php artisan serve
```

**Done!** API running at `http://localhost:8000`

**Cleanup later:**
```bash
docker-compose down -v 
```

---

### Option B: SQLite (Fastest - Zero Config)

**Why SQLite?** Instant setup, no external dependencies.

1. **Clone and install**
```bash
git clone <repository-url>
cd pgoldapp-crypto-api
composer install
```

2. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Create SQLite database**
```bash
touch database/database.sqlite
```

4. **Run migrations**
```bash
php artisan migrate --seed
```

5. **Start server**
```bash
php artisan serve
```

---

### Option C: MySQL/PostgreSQL (Manual Setup)

1. **Create database**
```bash
mysql -u root -p -e "CREATE DATABASE pgoldapp_crypto;"
```

2. **Update .env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgoldapp_crypto
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

3. **Run migrations**
```bash
php artisan migrate --seed
```

---

**Test the API immediately:**
```bash
# Login with test credentials
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

---

### Super Quick Start (Using Makefile)

If you have `make` installed:

**With Docker:**
```bash
make quick-docker
```
Runs: install dependencies → start Docker → configure → migrate → start server

**With SQLite:**
```bash
make quick-sqlite
```
Runs: install dependencies → setup SQLite → configure → migrate → start server

**Other useful commands:**
```bash
make help          
make test         
make clean        
make docker-logs   
```

---

### Testing

```bash
php artisan test

php artisan test --coverage

php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Seeded Test Data

After running seeders, you'll have:
- **Test User**: email: `test@example.com`, password: `password`
- **Wallet**: ₦100,000 balance
- **Sample Transactions**: Various buy/sell trades

## API Documentation

### Authentication

**Register**
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}

Response: 201 Created
{
  "user": {...},
  "token": "1|abc123..."
}
```

**Login**
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123!"
}

Response: 200 OK
{
  "user": {...},
  "token": "2|xyz789..."
}
```

**Logout**
```http
POST /api/logout
Authorization: Bearer {token}

Response: 200 OK
{
  "message": "Logged out successfully"
}
```

### Wallet Management

**Get Wallet**
```http
GET /api/wallet
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "id": 1,
    "balance": "50000.00",
    "balance_kobo": 5000000,
    "formatted_balance": "₦50,000.00"
  }
}
```

**Fund Wallet** (Simulated deposit)
```http
POST /api/wallet/fund
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 10000.00
}

Response: 200 OK
{
  "data": {
    "balance": "60000.00",
    "transaction": {...}
  }
}
```

### Cryptocurrency Trading

**Get Current Rates**
```http
GET /api/crypto/rates
Authorization: Bearer {token}

Response: 200 OK
{
  "data": {
    "btc": {
      "ngn": 98500000.50,
      "usd": 98234.50
    },
    "eth": {
      "ngn": 7850000.25,
      "usd": 3456.78
    },
    "usdt": {
      "ngn": 1545.00,
      "usd": 1.00
    }
  },
  "cached_at": "2024-02-13T10:30:00Z"
}
```

**Buy Cryptocurrency**
```http
POST /api/crypto/buy
Authorization: Bearer {token}
Content-Type: application/json

{
  "crypto_type": "btc",
  "amount_naira": 50000.00
}

Response: 200 OK
{
  "data": {
    "id": 123,
    "crypto_type": "btc",
    "crypto_amount": "0.00050761",
    "naira_cost": "50000.00",
    "fee": "750.00",
    "total_charged": "50750.00",
    "rate": "98500000.50",
    "status": "completed",
    "created_at": "2024-02-13T10:35:00Z"
  }
}
```

**Sell Cryptocurrency**
```http
POST /api/crypto/sell
Authorization: Bearer {token}
Content-Type: application/json

{
  "crypto_type": "eth",
  "amount_crypto": 0.5
}

Response: 200 OK
{
  "data": {
    "id": 124,
    "crypto_type": "eth",
    "crypto_amount": "0.5",
    "naira_received": "3887500.25",
    "fee": "39268.88",
    "rate": "7850000.50",
    "status": "completed",
    "created_at": "2024-02-13T10:40:00Z"
  }
}
```

### Transaction History

**Get All Transactions**
```http
GET /api/transactions?page=1&per_page=20
Authorization: Bearer {token}

Response: 200 OK
{
  "data": [
    {
      "id": 1,
      "type": "crypto_trade",
      "amount": "50750.00",
      "status": "completed",
      "metadata": {...},
      "created_at": "2024-02-13T10:35:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 45,
    "per_page": 20
  }
}
```

**Filter Transactions**
```http
GET /api/transactions?type=crypto_trade&status=completed&from=2024-01-01&to=2024-02-13
Authorization: Bearer {token}
```

**Get Crypto Trades Only**
```http
GET /api/crypto/trades?trade_type=buy&crypto_type=btc
Authorization: Bearer {token}
```

### Error Responses

All errors follow consistent format:

```json
{
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Validation error details"]
  }
}
```

**Common Status Codes:**
- `200 OK`: Success
- `201 Created`: Resource created
- `400 Bad Request`: Validation failed
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Not authorized
- `404 Not Found`: Resource doesn't exist
- `422 Unprocessable Entity`: Business logic error (e.g., insufficient funds)
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

## Trade-offs & Decisions

### What I Prioritized
**Financial integrity**: Atomic transactions, proper decimal handling, audit trails
**Code quality**: Clean architecture, SOLID principles, comprehensive testing
**Security**: Authentication, authorization, rate limiting, input validation
**Developer experience**: Clear documentation, easy setup, good error messages
**Production readiness**: Logging, monitoring hooks, graceful error handling

### What I Simplified (Time Constraints)
**Admin panel**: No UI for monitoring (API-only)
**Advanced caching**: Redis recommended but file cache works
**Background jobs**: Trade execution is synchronous (would queue in production)
**Notification system**: No email/SMS notifications for trades
**2FA**: Basic token auth only (would add in production)
**Withdrawal system**: Wallet funding is simulated (no real payment gateway)

### Future Enhancements
- WebSocket support for real-time price updates
- Trading limits per user tier
- Historical price charts
- Portfolio tracking
- Stop-loss/limit orders
- Multi-currency wallet support
- KYC verification integration

## Testing Strategy

### Coverage Focus
- Authentication flows
- Wallet operations (deposit, withdrawal, balance checks)
- Trade execution (buy/sell with all edge cases)
- Fee calculations
- Transaction history queries
- CoinGecko API integration (mocked)
- Race condition scenarios
- Error handling


## Performance Considerations

- **Database Indexes**: Added on all foreign keys and frequently queried fields
- **Query Optimization**: Eager loading relationships to avoid N+1 queries
- **Caching**: CoinGecko rates cached for 2 minutes
- **Rate Limiting**: Prevents API abuse
- **Connection Pooling**: Recommended for production (configure in database.php)

## Monitoring & Logging

All critical operations are logged:
- Failed login attempts
- Wallet transactions
- Trade executions
- CoinGecko API failures
- Validation errors

Configure log channel in `.env`:
```env
LOG_CHANNEL=stack
LOG_LEVEL=info
```
