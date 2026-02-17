# WFCS Auction

Charity auction platform for [The Well Foundation (WFCS)](https://wellfoundation.org.uk) — a Muslim charity based in Scotland.

Supports both online auctions and live in-person events, with Stripe Checkout payments and HMRC Gift Aid reporting.

---

## Tech stack

- **Backend:** PHP 8.2+ — MVC with Repository pattern
- **Dev runtime:** [Galvani](https://galvani.dev) — async multi-threaded PHP + embedded MariaDB
- **Production:** LAMP (Apache + MySQL + PHP)
- **Frontend:** TailwindCSS v4 + Vanilla JS
- **Payments:** Stripe Checkout
- **Email:** PHPMailer (SMTP)
- **Tests:** PHPUnit 11

---

## Quick start

```bash
# 1. Install dependencies
composer install && npm install

# 2. Configure environment
cp .env.example .env  # then edit with your values

# 3. Build CSS
npm run build:css

# 4. Initialise the database (from galvani-workspace root, one level up)
./galvani auction/db-init.php

# 5. Start the dev server
./galvani -t ./auction
```

Open `http://localhost:8080/` in your browser.

---

## Documentation

| Document | Description |
|----------|-------------|
| [Developer Guide](docs/developer/README.md) | Full setup, architecture, adding features, deployment |
| [Admin Guide](docs/admin/README.md) | Guide for charity staff managing the platform |
| [REST API Reference](docs/api/README.md) | API endpoints, authentication, rate limiting |
| [Architecture Overview](docs/wiki/Architecture.md) | Technical design overview |
| [Getting Started](docs/wiki/Getting-Started.md) | Condensed setup guide |

---

## Features

- Public auction browsing with search and category filtering
- Online bidding with real-time updates (AJAX polling)
- Buy Now option on eligible items
- Donor submission portal for item donations
- Stripe Checkout for winner payments
- Gift Aid tracking with HMRC CSV export
- Live event auctioneer panel + projector display
- Admin panel: events, items, users, payments, Gift Aid, settings
- REST API (`/api/v1/`) for external integrations
- JWT authentication (cookie for web, query param for API)
- DB-based rate limiting (compatible with multi-threaded runtime)
- Email verification + password reset

---

## Running tests

```bash
vendor/bin/phpunit tests/Unit/ --no-coverage
vendor/bin/phpunit tests/Feature/ --no-coverage
```

CI runs on every push via GitHub Actions (`.github/workflows/ci.yml`).

---

## Charity

**The Well Foundation (WFCS)**
Registered charity in Scotland — No: SC040105
211B Main Street, Bellshill, ML4 1AJ

Contact: info@wellfoundation.org.uk

Built by [Waseem Sadiq](https://waseemsadiq.com).
