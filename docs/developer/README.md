# Developer Guide

Complete onboarding guide for developers working on the WFCS Auction platform.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Environment Variables](#environment-variables)
- [Architecture Overview](#architecture-overview)
- [Directory Structure](#directory-structure)
- [Request Lifecycle](#request-lifecycle)
- [Adding a New Feature](#adding-a-new-feature)
- [Galvani-Specific Rules](#galvani-specific-rules)
- [CSS Build](#css-build)
- [Running Tests](#running-tests)
- [Deployment to LAMP Hosting](#deployment-to-lamp-hosting)

---

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2+ | Must be on PATH |
| Composer | 2.x | For PHP dependencies |
| Node.js | 18+ | For CSS build only |
| npm | 9+ | Bundled with Node |
| Galvani | latest | Local dev runtime only — not deployed |

**Galvani** is a multi-threaded PHP runtime with an embedded MariaDB. It is used for local development only. Production runs on a standard LAMP stack.

---

## Setup

The workspace has the following structure on disk. The `galvani` binary and `data/` directory live at the git-root level (one level above the `auction/` app folder) and are never committed.

```
galvani-workspace/        # local git root (not a GitHub repo)
├── galvani               # binary (gitignored)
├── data/                 # embedded MariaDB data (gitignored)
├── .env                  # root env: GALVANI_MYSQL=1
└── auction/              # THIS repo (its own GitHub repo)
    ├── .env              # app secrets
    ├── index.php
    └── ...
```

### 1. Clone the repo

```bash
# Place it inside your galvani-workspace folder
git clone git@github.com:yourorg/auction.git auction
```

### 2. Install PHP dependencies

```bash
cd auction
composer install
```

### 3. Install Node dependencies (CSS build)

```bash
npm install
```

### 4. Configure environment

```bash
cp .env.example .env
# Then edit .env with your values — see Environment Variables section below
```

### 5. Build CSS

```bash
npm run build:css
```

### 6. Initialise the database

```bash
# Run from the galvani-workspace root (one level up from auction/)
./galvani auction/db-init.php
```

### 7. Start the server

```bash
# Option A: app served at http://localhost:8080/auction/
./galvani

# Option B (recommended): app served at http://localhost:8080/
./galvani -t ./auction
```

### 8. Verify

Open `http://localhost:8080/` in your browser. You should see the auction home page.

---

## Environment Variables

All variables go in `auction/.env`. Relative paths resolve from the **galvani-workspace root** (one level up from the app folder) because that is Galvani's working directory.

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_ENV` | Yes | Environment name: `development` or `production` |
| `APP_DEBUG` | No | Set to `1` to enable debug output (development only) |
| `APP_URL` | Yes | Full base URL, e.g. `http://localhost:8080` |
| `APP_KEY` | Yes | Random string for misc encryption |
| `JWT_SECRET` | Yes | Secret key for signing JWT tokens — keep this private |
| `DB_SOCKET` | Yes | Path to MariaDB socket. See auto-detection note below |
| `DB_NAME` | Yes | Database name, e.g. `auction` |
| `DB_USER` | Yes | Database user |
| `DB_PASS` | Yes | Database password |
| `STRIPE_PUBLIC_KEY` | Yes | Stripe publishable key (`pk_test_...` or `pk_live_...`) |
| `STRIPE_SECRET_KEY` | Yes | Stripe secret key (`sk_test_...` or `sk_live_...`) |
| `STRIPE_WEBHOOK_SECRET` | Yes | Token used to verify Stripe webhooks (your own random string) |
| `MAIL_HOST` | Yes | SMTP host, e.g. `smtp.mailgun.org` |
| `MAIL_PORT` | Yes | SMTP port, e.g. `587` |
| `MAIL_USERNAME` | Yes | SMTP username |
| `MAIL_PASSWORD` | Yes | SMTP password |
| `MAIL_FROM_ADDRESS` | Yes | Sender email, e.g. `noreply@wellfoundation.org.uk` |
| `MAIL_FROM_NAME` | Yes | Sender display name, e.g. `WFCS Auction` |
| `GALVANI_MYSQL` | Dev only | Set to `1` in `.env` when using `-t` flag |

### Socket path auto-detection

The database config at `config/database.php` detects whether it is running as a web request (Galvani sets cwd to the app subfolder) or a CLI script (cwd is the git root):

```php
'socket' => basename(getcwd()) === 'auction'
    ? dirname(getcwd()) . '/data/mysql.sock'   // web request
    : getcwd() . '/data/mysql.sock',            // CLI / db-init
```

Never hardcode the socket path as an absolute path — it will break on other machines.

### Example `.env`

```dotenv
APP_ENV=development
APP_DEBUG=1
APP_URL=http://localhost:8080
APP_KEY=change-me-to-a-long-random-string
JWT_SECRET=another-long-random-string

DB_NAME=auction
DB_USER=root
DB_PASS=

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=my-secure-random-webhook-token

MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=yourmailtrapuser
MAIL_PASSWORD=yourmailtrappassword
MAIL_FROM_ADDRESS=noreply@wellfoundation.org.uk
MAIL_FROM_NAME="WFCS Auction"

GALVANI_MYSQL=1
```

---

## Architecture Overview

The app follows a strict **MVC + Repository pattern**:

```
HTTP Request
    │
    ▼
index.php           ← single entry point; loads .env, config, helpers, router
    │
    ▼
Core\Router         ← matches URI to a [Controller, method] tuple
    │
    ▼
Controller          ← validates input, calls services, returns a view or JSON
    │
    ▼
Service             ← business logic; calls repositories; never touches DB directly
    │
    ▼
Repository          ← ALL SQL lives here; returns plain arrays
    │
    ▼
Core\Database       ← singleton PDO wrapper
```

### Layer responsibilities

| Layer | Responsibility | May call |
|-------|---------------|----------|
| Controller | HTTP in/out, auth checks, input validation | Services, view helpers |
| Service | Business logic, orchestration | Repositories, other services |
| Repository | SQL queries only | Database singleton |
| View | HTML output | Helper functions, atoms |

---

## Directory Structure

```
auction/
├── app/
│   ├── Controllers/        # HTTP handlers
│   │   ├── AccountController.php
│   │   ├── AdminController.php
│   │   ├── ApiController.php
│   │   ├── AuctioneerController.php
│   │   ├── AuthController.php
│   │   ├── BidController.php
│   │   ├── EventController.php
│   │   ├── HomeController.php
│   │   ├── ItemController.php
│   │   └── PaymentController.php
│   │
│   ├── Helpers/
│   │   ├── functions.php   # Global helper functions (view(), redirect(), auth(), etc.)
│   │   └── validation.php  # Validation helpers
│   │
│   ├── Repositories/       # All SQL lives here
│   │   ├── BidRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── EventRepository.php
│   │   ├── GiftAidRepository.php
│   │   ├── ItemRepository.php
│   │   ├── PasswordResetRepository.php
│   │   ├── PaymentRepository.php
│   │   ├── RateLimitRepository.php
│   │   ├── SettingsRepository.php
│   │   └── UserRepository.php
│   │
│   ├── Services/           # Business logic
│   │   ├── AccountService.php
│   │   ├── ApiTokenService.php
│   │   ├── AuctionService.php
│   │   ├── AuthService.php
│   │   ├── BidService.php
│   │   ├── EventService.php
│   │   ├── GiftAidService.php
│   │   ├── ItemService.php
│   │   ├── NotificationService.php
│   │   ├── PasswordResetService.php
│   │   ├── PaymentService.php
│   │   ├── RateLimitService.php
│   │   ├── StripeService.php
│   │   └── UploadService.php
│   │
│   └── Views/              # PHP view templates
│       ├── layouts/        # Base page layouts (main.php, admin.php)
│       ├── partials/       # Reusable page sections (header, footer, nav)
│       ├── atoms/          # Small reusable UI components
│       ├── home/           # Home page views
│       ├── auth/           # Login, register, reset password views
│       ├── account/        # Account settings views
│       ├── events/         # Auction listing views
│       ├── items/          # Item detail views
│       ├── bids/           # Bid history views
│       ├── payment/        # Payment checkout views
│       ├── admin/          # Admin panel views
│       ├── auctioneer/     # Auctioneer control panel
│       ├── projector/      # Projector display view
│       └── errors/         # 404, 500 error views
│
├── config/
│   ├── app.php             # App name, URL, JWT secret, timezone
│   ├── database.php        # DB connection (socket, name, user, pass)
│   └── stripe.php          # Stripe keys and webhook secret
│
├── core/                   # Framework core (not namespaced under App\)
│   ├── Controller.php      # Base controller with view(), json(), redirect()
│   ├── Database.php        # PDO singleton wrapper
│   ├── JWT.php             # JWT encode/decode
│   └── Router.php          # Simple HTTP router
│
├── css/
│   ├── tailwind.css        # Tailwind v4 source
│   └── output.css          # Compiled CSS (committed to repo)
│
├── database/
│   ├── schema.sql          # Table definitions (source of truth)
│   └── seeds.sql           # Demo data for development
│
├── docs/                   # Documentation (this folder)
├── images/                 # Static images (logo, etc.)
├── tests/                  # PHPUnit test suite
│   ├── Unit/               # Unit tests (no DB, no HTTP)
│   └── Feature/            # Feature tests (full request simulation)
│
├── uploads/                # User-uploaded item images (gitignored in prod)
├── composer.json
├── db-init.php             # DB reset script
├── index.php               # Single entry point
├── package.json
└── phpunit.xml
```

---

## Request Lifecycle

1. **`index.php`** — Galvani routes all requests to `index.php`
2. **Load `.env`** — parse key=value lines into `$_ENV`
3. **Config** — load `config/app.php`, set timezone
4. **Autoloader** — Composer PSR-4: `App\` maps to `app/`, `Core\` maps to `core/`
5. **Helpers** — `functions.php` and `validation.php` loaded globally
6. **CORS headers** — set on `/api/*` routes before any output
7. **CSRF validation** — all HTML form POSTs validated against cookie token
8. **Controllers instantiated** — all controllers created once at the top of `index.php` (not inside route closures)
9. **Router dispatch** — `Core\Router` matches the request method + URI and calls the appropriate controller method
10. **Controller** — validates input, checks auth, calls services
11. **Service** — executes business logic via repositories
12. **View** — PHP template buffered with `ob_start()`, injected into layout

### Auth flow

- **Web (browser):** JWT stored in an `HttpOnly` cookie called `auth_token`. Set on login. Validated on every request via `getAuthUser()` helper.
- **API:** JWT passed as `?token=` query param or `token` POST field. Same JWT format, longer expiry.
- **JWT payload:** `id`, `email`, `name`, `role`, `slug`, `verified`, `exp`

### View system

```php
// In a controller:
return $this->view('items/show', [
    'item'     => $item,
    'basePath' => $basePath,
]);

// view() helper in functions.php:
// 1. Extracts variables into scope
// 2. ob_start()
// 3. require the view file (e.g. app/Views/items/show.php)
// 4. Captures output → $content
// 5. Requires the layout (app/Views/layouts/main.php)
// 6. Layout echoes $content in the appropriate slot
```

**Atoms** are small, reusable UI components. They are loaded via the `atom()` helper:

```php
<?php atom('badge', ['text' => 'Active', 'colour' => 'green']); ?>
```

---

## Adding a New Feature

This walkthrough adds a hypothetical "Testimonials" entity.

### Step 1: Schema

Add your table to `database/schema.sql`:

```sql
CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  author VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Reset the DB: `./galvani auction/db-init.php`

### Step 2: Repository

Create `app/Repositories/TestimonialRepository.php`:

```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class TestimonialRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->pdo();
    }

    public function all(int $limit = 20, int $offset = 0): array
    {
        return Database::getInstance()->query(
            'SELECT * FROM testimonials WHERE published = 1
             ORDER BY created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT * FROM testimonials WHERE slug = ?',
            [$slug]
        ) ?: null;
    }

    public function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO testimonials (slug, author, body, published) VALUES (?, ?, ?, ?)',
            [$data['slug'], $data['author'], $data['body'], $data['published'] ? 1 : 0]
        );
        return (int)$db->lastInsertId();
    }
}
```

### Step 3: Service

Create `app/Services/TestimonialService.php`:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TestimonialRepository;

class TestimonialService
{
    private TestimonialRepository $repo;

    public function __construct(?TestimonialRepository $repo = null)
    {
        $this->repo = $repo ?? new TestimonialRepository();
    }

    public function getPublished(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->repo->all($perPage, $offset);
    }
}
```

### Step 4: Controller

Create `app/Controllers/TestimonialController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\TestimonialService;

class TestimonialController extends Controller
{
    // No auth checks in __construct — Galvani rule 9
    public function index(): void
    {
        $service      = new TestimonialService();
        $testimonials = $service->getPublished();

        $this->view('testimonials/index', compact('testimonials'));
    }
}
```

### Step 5: View

Create `app/Views/testimonials/index.php`:

```php
<?php
// $testimonials is available from the controller
foreach ($testimonials as $t): ?>
  <div class="bg-white rounded-lg p-6">
    <p class="text-gray-700"><?= htmlspecialchars($t['body']) ?></p>
    <p class="mt-2 font-semibold"><?= htmlspecialchars($t['author']) ?></p>
  </div>
<?php endforeach; ?>
```

### Step 6: Register routes in `index.php`

```php
// Near the top of index.php with other controller instantiations:
$testimonialController = new \App\Controllers\TestimonialController();

// In the router section:
$router->get('/testimonials', [$testimonialController, 'index']);
```

### Step 7: Tests

Add unit tests in `tests/Unit/TestimonialServiceTest.php` (mock the repository), and feature tests in `tests/Feature/TestimonialsTest.php` (test the full HTTP flow).

After editing PHP classes, **restart Galvani** — controllers and services are cached between requests.

---

## Galvani-Specific Rules

These rules are critical. Violating them causes silent data loss, deadlocks, or broken routes.

### 1. No explicit transactions

```php
// WRONG — committed data is invisible across threads
$db->beginTransaction();
$db->execute(...);
$db->commit();

// CORRECT — autocommit only
$db->execute(...);
```

### 2. Use the DB singleton

```php
// WRONG — exhausts the connection pool
$db = new Database();

// CORRECT — always
$db = Database::getInstance();
```

### 3. Emulated prepares

The PDO connection must have `PDO::ATTR_EMULATE_PREPARES => true`. Native prepares corrupt `DATE`/`TIME` values. This is set in `config/database.php` — do not change it.

### 4. No PHP booleans in SQL

```php
// WRONG
$db->execute('UPDATE items SET active = ?', [true]);

// CORRECT
$db->execute('UPDATE items SET active = ?', [1]);
```

### 5. LIMIT/OFFSET must be interpolated

```php
// WRONG — emulated prepares quote the placeholder → "LIMIT '20'" → syntax error
$db->query('SELECT * FROM items LIMIT ?', [20]);

// CORRECT
$db->query('SELECT * FROM items LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset);
```

### 6. READ COMMITTED isolation

`SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED` is applied in the DB constructor. This ensures that writes committed in one thread are visible to other threads. Do not change this.

### 7. Restart after PHP class changes

Controllers, services, and repositories are cached after first load. **You must restart Galvani** after editing any PHP class. Views are re-read on every request, so view changes are live immediately.

```bash
# Ctrl+C to stop, then:
./galvani -t ./auction
```

### 8. Minimum 4 threads

Do not use `--threads 1`. Galvani needs at least 4 threads for normal operation. The default is fine.

### 9. No auth checks in controller constructors

The router instantiates all controllers at registration time. Any `exit()` or `die()` inside a constructor will kill all routes.

```php
// WRONG
public function __construct()
{
    if (!getAuthUser()) { exit; }  // kills all routes!
}

// CORRECT — check auth inside each method
public function sensitiveAction(): void
{
    if (!getAuthUser()) {
        $this->redirect('/login');
    }
    // ...
}
```

### 10. Multi-step writes in one request

All related DB writes must happen in a single request handler. You cannot split a multi-step write across multiple requests and rely on the intermediate state being visible.

### 11. DELETE bodies are stripped

Do not put data in the body of `DELETE` requests. Proxies strip it. Use query string params and read from `$_GET`.

### 12. CSRF via query params

For multipart forms and DELETE routes, pass `_csrf_token` as a query parameter rather than in the request body. Galvani may drop it otherwise.

### 13. Socket path auto-detection

Use the pattern in `config/database.php`. Never use absolute paths or `dirname(__DIR__)`. See the Environment Variables section for details.

---

## CSS Build

The app uses **Tailwind CSS v4** with a custom theme.

### Commands

```bash
# Build once
npm run build:css

# Watch for changes (development)
npm run watch:css
```

### Source files

| File | Purpose |
|------|---------|
| `css/tailwind.css` | Tailwind source — custom theme vars, fonts, component classes |
| `css/output.css` | Compiled CSS — committed to repo, served directly |

### Theme tokens

```css
--color-primary: #45a2da;
--color-primary-hover: #3b8ec7;
```

### Dark mode

Dark mode uses the `.dark` class on `<html>` with a View Transitions API circle animation. Toggle is in the header of every page. The CSS uses:

```css
@custom-variant dark (&:where(.dark, .dark *));
```

### Font

Outfit variable font: `css/fonts/Outfit-variable.woff2`. Loaded via `@font-face` in `tailwind.css`.

---

## Running Tests

The test suite uses PHPUnit 11 with two suites: Unit and Feature.

```bash
# Run unit tests only (no DB, no HTTP)
vendor/bin/phpunit tests/Unit/ --no-coverage

# Run feature tests only (requires DB connection)
vendor/bin/phpunit tests/Feature/ --no-coverage

# Run all tests
vendor/bin/phpunit --no-coverage
```

### Test structure

```
tests/
├── bootstrap.php       # Loads .env.test, autoloader, helper stubs
├── Unit/               # Pure unit tests — no DB, repositories mocked
│   ├── BidServiceTest.php
│   ├── RateLimitServiceTest.php
│   ├── AuthServiceTest.php
│   ├── GiftAidServiceTest.php
│   └── UploadServiceTest.php
└── Feature/            # Integration tests — real DB, real HTTP flow
    ├── AuthFlowTest.php
    ├── BiddingFlowTest.php
    ├── PaymentFlowTest.php
    ├── AdminPanelTest.php
    └── ApiEndpointsTest.php
```

### Test environment

Tests use `.env.test` for configuration. The CI pipeline (`.github/workflows/ci.yml`) spins up a MySQL service container.

---

## Deployment to LAMP Hosting

### 1. Push to your hosting

```bash
git push origin main
# Or deploy via FTP/SFTP/cPanel Git if your host does not support SSH
```

### 2. Install Composer dependencies on the server

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure `.env`

Create `.env` in the app root on the server. Set `APP_ENV=production`, `APP_DEBUG=0`, and fill in production DB credentials and Stripe live keys.

### 4. Import the database schema

```bash
mysql -u youruser -p yourdatabase < database/schema.sql
```

Run seeds only if you want demo data (not recommended for production).

### 5. Apache virtual host

Point the document root to the `auction/` folder. The app uses a single `index.php` entry point, so you need mod_rewrite. A `.htaccess` file with the following is sufficient:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

Or in your vhost config:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/auction
    <Directory /var/www/html/auction>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. PHP configuration

- PHP 8.2+ required
- `upload_max_filesize` and `post_max_size` should be at least `10M` for item image uploads
- `date.timezone = Europe/London`

### 7. File permissions

```bash
chmod -R 755 /var/www/html/auction
chmod -R 775 /var/www/html/auction/uploads
chown -R www-data:www-data /var/www/html/auction
```

### 8. Stripe webhook

After going live:

1. In the Stripe Dashboard, create a webhook endpoint pointing to:
   `https://yourdomain.com/webhook/stripe?webhook_secret=YOUR_TOKEN`
2. Select event: `checkout.session.completed`
3. Generate a secure random token (`openssl rand -hex 32`) and use it as `YOUR_TOKEN`
4. Set the same token in **Admin → Settings → Stripe → Webhook URL Token**
5. Set `STRIPE_WEBHOOK_SECRET=YOUR_TOKEN` in your `.env`

### 9. SSL

All production deployments must use HTTPS. Stripe requires it for webhooks and the checkout redirect.

### 10. No cron required

The app auto-processes expired auctions on every non-API web request (a lightweight check in `index.php`). No cron job is needed for status transitions.
