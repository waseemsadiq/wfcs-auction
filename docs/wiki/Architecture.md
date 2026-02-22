# Architecture

Technical overview of the WFCS Auction codebase.

---

## Overview

WFCS Auction is a PHP MVC application following the **Repository pattern**. It has no framework dependency — the core router, database wrapper, and JWT utilities are lightweight classes in the `core/` directory.

```
HTTP Request
    │
    ▼
index.php                 ← single entry point
    │
    ├── Load .env
    ├── Load config/
    ├── Load helpers
    ├── Instantiate controllers
    │
    ▼
Core\Router               ← matches URI pattern to a controller method
    │
    ▼
Controller                ← validates input, checks auth, calls services
    │
    ▼
Service                   ← business logic, no SQL
    │
    ▼
Repository                ← all SQL lives here
    │
    ▼
Core\Database             ← PDO singleton, READ COMMITTED isolation
    │
    ▼
MariaDB (via socket)
```

---

## Layer responsibilities

### Controllers (`app/Controllers/`)

- Handle HTTP input (`$_GET`, `$_POST`, route params)
- Validate user input
- Check authentication and authorisation
- Call one or more services
- Return a rendered view (HTML) or JSON response
- No SQL, no direct DB calls

### Services (`app/Services/`)

- Contain all business logic
- Orchestrate calls to one or more repositories
- Throw `\RuntimeException` for business rule violations
- Return plain arrays or scalars
- No SQL, no HTTP knowledge

### Repositories (`app/Repositories/`)

- All SQL is written here — nowhere else
- Accept and return plain PHP arrays
- Never called directly from controllers
- Use `Core\Database::getInstance()` always

### Views (`app/Views/`)

Three-tier hierarchy:

```
layouts/        ← base page shell (HTML, head, scripts)
partials/       ← reusable sections (header, footer, nav, sidebar)
atoms/          ← small UI components (badges, buttons, form fields)
[page]/         ← page-specific content views
```

Views are PHP files. The `view()` helper in `functions.php` buffers the content view and injects it into the layout.

---

## Database schema

Nine tables:

| Table | Purpose |
|-------|---------|
| `users` | Registered bidders, donors, and admins |
| `categories` | Item categories (e.g. Art, Jewellery, Travel) |
| `events` | Auction events with status lifecycle |
| `items` | Auction items linked to an event and category |
| `bids` | All bids placed by users on items |
| `payments` | Stripe payment records for auction winners |
| `password_reset_tokens` | Tokens for the forgot-password flow |
| `rate_limits` | DB-based rate limiting (works in multi-threaded env) |
| `settings` | Key-value store for admin-configurable app settings |

---

## Authentication

- JWT-based. No PHP sessions.
- **Web (browser):** JWT stored in an `HttpOnly` cookie (`auth_token`). Set on login, cleared on logout.
- **API:** JWT passed as `?token=` query parameter or `token` POST field.
- JWT payload: `{ id, email, name, role, slug, verified, exp }`
- Web session tokens expire after **120 minutes of inactivity** (2-hour sliding window: any authenticated page request resets the clock). API tokens expire in 1 year.
- The `getAuthUser()` helper decodes the JWT from the cookie or request parameter and returns the user array, or `null` if unauthenticated.

---

## Event (auction) status machine

```
draft ──publish──► published ──open──► active ──end──► ended ──close──► closed
```

- Status transitions are triggered by admin action (publish, open, end) or automatically when the `ends_at` timestamp passes.
- Automatic transitions run on every non-API web request (lightweight check in `index.php` — no cron required).

---

## Item status machine

```
pending ──approve──► active ──end──► ended ──pay──► sold
              │
              └──reject──► (deleted / not shown)
```

---

## CSRF protection

- A CSRF token is stored in an `HttpOnly` cookie (generated if absent).
- All HTML form POSTs must include `_csrf_token` as a hidden field or query parameter.
- API routes and the Stripe webhook are exempt.

---

## Rate limiting

Rate limits are stored in the `rate_limits` database table. This is intentional — Galvani is multi-threaded, so in-memory counters (like APCu) would only apply per-thread. DB-based limits are consistent across all threads.

Six actions are rate-limited: `login`, `register`, `bid`, `api_token`, `password_reset`, `resend_verification`.

---

## Galvani runtime notes

Galvani is the local development runtime. It is an async, multi-threaded PHP server with an embedded MariaDB. It is **not** used in production.

Key implications:

1. No PHP sessions (they would not persist across threads). JWT is used instead.
2. No explicit transactions (`beginTransaction` / `commit`). Autocommit only.
3. `PDO::ATTR_EMULATE_PREPARES => true` — native prepares corrupt date values.
4. `LIMIT`/`OFFSET` must be string-interpolated, not bound parameters.
5. `READ COMMITTED` transaction isolation — ensures writes in one thread are visible to others.
6. All controllers are instantiated once at startup in `index.php`. Auth checks must be in controller methods, not constructors.
7. After editing any PHP class, Galvani must be restarted. Views are re-read per request and do not require a restart.

---

## CSS build

TailwindCSS v4. Source: `css/tailwind.css`. Output: `css/output.css` (committed).

Custom theme:
- Primary colour: `#45a2da`
- Font: Outfit variable (`css/fonts/Outfit-variable.woff2`)
- Dark mode: `.dark` class on `<html>`, toggled with View Transitions API animation

---

## REST API

Base path: `/api/v1/`

All responses use an envelope:
```json
{ "data": ..., "meta": { "total": ..., "page": ..., "pages": ... } }
```

Authentication: JWT via `?token=` or POST `token` field. **Not** via `Authorization` header (Galvani drops custom headers).

See [API Reference](../api/README.md) for full endpoint documentation.

---

## Payments

Stripe Checkout is used for payment collection:

1. Admin ends the auction — winners are identified
2. Winner receives a payment email with a link to `/payment/:item-slug`
3. App creates a Stripe Checkout session and redirects the winner to Stripe
4. Stripe processes payment and sends a `checkout.session.completed` webhook
5. Webhook is received at `POST /webhook/stripe?webhook_secret=TOKEN`
6. App marks the payment `completed` and item `sold`

The webhook uses a query-parameter token instead of the Stripe signature header because headers are stripped by the runtime.

---

## Directory structure (top level)

```
auction/
├── app/            # MVC: Controllers, Services, Repositories, Views, Helpers
├── config/         # app.php, database.php, stripe.php
├── core/           # Router, Database, JWT, Controller base
├── css/            # Tailwind source + compiled output
├── database/       # schema.sql + seeds.sql
├── docs/           # Documentation
├── images/         # Static assets (logos)
├── tests/          # PHPUnit Unit + Feature suites
├── uploads/        # User-uploaded images (gitignored in prod)
├── composer.json
├── db-init.php     # DB reset script
├── index.php       # Single entry point
└── package.json
```
