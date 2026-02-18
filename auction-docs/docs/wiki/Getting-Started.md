# Getting Started

This guide gets you up and running with WFCS Auction in about 10 minutes. For the full developer reference, see [Developer Guide](../developer/README.md).

---

## What you need

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- Galvani binary (download separately — local dev only)
- Git

---

## Workspace layout

Galvani requires a specific folder structure. The binary and embedded database sit one level above the app:

```
galvani-workspace/        # your local git root (NOT committed to GitHub)
├── galvani               # Galvani binary (gitignored)
├── data/                 # embedded MariaDB data (gitignored)
├── .env                  # root env with GALVANI_MYSQL=1
└── auction/              # this repo
```

If you already have a `galvani-workspace/` folder, clone this repo into it:

```bash
cd galvani-workspace
git clone git@github.com:yourorg/auction.git auction
```

---

## Setup (5 steps)

### 1. Install dependencies

```bash
cd auction
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
```

Open `.env` and set at minimum:

```dotenv
APP_ENV=development
APP_DEBUG=1
APP_URL=http://localhost:8080
APP_KEY=any-long-random-string
JWT_SECRET=another-long-random-string
DB_NAME=auction
DB_USER=root
DB_PASS=
GALVANI_MYSQL=1
```

For payment testing, add your Stripe test keys:

```dotenv
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=any-random-token
```

### 3. Build CSS

```bash
npm run build:css
```

### 4. Initialise the database

```bash
# From galvani-workspace root (one level up from auction/)
./galvani auction/db-init.php
```

This creates all tables and loads demo data (seed items for development).

### 5. Start the server

```bash
# From galvani-workspace root
./galvani -t ./auction
```

Open `http://localhost:8080/` — you should see the auction home page.

---

## Default accounts (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Bidder | bidder@example.com | password |

> These are demo accounts from `database/seeds.sql`. Never use these credentials in production.

---

## Common tasks

### Reset the database

```bash
./galvani auction/db-init.php
```

### Rebuild CSS

```bash
cd auction && npm run build:css
```

### Watch CSS for changes

```bash
cd auction && npm run watch:css
```

### Run tests

```bash
cd auction
vendor/bin/phpunit tests/Unit/ --no-coverage
```

### Restart Galvani (after PHP class changes)

```
Ctrl+C  →  ./galvani -t ./auction
```

---

## Next steps

- Read the [Architecture overview](Architecture.md) to understand the codebase structure
- Check the [Developer Guide](../developer/README.md) for a walkthrough of adding new features
- Read [Galvani-specific rules](../developer/README.md#galvani-specific-rules) — these are critical and easy to get wrong
