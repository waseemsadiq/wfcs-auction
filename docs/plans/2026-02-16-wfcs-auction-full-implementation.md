# WFCS Auction — Full Implementation Plan (v2)

> **For Claude:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` to implement this plan task-by-task. Also REQUIRED: load the `mvc-php-development` skill before writing any PHP. Read `CLAUDE.md` first.

> **Supersedes:** `2026-02-16-wfcs-auction-app.md`

**Goal:** Build a full-stack PHP MVC charity auction platform supporting online, silent, and live auction modes with Stripe payments, Gift Aid, real-time bidding, a versioned REST API, and full PHPUnit test coverage on GitHub Actions CI.

**Architecture:** PHP MVC on Galvani (no frameworks). Repository → Service → Controller → View. All SQL in Repositories. All business logic in Services. Views are plain PHP templates using the three-tier partials system (layouts → partials → atoms) defined in `docs/plans/view-partials-spec.md`. Single `index.php` entry point with manual routing.

**Tech Stack:** PHP 8.2+, MySQL (Galvani embedded / production LAMP), TailwindCSS v4, Vanilla JS, Stripe API, Galvani WebSocket, PHPUnit 11, GitHub Actions CI.

**Search:** MySQL `LIKE` throughout. `WHERE title LIKE '%q%'` on item title only (not description). Category and type dropdowns handle the heavy filtering. GET forms, server-rendered results, URL-based state.

---

## CRITICAL GALVANI RULES (read before touching any code)

1. **No transactions.** Autocommit only. Never `beginTransaction()`/`commit()`.
2. **Singleton DB always.** `Database::getInstance()` everywhere. Never `new Database()`.
3. **Emulated prepares.** `PDO::ATTR_EMULATE_PREPARES => true` in constructor.
4. **No PHP booleans in SQL.** Use `0`/`1`. Never `true`/`false` or SQL `TRUE`/`FALSE`.
5. **LIMIT interpolated.** `'LIMIT ' . (int)$n` — never `LIMIT ?`.
6. **READ COMMITTED.** `SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED` in constructor.
7. **Restart after PHP class changes.** Views are re-read per request; classes are cached.
8. **No auth in constructors.** Router instantiates all controllers at registration.
9. **Socket path:**
```php
'socket' => basename(getcwd()) === 'auction'
    ? dirname(getcwd()) . '/data/mysql.sock'
    : getcwd() . '/data/mysql.sock',
```
10. **DELETE bodies stripped.** Pass DELETE params via `?foo=1` query string, read from `$_GET`.
11. **CSRF via query params on multipart/DELETE.** Use `_csrf_token` query param.
12. **Custom headers dropped.** Bearer tokens go in POST body (`token` field) or GET query param (`?token=xxx`). Never `Authorization` header.

---

## COMPLETE FILE & FOLDER STRUCTURE

```
auction/
├── .github/
│   └── workflows/
│       └── ci.yml
├── app/
│   ├── Controllers/
│   │   ├── Controller.php
│   │   ├── AuthController.php
│   │   ├── AccountController.php
│   │   ├── HomeController.php
│   │   ├── EventController.php
│   │   ├── ItemController.php
│   │   ├── BidController.php
│   │   ├── AdminController.php
│   │   ├── PaymentController.php
│   │   ├── GiftAidController.php
│   │   ├── ApiController.php          ← REST API v1
│   │   └── AuctioneerController.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── EventService.php
│   │   ├── ItemService.php
│   │   ├── BidService.php
│   │   ├── AuctionService.php
│   │   ├── PaymentService.php
│   │   ├── GiftAidService.php
│   │   ├── UploadService.php
│   │   ├── NotificationService.php
│   │   ├── SettingsService.php
│   │   ├── RateLimitService.php
│   │   ├── WebSocketService.php
│   │   └── JWT.php                    ← copied from booking
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   ├── ItemRepository.php
│   │   ├── BidRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── PaymentRepository.php
│   │   ├── GiftAidRepository.php
│   │   ├── SettingsRepository.php
│   │   └── RateLimitRepository.php
│   ├── Models/
│   │   └── Database.php
│   ├── Helpers/
│   │   ├── auth.php
│   │   ├── helpers.php
│   │   ├── view.php
│   │   ├── validation.php
│   │   └── config.php
│   ├── Routes/
│   │   ├── public.php
│   │   ├── auth.php
│   │   ├── account.php
│   │   ├── bidder.php
│   │   ├── donor.php
│   │   ├── admin.php
│   │   ├── auctioneer.php
│   │   └── api.php
│   └── Views/
│       ├── layouts/
│       │   ├── public.php
│       │   ├── admin.php
│       │   └── projector.php
│       ├── partials/
│       │   ├── head.php
│       │   ├── header-public.php
│       │   ├── header-admin.php
│       │   ├── mobile-menu.php
│       │   ├── footer.php
│       │   ├── toast.php
│       │   ├── scripts-dark-mode.php
│       │   └── scripts-mobile-menu.php
│       ├── atoms/
│       │   ├── button.php
│       │   ├── input.php
│       │   ├── label.php
│       │   ├── select.php
│       │   ├── textarea.php
│       │   ├── toggle.php
│       │   ├── file-upload.php
│       │   ├── badge.php
│       │   ├── stat-card.php
│       │   ├── item-card.php
│       │   ├── page-header.php
│       │   ├── breadcrumb.php
│       │   ├── alert.php
│       │   ├── empty-state.php
│       │   ├── table-wrapper.php
│       │   └── popover-shell.php
│       ├── errors/
│       │   ├── 403.php
│       │   ├── 404.php
│       │   └── 500.php
│       ├── auth/
│       │   ├── login.php
│       │   ├── register.php
│       │   ├── forgot-password.php
│       │   ├── reset-password.php
│       │   └── verify-email.php
│       ├── account/
│       │   └── settings.php
│       ├── home/
│       │   ├── index.php
│       │   ├── terms.php
│       │   └── privacy.php
│       ├── events/
│       │   └── show.php
│       ├── items/
│       │   ├── show.php
│       │   └── submit.php
│       ├── bids/
│       │   └── my-bids.php
│       ├── payment/
│       │   ├── checkout.php
│       │   └── success.php
│       ├── admin/
│       │   ├── dashboard.php
│       │   ├── events/
│       │   │   ├── index.php
│       │   │   └── show.php
│       │   ├── items/
│       │   │   └── index.php
│       │   ├── users/
│       │   │   └── index.php
│       │   ├── payments/
│       │   │   └── index.php
│       │   ├── gift-aid/
│       │   │   └── index.php
│       │   └── settings.php
│       └── auctioneer/
│           ├── panel.php
│           └── projector.php
├── config/
│   ├── app.php
│   ├── database.php
│   └── stripe.php
├── css/
│   └── tailwind.css              ← already exists, keep as-is
├── database/
│   ├── schema.sql
│   └── seeds.sql
├── docs/
│   ├── api/
│   │   └── v1.md
│   ├── developer/
│   │   ├── setup.md
│   │   ├── architecture.md
│   │   ├── database.md
│   │   ├── testing.md
│   │   └── deployment.md
│   ├── admin/
│   │   ├── getting-started.md
│   │   ├── events.md
│   │   ├── items.md
│   │   ├── payments.md
│   │   ├── gift-aid.md
│   │   └── live-events.md
│   ├── wiki/
│   │   ├── Home.md
│   │   ├── Architecture.md
│   │   └── API.md
│   ├── mockups/                  ← already exists, DO NOT TOUCH
│   └── plans/                    ← already exists
├── images/                       ← already exists, DO NOT TOUCH
├── tests/
│   ├── bootstrap.php
│   ├── Unit/
│   │   ├── BidServiceTest.php
│   │   ├── GiftAidServiceTest.php
│   │   ├── AuctionServiceTest.php
│   │   ├── AuthServiceTest.php
│   │   └── RateLimitServiceTest.php
│   └── Feature/
│       ├── AuthApiTest.php
│       ├── ItemsApiTest.php
│       ├── BidsApiTest.php
│       ├── EventsApiTest.php
│       └── AdminApiTest.php
├── uploads/                      ← gitignored
├── composer.json
├── package.json                  ← already exists
├── phpunit.xml
├── index.php
├── db-init.php
├── php.ini
└── .env
```

---

## COMPLETE DATABASE SCHEMA

### `settings`
| Column | Type |
|--------|------|
| `key` | VARCHAR(100) PRIMARY KEY |
| `value` | TEXT |

Seeds: `auto_payment_requests='1'`, `manual_payment_review='0'`, `outbid_email_notifications='1'`, `stripe_publishable_key=''`, `stripe_secret_key=''`, `stripe_webhook_secret=''`, `smtp_host=''`, `smtp_port='587'`, `smtp_user=''`, `smtp_pass=''`, `smtp_from_name='WFCS Auction'`, `smtp_from_email='info@wellfoundation.org.uk'`

### `users`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | generated from name |
| `name` | VARCHAR(255) NOT NULL | |
| `email` | VARCHAR(255) UNIQUE NOT NULL | |
| `password_hash` | VARCHAR(255) NOT NULL | |
| `role` | ENUM('admin','donor','bidder') DEFAULT 'bidder' | |
| `postcode` | VARCHAR(20) NULL | for Gift Aid pre-fill |
| `address` | TEXT NULL | for Gift Aid pre-fill |
| `paddle_number` | VARCHAR(20) NULL | live events |
| `email_verified_at` | TIMESTAMP NULL | NULL = unverified |
| `email_verification_token` | VARCHAR(64) NULL | raw random hex token |
| `email_verification_expires_at` | DATETIME NULL | 24h from registration |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### `password_reset_tokens`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `user_id` | INT NOT NULL | FK → users.id ON DELETE CASCADE |
| `token_hash` | VARCHAR(64) NOT NULL | sha256 of the raw token |
| `expires_at` | DATETIME NOT NULL | 1 hour from creation |
| `used_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| INDEX | `(token_hash)` | |

### `rate_limits`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `identifier` | VARCHAR(255) NOT NULL | IP address or `"user:123"` |
| `action` | VARCHAR(50) NOT NULL | `login`, `bid`, `register`, `api_token`, `password_reset`, `resend_verification` |
| `attempts` | INT DEFAULT 1 | |
| `window_start` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `blocked_until` | TIMESTAMP NULL | NULL = not blocked |
| INDEX | `(identifier, action)` | |

**Rate limit thresholds:**
| Action | Max attempts | Window | Block duration |
|--------|-------------|--------|----------------|
| `login` | 5 | 15 min | 30 min |
| `register` | 3 | 1 hour | 1 hour |
| `bid` | 30 | 1 min | 5 min |
| `api_token` | 10 | 1 hour | 1 hour |
| `password_reset` | 3 | 1 hour | 1 hour |
| `resend_verification` | 3 | 1 hour | 1 hour |

### `categories`
| Column | Type |
|--------|------|
| `id` | INT AUTO_INCREMENT PK |
| `slug` | VARCHAR(100) UNIQUE NOT NULL |
| `name` | VARCHAR(100) NOT NULL |
| `sort_order` | INT DEFAULT 0 |

Seeds (in order): Watches, Memorabilia, Experience, Art, Jewellery, Sports, Food & Drink, Holiday, Other

### `events`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | |
| `name` | VARCHAR(255) NOT NULL | |
| `description` | TEXT NULL | |
| `venue` | VARCHAR(255) NULL | |
| `auction_type` | ENUM('online','silent','live') DEFAULT 'online' | |
| `starts_at` | DATETIME NULL | |
| `ends_at` | DATETIME NULL | |
| `auto_payment` | TINYINT(1) DEFAULT 1 | |
| `status` | ENUM('draft','published','active','ended','closed') DEFAULT 'draft' | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Event status machine:**
```
draft → published  (admin clicks "Publish" — event visible to public, no bidding yet)
published → active (admin clicks "Open"    — bidding starts)
active → ended     (admin clicks "Close" OR auto when ends_at passes)
ended → closed     (all payments settled — admin marks closed)
```

### `items`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | |
| `event_id` | INT NULL | FK → events.id |
| `donor_id` | INT NOT NULL | FK → users.id |
| `category_id` | INT NOT NULL | FK → categories.id |
| `title` | VARCHAR(255) NOT NULL | |
| `description` | TEXT NULL | |
| `image_path` | VARCHAR(500) NULL | e.g. `uploads/items/abc.jpg` |
| `starting_bid` | DECIMAL(10,2) NOT NULL | |
| `current_bid` | DECIMAL(10,2) DEFAULT 0.00 | 0 = no bids placed yet |
| `bid_count` | INT DEFAULT 0 | |
| `market_value` | DECIMAL(10,2) NOT NULL | for Gift Aid calc |
| `reserve_price` | DECIMAL(10,2) NOT NULL | |
| `buy_now_price` | DECIMAL(10,2) NULL | |
| `min_increment` | DECIMAL(10,2) DEFAULT 1.00 | |
| `auction_end` | DATETIME NULL | NULL = use event ends_at |
| `lot_order` | INT NULL | live mode only |
| `status` | ENUM('pending','active','ended','awaiting_payment','paid','sold','cancelled') DEFAULT 'pending' | |
| `approved_by` | INT NULL | FK → users.id |
| `approved_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Notes:**
- `current_bid = 0` means no bids — display `starting_bid` as floor.
- Effective end: `COALESCE(items.auction_end, events.ends_at)`.

### `bids`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `item_id` | INT NOT NULL | FK → items.id |
| `bidder_id` | INT NULL | FK → users.id — NULL = floor bid |
| `amount` | DECIMAL(10,2) NOT NULL | |
| `is_floor_bid` | TINYINT(1) DEFAULT 0 | |
| `paddle_number` | VARCHAR(20) NULL | floor bids |
| `intends_gift_aid` | TINYINT(1) DEFAULT 0 | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### `gift_aid_declarations`
| Column | Type |
|--------|------|
| `id` | INT AUTO_INCREMENT PK |
| `user_id` | INT NULL | FK → users.id |
| `bid_id` | INT NULL | FK → bids.id |
| `full_name` | VARCHAR(255) NOT NULL |
| `address` | TEXT NOT NULL |
| `postcode` | VARCHAR(20) NOT NULL |
| `confirmed_taxpayer` | TINYINT(1) DEFAULT 0 |
| `gift_aid_amount` | DECIMAL(10,2) NOT NULL |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PK | |
| `item_id` | INT NOT NULL | FK → items.id |
| `bid_id` | INT NOT NULL | FK → bids.id |
| `winner_id` | INT NOT NULL | FK → users.id |
| `stripe_payment_intent_id` | VARCHAR(255) NULL | |
| `amount` | DECIMAL(10,2) NOT NULL | |
| `gift_aid_amount` | DECIMAL(10,2) DEFAULT 0.00 | |
| `status` | ENUM('pending','processing','paid','failed','refunded') DEFAULT 'pending' | |
| `payment_requested_at` | TIMESTAMP NULL | |
| `paid_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### `audit_log`
| Column | Type |
|--------|------|
| `id` | INT AUTO_INCREMENT PK |
| `user_id` | INT NULL |
| `action` | VARCHAR(100) NOT NULL |
| `entity_type` | VARCHAR(50) NULL |
| `entity_id` | INT NULL |
| `details` | TEXT NULL | JSON string |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP |

---

## COMPLETE ROUTE TABLE

Format: `METHOD /path → Controller::method() [auth_level]`

Auth levels: `[pub]` = public, `[auth]` = any logged-in, `[bidder]`, `[donor|admin]`, `[admin]`

### Public / Static
```
GET  /                              HomeController::index()                [pub]
GET  /terms                         HomeController::terms()                [pub]
GET  /privacy                       HomeController::privacy()              [pub]
GET  /events/:slug                  EventController::show()                [pub]
GET  /items/:slug                   ItemController::show()                 [pub]
GET  /display/:event_slug           AuctioneerController::projector()      [pub]
```

### Auth
```
GET  /login                         AuthController::loginPage()            [pub]
POST /login                         AuthController::login()                [pub]  ← rate limited: login
GET  /register                      AuthController::registerPage()         [pub]
POST /register                      AuthController::register()             [pub]  ← rate limited: register
GET  /logout                        AuthController::logout()               [auth]
GET  /forgot-password               AuthController::forgotPasswordPage()   [pub]
POST /forgot-password               AuthController::forgotPassword()       [pub]  ← rate limited: password_reset
GET  /reset-password                AuthController::resetPasswordPage()    [pub]  ?token=xxx
POST /reset-password                AuthController::resetPassword()        [pub]
GET  /verify-email                  AuthController::verifyEmail()          [pub]  ?token=xxx
POST /resend-verification           AuthController::resendVerification()   [auth] ← rate limited: resend_verification
```

### Account
```
GET  /account                       AccountController::settings()          [auth]
POST /account/profile               AccountController::updateProfile()     [auth]
POST /account/password              AccountController::updatePassword()    [auth]
```

### Bidder
```
GET  /my-bids                       BidController::myBids()                [bidder]
POST /bids                          BidController::place()                 [bidder] ← rate limited: bid
GET  /payment/:item_slug            PaymentController::checkout()          [bidder]
POST /payment/:item_slug            PaymentController::processCheckout()   [bidder]
GET  /payment/:item_slug/success    PaymentController::success()           [bidder]
```

### Donor
```
GET  /items/submit                  ItemController::submitPage()           [donor|admin]
POST /items/submit                  ItemController::submit()               [donor|admin]
```

### Admin — Dashboard
```
GET  /admin                         AdminController::dashboard()           [admin]
```

### Admin — Events
```
GET  /admin/auctions                AdminController::events()              [admin]
POST /admin/auctions                AdminController::createEvent()         [admin]
GET  /admin/auctions/:slug          AdminController::showEvent()           [admin]
POST /admin/auctions/:slug          AdminController::updateEvent()         [admin]
POST /admin/auctions/:slug/delete   AdminController::deleteEvent()         [admin]
POST /admin/auctions/:slug/publish  AdminController::publishEvent()        [admin]
POST /admin/auctions/:slug/open     AdminController::openEvent()           [admin]
POST /admin/auctions/:slug/close    AdminController::closeEvent()          [admin]
```

### Admin — Items
```
GET  /admin/items                   AdminController::items()               [admin]
POST /admin/items/:slug/approve     AdminController::approveItem()         [admin]
POST /admin/items/:slug/reject      AdminController::rejectItem()          [admin]
POST /admin/items/:slug/update      AdminController::updateItem()          [admin]
POST /admin/items/:slug/delete      AdminController::deleteItem()          [admin]
POST /admin/items/:slug/cancel      AdminController::cancelItem()          [admin]
```

### Admin — Users
```
GET  /admin/users                   AdminController::users()               [admin]
POST /admin/users/:slug/role        AdminController::updateUserRole()      [admin]
```

### Admin — Payments
```
GET  /admin/payments                AdminController::payments()            [admin]
POST /admin/payments/:id/request    AdminController::requestPayment()      [admin]
POST /admin/payments/:id/mark-paid  AdminController::markPaid()            [admin]
POST /admin/payments/:id/resend     AdminController::resendPaymentEmail()  [admin]
```

### Admin — Gift Aid
```
GET  /admin/gift-aid                AdminController::giftAid()             [admin]
GET  /admin/gift-aid/export         AdminController::exportGiftAid()       [admin]
```

### Admin — Settings
```
GET  /admin/settings                AdminController::settings()            [admin]
POST /admin/settings                AdminController::saveSettings()        [admin]
```

### Auctioneer (live mode)
```
GET  /auctioneer/:event_slug        AuctioneerController::panel()          [admin]
POST /auctioneer/open-lot           AuctioneerController::openLot()        [admin]
POST /auctioneer/close-lot          AuctioneerController::closeLot()       [admin]
POST /auctioneer/floor-bid          AuctioneerController::floorBid()       [admin]
```

### Internal API (polling — no version prefix, web-only)
```
GET  /api/items/:slug/bid           BidController::currentBid()            [pub]    → {current_bid, bid_count, status, ends_at}
GET  /api/my-bids/status            BidController::myBidStatus()           [bidder] → {item_slug: {status, current_bid, your_bid}}
POST /api/bids                      BidController::placeAjax()             [bidder] → {success, message, new_bid}
GET  /api/live/:event_slug          AuctioneerController::liveState()      [pub]    → {current_lot, current_bid, status}
```

### REST API v1
All routes prefixed `/api/v1/`. JSON responses only. Auth via `token` in POST body or `?token=` query param.

```
# Auth
POST /api/v1/auth/token             ApiController::token()                 [pub]  ← rate limited: api_token
                                    body: {email, password}
                                    returns: {token, expires_at, user: {id, slug, name, email, role}}

DELETE /api/v1/auth/token           ApiController::revokeToken()           [auth] body: {token}

# Items (read)
GET  /api/v1/items                  ApiController::listItems()             [pub]  ?q=&category=&type=&status=&page=
GET  /api/v1/items/:slug            ApiController::getItem()               [pub]  → full item with bids

# Items (write — auth required)
POST /api/v1/items                  ApiController::createItem()            [donor|admin]
PUT  /api/v1/items/:slug            ApiController::updateItem()            [admin]
DELETE /api/v1/items/:slug          ApiController::deleteItem()            [admin]
POST /api/v1/items/:slug/approve    ApiController::approveItem()           [admin]
POST /api/v1/items/:slug/reject     ApiController::rejectItem()            [admin]

# Events
GET  /api/v1/events                 ApiController::listEvents()            [pub]
GET  /api/v1/events/:slug           ApiController::getEvent()              [pub]
POST /api/v1/events                 ApiController::createEvent()           [admin]
PUT  /api/v1/events/:slug           ApiController::updateEvent()           [admin]
DELETE /api/v1/events/:slug         ApiController::deleteEvent()           [admin]
POST /api/v1/events/:slug/publish   ApiController::publishEvent()          [admin]
POST /api/v1/events/:slug/open      ApiController::openEvent()             [admin]
POST /api/v1/events/:slug/close     ApiController::closeEvent()            [admin]

# Bids
GET  /api/v1/items/:slug/bids       ApiController::listBids()              [pub]
POST /api/v1/items/:slug/bids       ApiController::placeBid()              [bidder] ← rate limited: bid

# Users (admin only)
GET  /api/v1/users                  ApiController::listUsers()             [admin]
GET  /api/v1/users/:slug            ApiController::getUser()               [admin]
PUT  /api/v1/users/:slug/role       ApiController::updateUserRole()        [admin]

# Payments (admin only)
GET  /api/v1/payments               ApiController::listPayments()          [admin]
POST /api/v1/payments/:id/request   ApiController::requestPayment()        [admin]
POST /api/v1/payments/:id/mark-paid ApiController::markPaid()              [admin]

# Gift Aid (admin only)
GET  /api/v1/gift-aid               ApiController::listGiftAid()           [admin]
GET  /api/v1/gift-aid/export        ApiController::exportGiftAid()         [admin]  → CSV download

# Categories
GET  /api/v1/categories             ApiController::listCategories()        [pub]

# My account
GET  /api/v1/me                     ApiController::me()                    [auth]
PUT  /api/v1/me                     ApiController::updateMe()              [auth]
GET  /api/v1/me/bids                ApiController::myBids()                [bidder]
```

### Webhooks
```
POST /webhooks/stripe               PaymentController::stripeWebhook()     [pub, NO CSRF]
```

---

## KEY FLOWS (step-by-step, no ambiguity)

### Flow: Registration + Email Verification
1. User submits `POST /register` with name, email, password, confirm_password, role, postcode (optional), address (optional)
2. Rate limit check: `register` action for IP. If blocked → flash error "Too many registrations from this address. Please try again later." → redirect back.
3. Validate: name not empty, email valid format, email not already taken, password ≥ 8 chars, passwords match, role in `['bidder','donor']`
4. Hash password: `password_hash($password, PASSWORD_DEFAULT)`
5. Generate slug from name (lowercase, replace spaces with `-`, strip non-alphanumeric, append `-2` / `-3` if taken)
6. Generate email verification token: `bin2hex(random_bytes(32))` → store raw in `email_verification_token`, store expiry `NOW() + 24 hours` in `email_verification_expires_at`
7. Insert user (email_verified_at = NULL)
8. Send verification email: link = `{APP_URL}/verify-email?token={raw_token}`
9. Auto-login: generate JWT, set auth cookie
10. `setFlash('info', 'Account created! Please check your email to verify your address.')` → redirect to `/`

### Flow: Email Verification
1. User clicks link: `GET /verify-email?token=xxx`
2. Look up user where `email_verification_token = ?` AND `email_verification_expires_at > NOW()` AND `email_verified_at IS NULL`
3. If not found: render `auth/verify-email.php` with `$expired = true` — show "This link has expired or is invalid" + resend button
4. If found: set `email_verified_at = NOW()`, clear `email_verification_token` and `email_verification_expires_at`
5. `setFlash('success', 'Email verified! You can now place bids.')` → redirect to `/`
6. `POST /resend-verification`: rate limit check → generate new token → update user → send email → flash "Verification email sent."

### Flow: Forgot Password
1. User submits `POST /forgot-password` with email
2. Rate limit check: `password_reset` for IP
3. Always show same response: `setFlash('info', 'If an account with that email exists, a reset link has been sent.')` — never leak whether email exists
4. Look up user by email. If not found: redirect (response already set above — do nothing else)
5. If found: generate token `bin2hex(random_bytes(32))`, hash it `hash('sha256', $token)`, insert into `password_reset_tokens` (expires 1 hour), expire any previous unused tokens for this user
6. Send email with link: `{APP_URL}/reset-password?token={raw_token}`
7. Redirect to `/login`

### Flow: Reset Password
1. `GET /reset-password?token=xxx`: hash token → look up in `password_reset_tokens` where `token_hash = ?` AND `expires_at > NOW()` AND `used_at IS NULL`
2. If not found: render `auth/reset-password.php` with `$invalid = true` — "This link has expired or been used."
3. If found: render `auth/reset-password.php` with `$token` (pass raw token through a hidden field)
4. `POST /reset-password` with `token`, `password`, `confirm_password`:
   - Validate: hash token, look up again (re-verify — prevents race conditions), passwords match, password ≥ 8 chars
   - Update user: `password_hash($password, PASSWORD_DEFAULT)`
   - Mark token used: `UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?`
   - Clear all other unused tokens for this user
   - Clear any existing auth cookie, generate new JWT, set cookie (auto-login)
   - `setFlash('success', 'Password updated. You are now logged in.')` → redirect to `/`

### Flow: Bid Placement (web)
1. User submits `POST /bids` with `item_id`, `amount`, `intends_gift_aid`, optional gift aid fields
2. `requireRole('bidder')` — redirect to `/login` if not auth, 403 if wrong role
3. Rate limit: `bid` for `"user:{user_id}"`
4. Email verified check: `if (!$user['email_verified_at'])` → `setFlash('error', 'Please verify your email address before bidding.')` → redirect back
5. Fetch item by ID. Verify `status = 'active'`
6. Verify effective end time has not passed
7. Verify `$user['id'] !== $item['donor_id']`
8. Verify `$amount >= $item['starting_bid']`
9. Verify `$amount >= $item['current_bid'] + $item['min_increment']`
10. Get current leading bidder (for outbid notification)
11. Insert bid, update `current_bid` + `bid_count` on item
12. If `intends_gift_aid = 1` AND `amount > market_value`: save Gift Aid declaration
13. If previous leading bidder exists AND notifications enabled: queue outbid email
14. `setFlash('success', 'Bid placed successfully!')` → redirect to `/items/{slug}`

### Flow: Auction End + Payment
1. On every admin page load: `AuctionService::processEndedItems()` runs
2. Fetch items: `status = 'active'` AND `COALESCE(auction_end, event.ends_at) < NOW()`
3. For each ended item:
   - Set `status = 'ended'`
   - Find winning bid (highest amount)
   - If winner: create payment record (`status = 'pending'`), set item `status = 'awaiting_payment'`
   - If `auto_payment = 1`: call `PaymentService::requestPayment()` → create Stripe PaymentIntent → email winner with `/payment/{slug}` link
   - Log to `audit_log`
4. Winner hits `/payment/:item_slug`:
   - Verify `$user['id'] === $payment['winner_id']`
   - `PaymentService::getClientSecret()` → returns Stripe `client_secret`
   - Render checkout page with Stripe Elements
5. Stripe webhook `POST /webhooks/stripe`:
   - Verify signature using `stripe_webhook_secret` from settings
   - On `payment_intent.succeeded`: mark payment `paid`, item `sold`, email winner confirmation, email admin notification
   - On `payment_intent.payment_failed`: mark payment `failed`, notify admin

### Flow: Stripe Webhook Secret Setup
**Instructions for admin (run once):**
1. Log into Stripe Dashboard → Developers → Webhooks
2. Click "Add endpoint"
3. Endpoint URL: `https://yourdomain.com/webhooks/stripe` (use your actual domain)
4. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
5. Click "Add endpoint"
6. On the endpoint detail page, click "Reveal" under "Signing secret"
7. Copy the value (starts with `whsec_`)
8. Go to Admin → Settings → Stripe tab
9. Paste into "Webhook Secret" field and save
10. For local testing: use Stripe CLI → `stripe listen --forward-to localhost:8080/auction/webhooks/stripe` → it prints a local webhook secret, put that in settings while testing

### Flow: Event Status Transitions (admin)
```
draft    → "Publish"  → POST /admin/auctions/:slug/publish → status = 'published'
                         Event becomes visible on public site (event page shows countdown)
                         Items NOT yet accepting bids

published → "Open"   → POST /admin/auctions/:slug/open    → status = 'active'
                        All items in event set to 'active' (if approved)
                        Bidding begins

active   → "Close"   → POST /admin/auctions/:slug/close   → status = 'ended'
                        All active items → 'ended'
                        AuctionService::processEventClose() → identify winners → create payments

ended    → "Close"   → (auto — all payments settled) status = 'closed'
```

### Flow: Live Auction (auctioneer panel)
1. Admin navigates to `/auctioneer/:event_slug` — event must be `active`
2. Panel shows all lots ordered by `lot_order`
3. "Open Lot": `POST /auctioneer/open-lot` → `{item_slug}` → sets item `active`, broadcasts `lot_opened` via WebSocket
4. Online bidders and projector receive WebSocket event, update UI
5. Auctioneer enters floor bid: `POST /auctioneer/floor-bid` → `{item_slug, amount, paddle_number}` → inserts bid with `is_floor_bid=1`, `bidder_id=NULL`, updates `current_bid`, broadcasts `bid_placed`
6. Online bidders can also `POST /api/bids` → JSON response → triggers WebSocket broadcast
7. "Close Lot": `POST /auctioneer/close-lot` → `{item_slug}` → sets item `ended`, finds winner, creates payment record, broadcasts `lot_closed`
8. Projector (`/display/:event_slug`) receives `lot_closed` → shows winner info

### Flow: Rate Limiting
`RateLimitService::check(string $identifier, string $action): void`
- Looks up `rate_limits` for `(identifier, action)`
- If `blocked_until > NOW()`: throw `RateLimitException` with time remaining
- If record exists and within window: increment attempts
- If record exists and outside window: reset (update window_start, attempts=1)
- If no record: insert
- If attempts exceed threshold: set `blocked_until`
- Controllers catch `RateLimitException`, call `setFlash('error', ...)` and redirect

### Flow: API Authentication
1. `POST /api/v1/auth/token` with `email` + `password` in body
2. Rate limit: `api_token` for IP
3. Validate credentials → generate JWT (24h expiry, payload: `{user_id, email, role}`)
4. Return `{token, expires_at, user: {...}}`
5. Subsequent requests:
   - GET: `?token={jwt}`
   - POST/PUT/DELETE: `token={jwt}` in body
6. `ApiController` helper `getApiUser()`: checks `$_POST['token'] ?? $_GET['token'] ?? null` → `JWT::decode()` → fetch user from DB
7. Errors return JSON: `{error: 'message', code: 401}` with appropriate HTTP status code

### Flow: Search (LIKE)
All search is GET-based — URL params preserved on refresh/share:
- `GET /?q=rolex&category=watches&type=online&status=active`
- Controller reads `$_GET`, passes to repository
- Repository: `WHERE i.status = 'active' AND i.title LIKE :q AND c.slug = :category AND ...`
- `:q` bound as `'%' . $q . '%'`
- Results re-rendered server-side, same page template
- Empty search (`q=''`) returns all results (LIKE `'%%'` matches everything)

---

## RATE LIMITING IMPLEMENTATION

### `RateLimitService`
```php
class RateLimitService {
    public function __construct(private RateLimitRepository $repo) {}

    public function check(string $identifier, string $action): void {
        $record = $this->repo->find($identifier, $action);
        $thresholds = [
            'login'                => ['max' => 5,  'window' => 900,  'block' => 1800],
            'register'             => ['max' => 3,  'window' => 3600, 'block' => 3600],
            'bid'                  => ['max' => 30, 'window' => 60,   'block' => 300],
            'api_token'            => ['max' => 10, 'window' => 3600, 'block' => 3600],
            'password_reset'       => ['max' => 3,  'window' => 3600, 'block' => 3600],
            'resend_verification'  => ['max' => 3,  'window' => 3600, 'block' => 3600],
        ];
        $t = $thresholds[$action] ?? ['max' => 10, 'window' => 3600, 'block' => 3600];

        if ($record && $record['blocked_until'] && strtotime($record['blocked_until']) > time()) {
            $remaining = strtotime($record['blocked_until']) - time();
            throw new \RuntimeException("Too many attempts. Try again in " . ceil($remaining / 60) . " minutes.");
        }
        if ($record && (time() - strtotime($record['window_start'])) < $t['window']) {
            $attempts = $record['attempts'] + 1;
            $blocked = $attempts >= $t['max'] ? date('Y-m-d H:i:s', time() + $t['block']) : null;
            $this->repo->increment($record['id'], $attempts, $blocked);
            if ($blocked) {
                throw new \RuntimeException("Too many attempts. Try again in " . ceil($t['block'] / 60) . " minutes.");
            }
        } else {
            $this->repo->upsert($identifier, $action);
        }
    }
}
```

### `RateLimitRepository`
```php
find(string $identifier, string $action): ?array
increment(int $id, int $attempts, ?string $blockedUntil): void
upsert(string $identifier, string $action): void  // INSERT or reset window
cleanup(): void  // DELETE WHERE window_start < NOW() - 86400 (daily maintenance)
```

---

## ERROR PAGES

### `errors/404.php`
- Public layout, max-w-4xl
- Large "404" heading, "Page not found", brief message, "Go home" button
- No stack trace

### `errors/403.php`
- Public layout, max-w-4xl
- "Access denied" + appropriate message
- Link to login if not authenticated

### `errors/500.php`
- Plain HTML only (no layout — layout itself may be broken)
- "Something went wrong" — no stack trace, no PHP errors
- Contact email

### Error handling in `index.php`
```php
set_exception_handler(function(\Throwable $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    include __DIR__ . '/app/Views/errors/500.php';
    exit;
});

// 404: at end of routing, if no route matched:
http_response_code(404);
include __DIR__ . '/app/Views/errors/404.php';
exit;
```

---

## CI / TESTING

### `phpunit.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
  bootstrap="tests/bootstrap.php"
  colors="true">
  <testsuites>
    <testsuite name="Unit">
      <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
      <directory>tests/Feature</directory>
    </testsuite>
  </testsuites>
  <coverage>
    <include>
      <directory suffix=".php">app/Services</directory>
      <directory suffix=".php">app/Repositories</directory>
    </include>
  </coverage>
</phpunit>
```

### `tests/bootstrap.php`
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = __DIR__ . '/../.env.test';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim(trim($v), '"\'');
        }
    }
}

require_once __DIR__ . '/../app/Helpers/config.php';
require_once __DIR__ . '/../app/Helpers/helpers.php';
require_once __DIR__ . '/../app/Helpers/view.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/validation.php';
```

### `.env.test`
```
DB_HOST=127.0.0.1
DB_DATABASE=auction_test
DB_USER=root
DB_PASS=root
APP_KEY=0000000000000000000000000000000000000000000000000000000000000001
JWT_SECRET=test_jwt_secret_do_not_use_in_production
APP_URL=http://localhost
```

### `.github/workflows/ci.yml`
```yaml
name: CI
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: auction_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, pdo_mysql, openssl
          coverage: none

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Set up test database
        run: mysql -h 127.0.0.1 -u root -proot auction_test < database/schema.sql

      - name: Run unit tests
        run: vendor/bin/phpunit --testsuite Unit
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: auction_test
          DB_USER: root
          DB_PASS: root
          APP_KEY: "0000000000000000000000000000000000000000000000000000000000000001"
          JWT_SECRET: test_jwt_secret

      - name: Run feature tests
        run: vendor/bin/phpunit --testsuite Feature
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: auction_test
          DB_USER: root
          DB_PASS: root
          APP_KEY: "0000000000000000000000000000000000000000000000000000000000000001"
          JWT_SECRET: test_jwt_secret
```

### Unit Tests to write (TDD — write test first, then implementation)

#### `tests/Unit/BidServiceTest.php`
- `testBidMustExceedCurrentBid()` — place bid at current_bid, expect exception
- `testBidMustMeetMinIncrement()` — bid at current_bid + 0.50, increment is 1.00 → exception
- `testBidMustMeetStartingBid()` — bid below starting_bid → exception
- `testDonorCannotBidOwnItem()` — bidder_id === donor_id → exception
- `testItemMustBeActiveToAcceptBid()` — status = 'ended' → exception
- `testEmailMustBeVerifiedToBid()` — user email_verified_at is null → exception
- `testValidBidUpdatesCurrentBid()` — happy path, returns success array
- `testBuyNowEndsBidding()` — buy_now placed, item status set to ended

#### `tests/Unit/GiftAidServiceTest.php`
- `testCalculationAboveMarketValue()` — bid £200, market £100 → £25
- `testCalculationAtMarketValue()` — bid £100, market £100 → £0
- `testCalculationBelowMarketValue()` — bid £50, market £100 → £0
- `testDeclarationRequiresConfirmedTaxpayer()` — confirmed = 0 → exception
- `testDeclarationRequiresFullName()` — empty name → exception

#### `tests/Unit/AuctionServiceTest.php`
- `testEndedItemGetsWinnerIdentified()`
- `testItemWithNoBidsStaysEnded()`
- `testAutoPaymentRequestTriggeredWhenSettingOn()`
- `testAutoPaymentSkippedWhenSettingOff()`
- `testEffectiveEndTimeUsesItemAuctionEndWhenSet()`
- `testEffectiveEndTimeUsesEventEndsAtWhenItemAuctionEndNull()`

#### `tests/Unit/AuthServiceTest.php`
- `testPasswordIsHashed()`
- `testDuplicateEmailThrows()`
- `testLoginWithWrongPasswordThrows()`
- `testLoginWithUnknownEmailThrows()`
- `testSlugGeneratedFromName()`
- `testSlugDeduplicatedWithSuffix()`

#### `tests/Unit/RateLimitServiceTest.php`
- `testAllowsRequestsBelowThreshold()`
- `testBlocksAfterThresholdExceeded()`
- `testResetsWindowAfterExpiry()`
- `testDifferentActionsTrackedSeparately()`

### Feature Tests

#### `tests/Feature/AuthApiTest.php`
- `testTokenEndpointReturnsJwt()`
- `testTokenEndpointRejects401OnBadPassword()`
- `testTokenEndpointRateLimited()`

#### `tests/Feature/ItemsApiTest.php`
- `testListItemsReturns200()`
- `testGetItemReturns200()`
- `testGetNonExistentItemReturns404()`
- `testListItemsSearchFilter()`
- `testListItemsCategoryFilter()`

#### `tests/Feature/BidsApiTest.php`
- `testPlaceBidRequiresAuth()`
- `testPlaceBidReturnsSuccess()`
- `testPlaceBidValidationErrors()`

#### `tests/Feature/EventsApiTest.php`
- `testListEventsReturns200()`
- `testCreateEventRequiresAdmin()`
- `testPublishEventTransitionsStatus()`

#### `tests/Feature/AdminApiTest.php`
- `testListUsersRequiresAdmin()`
- `testListPaymentsRequiresAdmin()`

---

## DOCUMENTATION STRUCTURE

All docs are Markdown. Agents must write complete, accurate content — not placeholders.

### `docs/api/v1.md` — REST API Reference
Complete reference for every endpoint:
- Method + URL
- Auth required (yes/no + role)
- Request params/body (table)
- Response schema (JSON example)
- Error codes
- Example curl command

### `docs/developer/setup.md` — Developer Setup
- Prerequisites (PHP 8.2, Composer, Node, Galvani binary)
- Clone + install steps
- `.env` configuration
- Running the server: `./galvani -t ./auction`
- Running DB init: `./galvani -t ./auction auction/db-init.php`
- Running CSS build: `npm run build:css`
- Running tests: `vendor/bin/phpunit`
- Default admin login: admin@example.com / password

### `docs/developer/architecture.md` — Architecture Guide
- Repository → Service → Controller → View pattern
- Why no transactions
- Galvani-specific patterns (singleton DB, socket path, emulated prepares)
- View partials system (reference to view-partials-spec.md)
- Base path and routing

### `docs/developer/database.md` — Database Reference
- Every table with column descriptions
- Relationships diagram (text-based)
- How to reset: `db-init.php`
- How to run schema-only (no seeds)

### `docs/developer/testing.md` — Testing Guide
- PHPUnit setup
- Running tests locally vs CI
- Writing unit tests (mock DB pattern)
- Writing feature tests (test DB setup)
- `.env.test` configuration

### `docs/developer/deployment.md` — Deployment Guide
- Shared LAMP hosting steps (IONOS)
- Apache `.htaccess` required
- `php.ini` values for production
- Environment variables via `config/env.php` (not `.env` on IONOS)
- Stripe live key setup
- SMTP configuration

### `docs/admin/getting-started.md`
- First login
- Setting up Stripe (with screenshot guide)
- Setting up SMTP
- Creating your first event

### `docs/admin/events.md`
Full guide: creating, publishing, opening, closing events. All three types (online/silent/live).

### `docs/admin/items.md`
Approving, rejecting, editing items. Understanding the pending queue.

### `docs/admin/payments.md`
Payment workflow, auto vs manual, sending requests, marking paid, Stripe dashboard link.

### `docs/admin/gift-aid.md`
What Gift Aid is, how declarations are collected, how to export HMRC CSV, retention requirements (7 years).

### `docs/admin/live-events.md`
Running the auctioneer panel. Floor bids. The projector view. WebSocket troubleshooting.

### `docs/wiki/Home.md`
Project overview, links to all other wiki pages.

### `docs/wiki/Architecture.md`
High-level architecture diagram + explanation.

### `docs/wiki/API.md`
Quick-start guide for API consumers. Links to full API reference.

---

## PHASE BREAKDOWN

### Phase 0: CI + Test Scaffold (TDD foundation — do this first)
**Task 0.1** — `composer.json`, `phpunit.xml`, `tests/bootstrap.php`, `.env.test`
**Task 0.2** — `.github/workflows/ci.yml`
**Task 0.3** — Write all Unit test files (failing tests only — no implementation yet)
**Task 0.4** — Write all Feature test files (failing tests only)
**Task 0.5** — Confirm `vendor/bin/phpunit` runs and all tests fail (expected)

### Phase 1: Foundation
**Task 1.1** — `database/schema.sql` (all tables), `database/seeds.sql`, `db-init.php`
**Task 1.2** — `config/database.php`, `config/app.php`, `config/stripe.php`, `.env`, `php.ini`
**Task 1.3** — `app/Models/Database.php` (copy + adapt socket path from booking)
**Task 1.4** — `app/Services/JWT.php` (copy from booking — identical)
**Task 1.5** — `app/Helpers/config.php`, `app/Helpers/helpers.php` (adapt from booking)
**Task 1.6** — `app/Helpers/auth.php` (adapt — add Bearer-in-body check, role checks for bidder/donor)
**Task 1.7** — `app/Helpers/validation.php`, `app/Helpers/view.php` (adapt from booking)
**Task 1.8** — `app/Controllers/Controller.php` (base controller)
**Task 1.9** — `index.php` entry point with routing, error handlers, basePath
**Task 1.10** — All route files (`app/Routes/*.php`) — register routes, no logic
**Task 1.11** — `app/Views/layouts/public.php`, `app/Views/layouts/admin.php`, `app/Views/layouts/projector.php`
**Task 1.12** — All partials: head, header-public, header-admin, mobile-menu, footer, toast, scripts-dark-mode, scripts-mobile-menu
**Task 1.13** — All atoms (16 files — see view-partials-spec.md for exact spec of each)
**Task 1.14** — Error views: `errors/403.php`, `errors/404.php`, `errors/500.php`

### Phase 2: Auth (login, register, email verification)
**Task 2.1** — `UserRepository` (all methods)
**Task 2.2** — `AuthService` (register, login, generateToken, generateSlug, generateVerificationToken)
**Task 2.3** — `AuthController` (loginPage, login, registerPage, register, logout, verifyEmail, resendVerification)
**Task 2.4** — Auth views: login.php, register.php, verify-email.php
**Task 2.5** — Run `AuthServiceTest` — all tests should pass

### Phase 3: Forgot/Reset Password
**Task 3.1** — `AuthService` additions: forgotPassword, validateResetToken, resetPassword
**Task 3.2** — `AuthController` additions: forgotPasswordPage, forgotPassword, resetPasswordPage, resetPassword
**Task 3.3** — Views: forgot-password.php, reset-password.php

### Phase 4: Rate Limiting
**Task 4.1** — `RateLimitRepository` (find, increment, upsert, cleanup)
**Task 4.2** — `RateLimitService` (check — full implementation as above)
**Task 4.3** — Wire rate limiting into: login, register, forgotPassword, resendVerification
**Task 4.4** — Run `RateLimitServiceTest` — all tests should pass

### Phase 5: Account Settings
**Task 5.1** — `AccountController` (settings, updateProfile, updatePassword)
**Task 5.2** — `app/Views/account/settings.php` (two sections: Profile, Password)
**Task 5.3** — Wire `/account` routes

### Phase 6: Categories + Events + Items (public browsing)
**Task 6.1** — `CategoryRepository`, `EventRepository`, `ItemRepository` (all methods)
**Task 6.2** — `EventService`, `ItemService` (all methods)
**Task 6.3** — `UploadService` (validates + saves images, 5MB limit, jpg/png/webp only)
**Task 6.4** — `HomeController` (index, terms, privacy)
**Task 6.5** — `EventController` (show)
**Task 6.6** — `ItemController` (show, submitPage, submit)
**Task 6.7** — Views: home/index.php, home/terms.php, home/privacy.php, events/show.php, items/show.php, items/submit.php
**Task 6.8** — CSS rebuild: `npm run build:css`

### Phase 7: Bidding
**Task 7.1** — `BidRepository` (all methods)
**Task 7.2** — `BidService` (place, placeBuyNow, placeFloorBid)
**Task 7.3** — `BidController` (place, placeAjax, myBids, currentBid, myBidStatus)
**Task 7.4** — Views: bids/my-bids.php
**Task 7.5** — Rate limit on bid submission (wire into BidController::place)
**Task 7.6** — Run `BidServiceTest` — all tests should pass
**Task 7.7** — `js/bidding.js` (startBidPolling, stopBidPolling, checkMyBidStatus, outbid detection)

### Phase 8: Gift Aid
**Task 8.1** — `GiftAidRepository`, `GiftAidService` (calculate, saveDeclaration, getExisting, exportCsv)
**Task 8.2** — Wire Gift Aid into bid placement flow (BidController::place)
**Task 8.3** — Run `GiftAidServiceTest` — all tests should pass

### Phase 9: Auction Timing + Status Transitions
**Task 9.1** — `AuctionService` (processEndedItems, endItem, processEventClose, effectiveEndTime)
**Task 9.2** — Wire `processEndedItems()` into admin page load (index.php, admin route group)
**Task 9.3** — Event status machine: publishEvent, openEvent, closeEvent in EventService
**Task 9.4** — Run `AuctionServiceTest` — all tests should pass

### Phase 10: Stripe Payments
**Task 10.1** — `SettingsRepository`, `SettingsService` (get, set, getStripeSecretKey, getStripePublishableKey, getWebhookSecret)
**Task 10.2** — `PaymentRepository` (all methods)
**Task 10.3** — `PaymentService` (requestPayment, handleWebhook, getClientSecret, markPaidManually)
**Task 10.4** — `PaymentController` (checkout, processCheckout, success, stripeWebhook)
**Task 10.5** — Views: payment/checkout.php, payment/success.php
**Task 10.6** — `composer require stripe/stripe-php`

### Phase 11: Admin Panel
**Task 11.1** — `AdminController` (all methods — dashboard, events CRUD, items management, users, payments, gift-aid, settings)
**Task 11.2** — Admin views: dashboard.php, events/index.php, events/show.php, items/index.php, users/index.php, payments/index.php, gift-aid/index.php, settings.php
**Task 11.3** — CSS rebuild after new admin Tailwind classes

### Phase 12: Notifications
**Task 12.1** — `NotificationService` using PHPMailer (SMTP from settings)
   - sendPaymentRequest, sendPaymentConfirmation, sendOutbidNotification, sendItemRejected, sendVerificationEmail, sendPasswordResetEmail
**Task 12.2** — `composer require phpmailer/phpmailer`
**Task 12.3** — Wire notifications into: registration, forgot-password, bid outbid, payment request, payment confirmation, item rejected

### Phase 13: REST API v1
**Task 13.1** — `ApiController` (all methods — auth, items, events, bids, users, payments, gift-aid, categories, me)
**Task 13.2** — Wire `/api/v1/` routes in `app/Routes/api.php`
**Task 13.3** — Rate limit on `api_token` route
**Task 13.4** — Run `AuthApiTest`, `ItemsApiTest`, `BidsApiTest`, `EventsApiTest`, `AdminApiTest` — all should pass

### Phase 14: Live Auction Mode
**Task 14.1** — `WebSocketService` (broadcast — wraps Galvani WebSocket)
**Task 14.2** — `AuctioneerController` (panel, openLot, closeLot, floorBid, liveState, projector)
**Task 14.3** — Views: auctioneer/panel.php, auctioneer/projector.php
**Task 14.4** — `js/live-auction.js` (WebSocket client, auto-reconnect, event handlers)

### Phase 15: Documentation
**Task 15.1** — `docs/api/v1.md` (full REST API reference)
**Task 15.2** — `docs/developer/` (all 5 files)
**Task 15.3** — `docs/admin/` (all 6 files)
**Task 15.4** — `docs/wiki/` (Home.md, Architecture.md, API.md)

### Phase 16: Polish
**Task 16.1** — CSRF audit: every `<form method="POST">` has `_csrf_token` hidden field. Stripe webhook route skips CSRF.
**Task 16.2** — Dark mode audit: every view has `dark:` variants for all bg/text/border classes
**Task 16.3** — Mobile QA: test every page at 375px, every popover, bid form, admin tables scroll
**Task 16.4** — Final CSS rebuild
**Task 16.5** — Full test run: `vendor/bin/phpunit` — all green
**Task 16.6** — Final acceptance criteria check (see below)

---

## `php.ini` (match shared server — 5MB upload limit)
```ini
upload_max_filesize = 5M
post_max_size = 8M
memory_limit = 256M
max_execution_time = 30
```

---

## ACCEPTANCE CRITERIA

- [ ] Public can browse items and events without logging in
- [ ] Register → verification email → verify → bid flow works end to end
- [ ] Forgot password → email → reset → auto-login works
- [ ] Rate limiting blocks after threshold on login, register, bid, api_token, password_reset
- [ ] Account settings: update profile and password
- [ ] Event status machine: draft → published → active → ended → closed
- [ ] Bidder can place bids, see Gift Aid calculation, view My Bids dashboard
- [ ] Bidder toast when outbid on active item (polling)
- [ ] Donor submits item → admin sees in pending queue → approves/rejects
- [ ] Auto-payment: when ON, payment request sent automatically on auction end
- [ ] Winner pays via Stripe Elements → webhook marks sold → confirmation email
- [ ] Gift Aid calculated per bid, admin exports HMRC CSV
- [ ] Live mode: open lot → floor bids → close lot → projector updates in real time
- [ ] REST API: all endpoints return correct JSON, auth enforced, rate limited
- [ ] All dialogs use Popover API — zero `alert()`/`confirm()`/`prompt()`
- [ ] All icons are inline SVG — zero icon libraries
- [ ] No numeric IDs in any URL
- [ ] Full dark mode throughout
- [ ] CSRF on all POST forms
- [ ] Mobile-first responsive on all pages
- [ ] 404, 403, 500 error pages render correctly
- [ ] All PHPUnit tests pass (unit + feature)
- [ ] GitHub Actions CI passes on push
- [ ] All docs written and complete
- [ ] `npm run build:css` produces correct `css/output.css`

---

## REFERENCE DOCUMENTS

- View partials + atoms spec: `docs/plans/view-partials-spec.md`
- Approved mockups: `docs/mockups/*.html`
- Galvani rules: `CLAUDE.md`
- Design tokens: `css/tailwind.css`
- Booking app patterns (copy/adapt): `/Users/waseem/Sites/www/booking/`
