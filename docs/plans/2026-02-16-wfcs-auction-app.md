# WFCS Auction App — Full Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a full-stack PHP MVC charity auction platform supporting online, silent (event), and live auction modes with Stripe payments, Gift Aid, and real-time bidding.

**Architecture:** PHP MVC on Galvani (no frameworks). Repository → Service → Controller → View. All SQL in Repositories. All business logic in Services. Views are plain PHP templates. Single `index.php` entry point with manual routing.

**Tech Stack:** PHP 8+, MySQL (Galvani embedded), TailwindCSS v4, Vanilla JS, Stripe API, WebSocket (Galvani), Outfit font, Heroicons/Feather inline SVG only.

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

---

## FILE & FOLDER STRUCTURE

```
auction/
├── app/
│   ├── Controllers/
│   │   ├── Controller.php              # Base controller
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── EventController.php
│   │   ├── ItemController.php
│   │   ├── BidController.php
│   │   ├── AdminController.php
│   │   ├── PaymentController.php
│   │   ├── GiftAidController.php
│   │   └── AuctioneerController.php   # Live mode only
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── EventService.php
│   │   ├── ItemService.php
│   │   ├── BidService.php
│   │   ├── AuctionService.php         # Status transitions + timing
│   │   ├── PaymentService.php         # Stripe
│   │   ├── GiftAidService.php
│   │   ├── UploadService.php
│   │   ├── NotificationService.php    # Email
│   │   ├── SettingsService.php
│   │   └── WebSocketService.php       # Live mode real-time
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   ├── ItemRepository.php
│   │   ├── BidRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── PaymentRepository.php
│   │   ├── GiftAidRepository.php
│   │   └── SettingsRepository.php
│   ├── Models/
│   │   └── Database.php               # PDO singleton (copy from booking)
│   ├── Helpers/
│   │   ├── auth.php                   # getAuthUser(), setAuthCookie(), etc.
│   │   ├── helpers.php                # e(), formatPrice(), setFlash(), etc.
│   │   ├── view.php                   # render(), renderPublic(), etc.
│   │   ├── validation.php             # validateCsrfToken(), getCsrfToken()
│   │   └── config.php                 # config() helper
│   ├── Routes/
│   │   ├── public.php
│   │   ├── bidder.php
│   │   ├── donor.php
│   │   ├── admin.php
│   │   └── auctioneer.php
│   └── Views/
│       ├── layouts/
│       │   ├── app.php                # Main layout (header + footer + dark mode)
│       │   └── projector.php          # Full-screen live display layout
│       ├── shared/
│       │   ├── header.php
│       │   ├── footer.php
│       │   ├── nav.php
│       │   ├── alert-dialog.php       # showAlert() popover
│       │   ├── confirm-dialog.php     # showConfirm() popover
│       │   └── toast.php              # showToast() toast
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── home/
│       │   └── index.php              # Public catalogue
│       ├── events/
│       │   └── show.php               # Event detail + item grid
│       ├── items/
│       │   ├── show.php               # Item detail + bid form
│       │   └── submit.php             # Donor submission form
│       ├── bids/
│       │   └── my-bids.php            # Bidder dashboard
│       ├── payment/
│       │   └── checkout.php           # Stripe checkout for winner
│       ├── admin/
│       │   ├── dashboard.php
│       │   ├── events/
│       │   │   ├── index.php
│       │   │   └── show.php           # Event detail + lots management
│       │   ├── items/
│       │   │   └── index.php          # Pending approval queue + all items
│       │   ├── users/
│       │   │   └── index.php
│       │   ├── payments/
│       │   │   └── index.php
│       │   ├── gift-aid/
│       │   │   └── index.php          # Declarations list + export
│       │   └── settings.php
│       └── auctioneer/
│           ├── panel.php              # Auctioneer control panel
│           └── projector.php          # Big screen display
├── config/
│   ├── app.php
│   ├── database.php
│   └── stripe.php
├── database/
│   ├── schema.sql
│   └── seeds.sql
├── docs/
│   └── plans/
│       └── 2026-02-16-wfcs-auction-app.md  ← this file
├── tests/
│   ├── BidServiceTest.php
│   ├── GiftAidServiceTest.php
│   └── AuctionServiceTest.php
├── css/
│   └── app.css                        # Tailwind v4 + @theme tokens
├── js/
│   ├── app.js                         # Dark mode toggle, mobile nav, flash
│   ├── bidding.js                     # Polling for silent/online mode
│   └── live-auction.js                # WebSocket client for live mode
├── uploads/                           # gitignored
├── composer.json
├── package.json
├── tailwind.config.js
├── index.php                          # Entry point
├── db-init.php
└── .env
```

---

## DATABASE SCHEMA

### Table: `settings`
| Column | Type | Notes |
|--------|------|-------|
| `key` | VARCHAR(100) PRIMARY KEY | e.g. `auto_payment_requests` |
| `value` | TEXT | `'1'` or `'0'` for booleans |

**Initial seeds:**
- `auto_payment_requests` = `'1'`
- `manual_payment_review` = `'0'`
- `outbid_email_notifications` = `'1'`
- `stripe_publishable_key` = (set via admin settings)
- `stripe_secret_key` = (set via admin settings, encrypted)
- `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from_name`, `smtp_from_email`

### Table: `users`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | generated from name |
| `name` | VARCHAR(255) NOT NULL | |
| `email` | VARCHAR(255) UNIQUE NOT NULL | |
| `password_hash` | VARCHAR(255) NOT NULL | `password_hash()` |
| `role` | ENUM('admin','donor','bidder') NOT NULL DEFAULT 'bidder' | |
| `postcode` | VARCHAR(20) | for Gift Aid |
| `address` | TEXT | for Gift Aid |
| `paddle_number` | VARCHAR(20) | for live events |
| `email_verified_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### Table: `categories`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | |
| `name` | VARCHAR(100) NOT NULL | |
| `sort_order` | INT DEFAULT 0 | |

**Seeds:** Watches, Memorabilia, Experience, Art, Jewellery, Sports, Food & Drink, Holiday, Other

### Table: `events`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | |
| `name` | VARCHAR(255) NOT NULL | |
| `description` | TEXT | |
| `venue` | VARCHAR(255) | |
| `auction_type` | ENUM('online','silent','live') NOT NULL DEFAULT 'online' | |
| `starts_at` | DATETIME NULL | NULL = items have individual timings |
| `ends_at` | DATETIME NULL | NULL = items have individual timings |
| `auto_payment` | TINYINT(1) DEFAULT 1 | auto-send payment requests |
| `status` | ENUM('draft','published','active','ended','closed') DEFAULT 'draft' | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### Table: `items`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `slug` | VARCHAR(100) UNIQUE NOT NULL | |
| `event_id` | INT NULL | FK → events.id, NULL = standalone/online item |
| `donor_id` | INT NOT NULL | FK → users.id |
| `category_id` | INT NOT NULL | FK → categories.id |
| `title` | VARCHAR(255) NOT NULL | |
| `description` | TEXT | |
| `image_path` | VARCHAR(500) | relative path e.g. `uploads/items/abc.jpg` |
| `starting_bid` | DECIMAL(10,2) NOT NULL | |
| `current_bid` | DECIMAL(10,2) DEFAULT 0.00 | updated on each accepted bid |
| `bid_count` | INT DEFAULT 0 | incremented on each accepted bid |
| `market_value` | DECIMAL(10,2) NOT NULL | for Gift Aid calculation |
| `reserve_price` | DECIMAL(10,2) NOT NULL | |
| `buy_now_price` | DECIMAL(10,2) NULL | optional |
| `min_increment` | DECIMAL(10,2) DEFAULT 1.00 | minimum bid increment |
| `auction_end` | DATETIME NULL | NULL = uses event ends_at |
| `lot_order` | INT NULL | for live mode sequential ordering |
| `status` | ENUM('pending','active','ended','awaiting_payment','paid','sold','cancelled') DEFAULT 'pending' | |
| `approved_by` | INT NULL | FK → users.id (admin who approved) |
| `approved_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Notes:**
- `current_bid` starts at 0. The actual floor is `starting_bid`. Display logic: if `current_bid == 0`, show `starting_bid` as the starting price.
- `auction_end` is NULL for silent/live items — those inherit `event.ends_at`.
- Effective end time: `COALESCE(items.auction_end, events.ends_at)`.

### Table: `bids`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `item_id` | INT NOT NULL | FK → items.id |
| `bidder_id` | INT NULL | FK → users.id, NULL if floor bid |
| `amount` | DECIMAL(10,2) NOT NULL | |
| `is_floor_bid` | TINYINT(1) DEFAULT 0 | placed by auctioneer on behalf of room |
| `paddle_number` | VARCHAR(20) NULL | for floor bids |
| `intends_gift_aid` | TINYINT(1) DEFAULT 0 | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Table: `gift_aid_declarations`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `user_id` | INT NULL | FK → users.id, NULL if anonymous floor bidder |
| `bid_id` | INT NULL | FK → bids.id — the specific bid this was declared for |
| `full_name` | VARCHAR(255) NOT NULL | |
| `address` | TEXT NOT NULL | |
| `postcode` | VARCHAR(20) NOT NULL | |
| `confirmed_taxpayer` | TINYINT(1) DEFAULT 0 | |
| `gift_aid_amount` | DECIMAL(10,2) NOT NULL | 25% of (bid - market_value), min 0 |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Table: `payments`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `item_id` | INT NOT NULL | FK → items.id |
| `bid_id` | INT NOT NULL | FK → bids.id — the winning bid |
| `winner_id` | INT NOT NULL | FK → users.id |
| `stripe_payment_intent_id` | VARCHAR(255) | |
| `amount` | DECIMAL(10,2) NOT NULL | bid amount |
| `gift_aid_amount` | DECIMAL(10,2) DEFAULT 0.00 | |
| `status` | ENUM('pending','processing','paid','failed','refunded') DEFAULT 'pending' | |
| `payment_requested_at` | TIMESTAMP NULL | when request was sent |
| `paid_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Table: `audit_log`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT AUTO_INCREMENT PRIMARY KEY | |
| `user_id` | INT NULL | FK → users.id |
| `action` | VARCHAR(100) NOT NULL | e.g. `bid_placed`, `item_approved`, `payment_received` |
| `entity_type` | VARCHAR(50) | e.g. `item`, `bid`, `payment` |
| `entity_id` | INT NULL | |
| `details` | TEXT | JSON string of relevant data |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

---

## ROUTES

All routes follow this pattern: `METHOD /path → Controller::method()`

Auth levels: `[pub]` = public, `[auth]` = any logged-in user, `[bidder]` = bidder role, `[donor]` = donor role, `[admin]` = admin role, `[auctioneer]` = admin role with live event access.

### Public Routes
```
GET  /                           HomeController::index()         [pub] Catalogue
GET  /events/:slug               EventController::show()         [pub] Event page
GET  /items/:slug                ItemController::show()          [pub] Item detail
GET  /login                      AuthController::loginPage()     [pub]
POST /login                      AuthController::login()         [pub]
GET  /register                   AuthController::registerPage()  [pub]
POST /register                   AuthController::register()      [pub]
GET  /logout                     AuthController::logout()        [auth]
GET  /terms                      HomeController::terms()         [pub]
GET  /privacy                    HomeController::privacy()       [pub]
```

### Bidder Routes
```
GET  /my-bids                    BidController::myBids()         [bidder]
POST /bids                       BidController::place()          [bidder]
GET  /payment/:item_slug         PaymentController::checkout()   [bidder]
POST /payment/:item_slug         PaymentController::processCheckout() [bidder]
GET  /payment/:item_slug/success PaymentController::success()    [bidder]
```

### Donor Routes
```
GET  /items/submit               ItemController::submitPage()    [donor|admin]
POST /items/submit               ItemController::submit()        [donor|admin]
```

### Admin Routes
```
GET  /admin                      AdminController::dashboard()    [admin]

# Events
GET  /admin/events               AdminController::events()       [admin]
POST /admin/events               AdminController::createEvent()  [admin]
GET  /admin/events/:slug         AdminController::showEvent()    [admin]
POST /admin/events/:slug         AdminController::updateEvent()  [admin]
POST /admin/events/:slug/delete  AdminController::deleteEvent()  [admin]
POST /admin/events/:slug/open    AdminController::openEvent()    [admin]
POST /admin/events/:slug/close   AdminController::closeEvent()   [admin]

# Items
GET  /admin/items                AdminController::items()        [admin]
POST /admin/items/:slug/approve  AdminController::approveItem()  [admin]
POST /admin/items/:slug/reject   AdminController::rejectItem()   [admin]
POST /admin/items/:slug/update   AdminController::updateItem()   [admin]
POST /admin/items/:slug/delete   AdminController::deleteItem()   [admin]
POST /admin/items/:slug/cancel   AdminController::cancelItem()   [admin]

# Users
GET  /admin/users                AdminController::users()        [admin]
POST /admin/users/:slug/role     AdminController::updateUserRole() [admin]

# Payments
GET  /admin/payments             AdminController::payments()     [admin]
POST /admin/payments/:id/request AdminController::requestPayment() [admin]
POST /admin/payments/:id/mark-paid AdminController::markPaid()  [admin]

# Gift Aid
GET  /admin/gift-aid             AdminController::giftAid()      [admin]
GET  /admin/gift-aid/export      AdminController::exportGiftAid() [admin]

# Settings
GET  /admin/settings             AdminController::settings()     [admin]
POST /admin/settings             AdminController::saveSettings() [admin]
```

### Auctioneer Routes (live mode)
```
GET  /auctioneer/:event_slug     AuctioneerController::panel()   [admin]
POST /auctioneer/open-lot        AuctioneerController::openLot() [admin]
POST /auctioneer/close-lot       AuctioneerController::closeLot() [admin]
POST /auctioneer/floor-bid       AuctioneerController::floorBid() [admin]
GET  /display/:event_slug        AuctioneerController::projector() [pub] big screen
```

### API Routes (JSON — for polling + WebSocket)
```
GET  /api/items/:slug/bid        BidController::currentBid()     [pub] returns {current_bid, bid_count, status}
GET  /api/my-bids/status         BidController::myBidStatus()    [bidder] returns bid statuses
POST /api/bids                   BidController::placeAjax()      [bidder] JSON response
GET  /api/live/:event_slug       AuctioneerController::liveState() [pub] current lot state
```

### Webhook Routes
```
POST /webhooks/stripe            PaymentController::stripeWebhook() [pub, no CSRF]
```

---

## VIEWS — FULL SPECIFICATION

### Layout: `app.php`
**Header (sticky, z-40):**
- Logo: `logo-blue.svg` (light) / `logo-white.svg` (dark), `h-14 w-14`
- Nav links (desktop): Home, Events, My Bids (if bidder), Submit Item (if donor/admin), Admin (if admin)
- Nav link style: `text-sm font-semibold uppercase tracking-widest`
- Active link: `text-primary`
- Inactive link: `text-slate-400 dark:text-slate-500 hover:text-primary`
- Dark mode toggle: moon/sun icon, `w-5 h-5`, icon-only button
- Mobile: hamburger → right-side sidebar overlay with full nav links
- Header classes: `bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700/30 sticky top-0 z-40 h-20`

**Footer:**
- `bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700/30`
- Left: WFCS logo + one-line description
- Right: Terms | Privacy links
- Bottom bar: footer_text from style guide with dynamic year

**Shared:**
- `alert-dialog.php` — Popover API alert (no backdrop overlay div). API: `showAlert(message)`
- `confirm-dialog.php` — Popover API confirm. API: `showConfirm(message, onConfirm)`
- `toast.php` — Bottom-right toast. API: `showToast(message, type)` types: success/error/info. Auto-dismisses after 5s with progress bar.
- Flash message: read from cookie on page load, auto-show as toast. Set via `setFlash(type, message)` in PHP before redirect.

---

### Page: Home (`/`) — `home/index.php`
**Purpose:** Public catalogue of all active items across all published events.

**Hero section:**
- Dark slate background (`bg-slate-900`)
- WFCS logo large, tagline: "Bidding that makes a difference."
- If user is admin: "Admin Mode" badge + "Go to Dashboard" button
- If logged out: "Sign in to Bid" button + "Browse Items" button
- If logged in as bidder: "Browse Items" + "My Bids" button

**Filter bar (sticky below hero on scroll):**
- Search input (searches title + description, client-side filter)
- Category dropdown (All | each category from DB)
- Auction type filter: All | Online | Silent Event | Live Event
- Status filter: Active | Ending Soon (< 24h) | All

**Item grid:**
- `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6`
- Each item = **ItemCard** component (see below)
- Empty state: magnifier icon + "No active items found" + Reset Filters button

**ItemCard component:**
- Image: `w-full h-48 object-cover rounded-t-xl`, placeholder gradient if no image
- Category badge: top-left overlay `bg-primary/10 text-primary`
- "Ending Soon" badge: top-right, `bg-amber-100 text-amber-800`, shown if < 24h remain
- "Reserve Met" badge: shown if `current_bid >= reserve_price`
- Title: `text-sm font-semibold text-slate-900 dark:text-white`
- Current bid: `text-2xl font-bold` + `text-primary`
- Starting bid label if no bids placed
- Bid count: `text-xs text-slate-500` e.g. "3 bids"
- Countdown timer: `text-xs text-slate-500` e.g. "2d 4h 31m remaining"
- Entire card is a link to `/items/:slug`

---

### Page: Event (`/events/:slug`) — `events/show.php`
**Purpose:** Landing page for a specific event (gala night, online campaign, etc.)

**Event header:**
- Event name `text-2xl font-bold`
- Venue + date/time range
- Auction type badge: "Online Auction" / "Silent Auction" / "Live Auction"
- Status badge: Draft / Published / Active / Ended
- Description

**For silent/live events: countdown to event end**
- Large countdown: Days, Hours, Minutes, Seconds — JS countdown
- "Bidding opens at [time]" if not started yet

**Item grid:**
- Same ItemCard grid as home page but scoped to this event
- For live mode: shows lot order number on each card

---

### Page: Item Detail (`/items/:slug`) — `items/show.php`
**Purpose:** Full item page with bidding.

**Left column (7/12):**
- Item image: `w-full h-80 object-cover rounded-xl`
- If admin: hover overlay "Edit Item" button → opens edit popover
- Category breadcrumb: `Home > [Category] > [Item Title]`
- Title: `text-2xl font-bold mb-2`
- Description: `text-sm text-slate-600 dark:text-slate-400`
- **Specifications card:**
  - Starting Bid
  - Market Value (for Gift Aid display)
  - Reserve Status: "Reserve met ✓" (green) or "Reserve not met" (muted)
  - Category
  - Closing: formatted datetime
  - Donor: username (not email)
- **Bid history table** (collapsible):
  - Columns: Time | Amount | Gift Aid?
  - Bidder shown as "Bidder #[N]" (anonymised, except admin sees full name)
  - Empty state: "No bids yet. Be the first!"

**Right column (5/12) — sticky:**
- **Current bid card** (dark slate background):
  - "Current Bid" label
  - `£[amount]` in `text-4xl font-black`
  - Reserve badge
  - "[N] bids" count
- **Bid form** (shown if item is active):
  - Hidden if: user not logged in (show "Sign in to bid" button instead)
  - Hidden if: user is the donor of this item
  - Hidden if: item status ≠ active
  - Bid amount input: pre-filled with `current_bid + min_increment`
  - Min/max validation: must be ≥ `current_bid + min_increment`
  - Buy Now button: shown only if `buy_now_price` is set and item is active
  - **Gift Aid section:**
    - Checkbox: "I intend to claim Gift Aid on this bid"
    - If checked and `bid_amount > market_value`: show calculated boost `+£X.XX`
    - If first time checking: Gift Aid declaration fields expand:
      - Full name (pre-filled if user has address)
      - Address (pre-filled)
      - Postcode (pre-filled)
      - "I confirm I am a UK taxpayer" checkbox (required)
    - If user has existing declaration on file: "Using your saved Gift Aid details ✓"
  - Submit button: "Place Bid — £[amount]" primary style
  - On submit: PHP form POST to `/bids`, redirect back with flash message
- **Countdown timer** (JS, updates every second)
- **Status banner** (if ended): "This auction has ended. Winner notified."
- **Buy Now section**: if `buy_now_price` set, separate highlighted box: "Buy Now for £[price]" — single click, confirm via popover, then redirects to payment

**Real-time polling (silent/online mode):**
- On item detail page: JS polls `/api/items/:slug/bid` every 10s
- If `current_bid` has changed: update the displayed amount with a brief pulse animation
- If the viewing user was the previous high bidder and has been outbid: show prominent "You've been outbid!" toast (error type)

---

### Page: My Bids (`/my-bids`) — `bids/my-bids.php`
**Purpose:** Bidder's personal dashboard showing all bids placed.

**Stats row:**
- "Bids Placed": total count
- "Leading": count of items where you hold highest bid
- "Outbid": count where you've been beaten
- "Won": count of items you won (ended, awaiting_payment or paid)

**Tabs:**
- Active | Won | Outbid | All

**Bid cards (per item you've bid on):**
- Item thumbnail (60×60, rounded)
- Item title (link to item)
- Your bid: `£[amount]`
- Status pill: "Leading" (green) / "Outbid" (amber) / "Won" (blue) / "Paid" (green)
- Current winning bid (if outbid: shows the new amount)
- Countdown or "Ended [date]"
- If "Outbid" + item still active: "Bid Again" button (link to item page)
- If "Won" + payment pending: "Pay Now — £[amount]" primary button → `/payment/:item_slug`
- If "Won" + paid: "Paid ✓" badge

**Polling:**
- Page polls `/api/my-bids/status` every 15s while tab is visible
- If any status changes, update the relevant card's status pill without full page reload

---

### Page: Donor Submit (`/items/submit`) — `items/submit.php`
**Purpose:** Donor submits an item for admin approval.

**Form fields:**
- Title (text, required, max 255)
- Description (textarea, required)
- Category (select, required — populated from categories table)
- Image upload (file input, accept jpg/png/webp, max 5MB, validated server-side with `getimagesize()`)
- Starting Bid (number, required, min 1)
- Market Value (number, required, min 0 — for Gift Aid; helper text explains why)
- Reserve Price (number, required, must be ≥ starting bid)
- Buy Now Price (number, optional — leave blank to disable)
- Minimum Increment (number, default 1.00)
- Auction End Date/Time (datetime-local, required for online mode)
- Event (select, optional — links to a published event)

**On submit:**
- Validate all fields server-side
- Save image to `uploads/items/[uuid].[ext]`
- Create item with status `pending`
- `setFlash('success', 'Item submitted for review.')` → redirect home
- Admin sees it in pending queue

---

### Page: Payment Checkout (`/payment/:item_slug`) — `payment/checkout.php`
**Purpose:** Winner completes payment for their won item.

**Guard:** Only accessible to the winning bidder of that item. Redirect others to home with error.

**Item summary:**
- Image thumbnail
- Title
- "You won this auction with a bid of £[amount]"
- Gift Aid amount if applicable: "+ £[ga_amount] Gift Aid (claimed by WFCS)"
- Total line

**Payment form:**
- Stripe Elements card input (embedded)
- "Pay £[amount]" button
- "This payment is processed securely by Stripe" note
- Powered by Stripe badge

**On success:**
- Webhook receives `payment_intent.succeeded` → marks payment `paid`, item `sold`
- Winner also hits `/payment/:slug/success` client-side redirect
- Success page: "Payment received! Thank you." with order summary

---

### Admin: Dashboard (`/admin`) — `admin/dashboard.php`
**Stats row:**
- Total Active Auctions (items with status `active`)
- Pending Approval (items with status `pending`)
- Items Awaiting Payment
- Total Raised (sum of `payments.amount` where status = `paid`) — `£X,XXX`
- Gift Aid Reclaimed (sum of `payments.gift_aid_amount` where status = `paid`) — `£X,XXX`

**Quick actions:**
- "New Event" button → opens create event popover
- "Submit Item" button → `/items/submit`
- "Export Gift Aid" button → `/admin/gift-aid/export`

**Pending approval queue** (if any):
- Table: Item | Donor | Starting Bid | Submitted | Actions
- Actions: Approve (green) / Reject (red) — both open confirm popovers

**Recently ended, awaiting payment** (if any):
- Table: Item | Winner | Bid Amount | Ended | Payment Status | Actions
- Action: "Send Payment Request" button (if `auto_payment = 0` or request not yet sent)

**Activity log** (last 20 entries from `audit_log`):
- Time | Action | Entity | Details

---

### Admin: Events (`/admin/events`) — `admin/events/index.php`
**Table columns:** Name | Type | Status | Starts | Ends | Items | Actions

**Actions per row:**
- View/Edit → `/admin/events/:slug`
- Open (if draft/published) → POST `/admin/events/:slug/open` (sets status to `active`)
- Close (if active) → POST `/admin/events/:slug/close` (sets status to `ended`, triggers payment flow)
- Delete (if draft only) → confirm popover

**Create Event popover (triggered by "New Event" button):**
Fields:
- Name (text, required)
- Description (textarea)
- Venue (text)
- Auction Type (radio: Online / Silent / Live)
- Start Date/Time (datetime-local, required for silent/live)
- End Date/Time (datetime-local, required for silent/live)
- Auto payment toggle (on/off, defaults to setting value)

---

### Admin: Event Detail (`/admin/events/:slug`) — `admin/events/show.php`
**Event info panel** (editable via "Edit Event" popover — same fields as create)

**Event stats:**
- Items count | Active | Pending | Ended
- Total bids | Highest bid | Total raised

**Lots/Items table:**
- Columns: # | Image | Title | Category | Starting Bid | Current Bid | Bids | Status | Actions
- For live mode: drag-to-reorder lot order (updates `lot_order` via POST)
- Actions: Edit | Approve | Reject | Remove from event | Cancel

**Add Item to Event:**
- "Add Existing Item" button → popover with search/select of pending items
- "Add New Item" button → links to `/items/submit?event_id=[id]`

---

### Admin: Items (`/admin/items`) — `admin/items/index.php`
**Filter tabs:** All | Pending | Active | Ended | Awaiting Payment | Cancelled

**Table columns:** Image | Title | Event | Donor | Category | Starting Bid | Current Bid | Bids | Status | Actions

**Actions:**
- Approve → confirm popover → `POST /admin/items/:slug/approve`
- Reject → confirm popover with optional reason → `POST /admin/items/:slug/reject`
- Edit → edit popover (all item fields editable by admin)
- Cancel → confirm popover → `POST /admin/items/:slug/cancel`
- Delete → confirm popover (only if no bids placed)

**Edit Item Popover fields:**
- Title, Description, Category, Image (upload new), Starting Bid, Market Value, Reserve Price, Buy Now Price, Min Increment, Auction End

---

### Admin: Users (`/admin/users`) — `admin/users/index.php`
**Table columns:** Name | Email | Role | Paddle # | Bids | Items Donated | Joined | Actions

**Actions:**
- Change Role → role select popover (bidder / donor / admin)
- View bids → links to `/admin/payments?user=[slug]`

---

### Admin: Payments (`/admin/payments`) — `admin/payments/index.php`
**Filter tabs:** All | Pending | Processing | Paid | Failed

**Table columns:** Item | Winner | Bid Amount | Gift Aid | Total | Status | Requested | Paid | Actions

**Actions:**
- "Send Request" (if not yet sent) → `POST /admin/payments/:id/request` → creates Stripe Payment Intent + emails winner
- "Mark Paid" (manual fallback) → confirm popover → `POST /admin/payments/:id/mark-paid`
- "Resend Email" → resend payment request email

---

### Admin: Gift Aid (`/admin/gift-aid`) — `admin/gift-aid/index.php`
**Stats:** Total declarations | Total Gift Aid claimable | Already claimed

**Table columns:** Bidder | Item | Bid | Market Value | Gift Aid Amount | Bid Date | Name | Postcode | Taxpayer ✓

**Export button:** "Export HMRC CSV" → `/admin/gift-aid/export`
- Generates CSV in HMRC R68 format
- Columns: Full Name, Postcode, Amount (the gift aid boost value), Date

---

### Admin: Settings (`/admin/settings`) — `admin/settings.php`
**Sections with tabs:**

**1. Auction Behaviour**
- Auto-send payment requests toggle (on/off)
- Manual payment review toggle (on/off — if on, auto_payment = off per event override)
- Outbid email notifications toggle

**2. Stripe**
- Publishable key (text input)
- Secret key (password input — stored encrypted)
- Test mode toggle
- "Test Connection" button → makes a test API call, shows success/error

**3. Email (SMTP)**
- SMTP Host, Port, Username, Password
- From Name, From Email
- "Send Test Email" button

All toggles use the style guide `toggle_switch` component.
Each section saves independently (separate POST forms for each tab section).

---

### Auctioneer: Panel (`/auctioneer/:event_slug`) — `auctioneer/panel.php`
**Purpose:** Admin-only control panel for running a live auction in real time.

**Layout:** Full-width, dark theme.

**Left panel — Lot queue:**
- List of all lots in `lot_order` order
- Each row: # | Image thumb | Title | Starting Bid | Status
- Current lot highlighted
- Completed lots greyed out
- Click row to focus lot

**Centre panel — Current lot:**
- Large item image
- Title + description
- Current bid: `text-5xl font-black text-white`
- Bid count
- Status badge: "Open for Bids" (green pulse) / "Closed" / "Pending"

**Right panel — Bid controls:**
- "Open Lot" button → `POST /auctioneer/open-lot` with `item_slug`
  - Sets item status `active`
  - Pushes WebSocket event `lot_opened` to all clients
- "Close Lot" button (only shown when lot is open) → confirm popover → `POST /auctioneer/close-lot`
  - Sets item status `ended`
  - Identifies winner
  - Pushes WebSocket event `lot_closed` with winner info
- **Floor Bid section:**
  - Paddle number input
  - Amount input (pre-filled with `current_bid + min_increment`)
  - "Record Floor Bid" button → `POST /auctioneer/floor-bid`
  - Creates bid with `is_floor_bid = 1`, `paddle_number`, `bidder_id = NULL`
  - Updates `current_bid` on item
  - Pushes WebSocket event `bid_placed` to all clients

**Live bid feed (right panel, scrolling):**
- Last 10 bids for current lot: Time | Amount | Paddle/User | Floor?
- New bids appear at top with slide-in animation

**WebSocket connection status indicator:** Green dot "Live" / Red dot "Reconnecting..."

---

### Auctioneer: Projector (`/display/:event_slug`) — `auctioneer/projector.php`
**Purpose:** Big-screen display for the room. Public URL, no auth required.

**Layout:** `projector.php` layout — full screen, no header/footer, very large text.

**Top bar:** Event name | Current lot: `X of Y` | Time

**Main content:**
- Lot number: `text-8xl font-black text-primary`
- Item image: `w-full h-64 object-cover`
- Item title: `text-4xl font-bold text-white`
- **"BIDDING OPEN"** / **"BIDDING CLOSED"** status — large badge, animated
- Current bid: `text-7xl font-black text-white` — updates in real time via WebSocket
- Bid count: `text-2xl text-slate-300`

**Bottom ticker:** Scrolling list of recent bids

**WebSocket:** Connects to same WebSocket channel. Updates current bid display on `bid_placed` event. Shows `lot_opened` / `lot_closed` state changes.

---

## JAVASCRIPT INTERACTIONS

### `app.js` (global)
- Dark mode: reads `theme` cookie, applies `dark` class to `<html>`, toggle button writes cookie and toggles class
- Mobile menu: hamburger opens right-side sidebar overlay (translate-x animation)
- Flash messages: on DOMContentLoaded, read `flash` cookie, call `showToast()`, clear cookie
- View Transitions API: enabled on all `<a>` navigations within the app

### `bidding.js` (item detail + my-bids pages)
- `startBidPolling(itemSlug, intervalMs = 10000)`: polls `/api/items/:slug/bid`, updates current bid display
- `stopBidPolling()`: clears interval (called on page visibility change to hidden)
- `checkMyBidStatus(intervalMs = 15000)`: polls `/api/my-bids/status`, updates status pills on my-bids page
- Outbid detection: if `was_leading` was `true` and now `false`, call `showToast("You've been outbid!", 'error')`

### `live-auction.js` (live mode pages + projector)
- WebSocket client connecting to Galvani WebSocket endpoint
- Events handled:
  - `bid_placed`: update current bid display, add to bid feed
  - `lot_opened`: show "BIDDING OPEN" state, enable bid form
  - `lot_closed`: show "BIDDING CLOSED" state, disable bid form, show winner info
  - `ping`: heartbeat response
- Auto-reconnect: on `close` event, wait 2s then reconnect

---

## PHASE BREAKDOWN & TASK SEQUENCE

### Phase 1: Foundation

**Task 1.1 — Project files**
- Create `composer.json` with autoloader (PSR-4 `App\` → `app/`)
- Create `package.json` with Tailwind v4 + vite
- Create `tailwind.config.js`
- Create `css/app.css` with `@import "tailwindcss"` + `@theme` block for WFCS tokens:
  ```css
  @theme {
    --color-primary: #45a2da;
    --color-primary-hover: #3b8ec7;
    --font-sans: 'Outfit', system-ui, sans-serif;
  }
  ```
- Add Outfit font: self-hosted woff2 files in `css/fonts/`
- Create `build.sh` for CSS compilation

**Task 1.2 — Database class**
Copy `Database.php` from booking app. Update socket path to auction pattern. This is the singleton with all query methods (`query`, `queryOne`, `execute`, `scalar`, `lastInsertId`).

**Task 1.3 — Schema + seeds**
Write `database/schema.sql` (all tables from spec above).
Write `database/seeds.sql`:
- Insert categories (Watches, Memorabilia, Experience, Art, Jewellery, Sports, Food & Drink, Holiday, Other)
- Insert default settings
- Insert one admin user (password: `password`, hashed)
- Insert 3 sample items in `active` status

Write `db-init.php` to drop + recreate + seed.

**Task 1.4 — Config files**
`config/database.php` — socket path detection as per Galvani rules
`config/app.php` — `APP_KEY`, `APP_NAME`, `APP_URL`
`config/stripe.php` — reads from `settings` table (not .env) so admin can configure via UI

**Task 1.5 — Helpers**
Copy and adapt from booking app:
- `helpers.php`: `e()`, `formatPrice()`, `setFlash()`, `getFlash()`, `redirectTo()`, `paginationHtml()`, `statusBadge()`
- `view.php`: `render()`, `renderPublic()`, `adminTable()`, `adminTableEnd()`, `subTabs()`, `popoverFormStart()`, `popoverFormEnd()`
- `auth.php`: `getAuthUser()`, `setAuthCookie()`, `clearAuthCookie()`, `requireAuth()`, `requireRole()`
- `validation.php`: `validateCsrfToken()`, `getCsrfToken()`
- `config.php`: `config(string $key)` reads from config files

**Task 1.6 — Entry point `index.php`**
- `$basePath` calculation
- .env loader
- Helper requires + autoloader
- CSRF validation on POST (skip `/webhooks/stripe`)
- Routing: load `app/Routes/public.php`, `app/Routes/bidder.php`, `app/Routes/donor.php`, `app/Routes/admin.php`, `app/Routes/auctioneer.php`

**Task 1.7 — Base Controller**
`app/Controllers/Controller.php`:
- `render(string $view, array $data)`: calls `render()` helper
- `redirect(string $path)`: `redirectTo($basePath . $path)`
- `requireAuth()`: calls `requireAuth()` helper, redirects to `/login?return=[path]`
- `requireRole(string ...$roles)`: checks user role, 403 if not allowed
- `json(array $data, int $status = 200)`: JSON response + exit
- `verifyCsrf()`: validates CSRF token

**Task 1.8 — Layout views**
`app/Views/layouts/app.php`:
- `$pageTitle` var in `<title>`
- Outfit font `<link>` + CSS
- Dark mode: `<html class="<?= $_COOKIE['theme'] === 'dark' ? 'dark' : '' ?>">`
- Include `shared/header.php`
- `<?= $pageContent ?>` (set by `render()` via `ob_start()`)
- Include `shared/footer.php`
- Include `shared/alert-dialog.php`, `shared/confirm-dialog.php`, `shared/toast.php`
- `<script src="js/app.js">` at end of body

`app/Views/layouts/projector.php`:
- Full black background, no header/footer, just `$pageContent`
- Include `shared/toast.php` (for WebSocket connection status)
- Include `live-auction.js`

`app/Views/shared/header.php`: as per spec above
`app/Views/shared/footer.php`: as per spec above
`app/Views/shared/alert-dialog.php`: Popover API, `showAlert(msg)` JS function
`app/Views/shared/confirm-dialog.php`: Popover API, `showConfirm(msg, callback)` JS function
`app/Views/shared/toast.php`: fixed bottom-right, `showToast(msg, type)` JS function

---

### Phase 2: Auth

**Task 2.1 — UserRepository**
Methods:
- `findByEmail(string $email): ?array`
- `findBySlug(string $slug): ?array`
- `findById(int $id): ?array`
- `create(array $data): int` — returns inserted ID
- `updateRole(int $id, string $role): void`
- `list(int $limit, int $offset): array`
- `count(): int`

**Task 2.2 — AuthService**
Methods:
- `register(array $data): array` — validates, hashes password, generates slug, calls `UserRepository::create()`
- `login(string $email, string $password): array` — returns `['user' => [...], 'token' => '...']`
- `generateToken(array $user): string` — JWT or signed cookie value (copy JWT class from booking)
- `generateSlug(string $name): string` — `strtolower(preg_replace)`, ensure unique with suffix

**Task 2.3 — AuthController**
- `loginPage()`: show `auth/login.php`
- `login()`: call `AuthService::login()`, set cookie, redirect by role
- `registerPage()`: show `auth/register.php`
- `register()`: call `AuthService::register()`, auto-login, redirect
- `logout()`: `clearAuthCookie()`, redirect `/`

**Task 2.4 — Auth views**

`auth/login.php`:
- Card centred, max-w-md
- Logo at top
- Email input + Password input
- "Sign in" primary button
- "Don't have an account? Register" link
- Error flash message displayed above form

`auth/register.php`:
- Card centred, max-w-md
- Name | Email | Password | Confirm Password
- Role select: Bidder / Donor (admin can only be set via admin panel)
- Postcode + Address (optional, for Gift Aid)
- "Create account" button
- "Already have an account? Sign in" link

---

### Phase 3: Categories + Events + Items

**Task 3.1 — CategoryRepository**
- `all(): array`
- `findBySlug(string $slug): ?array`

**Task 3.2 — EventRepository**
- `all(string $status = null): array`
- `findBySlug(string $slug): ?array`
- `create(array $data): int`
- `update(int $id, array $data): void`
- `delete(int $id): void`
- `updateStatus(int $id, string $status): void`
- `getPublished(): array` — status IN ('published','active')

**Task 3.3 — ItemRepository**
- `allActive(): array` — status = 'active', with JOIN on categories + events
- `findBySlug(string $slug): ?array` — with JOIN on categories + events + donor
- `findByEvent(int $eventId, string $status = null): array`
- `findPending(): array`
- `create(array $data): int`
- `update(int $id, array $data): void`
- `updateStatus(int $id, string $status): void`
- `updateCurrentBid(int $id, float $amount): void` — also increments `bid_count`
- `delete(int $id): void`
- `approve(int $id, int $adminId): void` — sets status `active`, records approver
- `listAdmin(string $status, int $limit, int $offset): array`
- `countAdmin(string $status): int`
- `findNewlyEnded(): array` — status = 'active' AND effective end < NOW()

**Task 3.4 — UploadService**
- `upload(array $file, string $subdir = 'items'): string` — returns relative path
- Validates: `$file['error'] === 0`, size ≤ 5MB, `getimagesize()` must return valid result
- Allowed MIME types: `image/jpeg`, `image/png`, `image/webp`
- Saves to `uploads/[subdir]/[uuid].[ext]`
- Returns `'uploads/items/abc123.jpg'`

**Task 3.5 — EventService**
- `create(array $data): int` — generates slug, validates, calls EventRepository
- `update(int $id, array $data): void`
- `open(int $id): void` — sets status `active`, if silent/live sets items to `active`
- `close(int $id): void` — sets status `ended`, calls `AuctionService::processEventClose()`
- `delete(int $id): void` — only if status = 'draft'

**Task 3.6 — ItemService**
- `submit(array $formData, int $donorId): int` — validates, calls UploadService, calls ItemRepository::create()
- `approve(string $slug, int $adminId): void`
- `reject(string $slug, string $reason = ''): void` — sets status `cancelled`, notifies donor
- `update(string $slug, array $data): void`
- `delete(string $slug): void`
- `generateSlug(string $title): string`

**Task 3.7 — HomeController**
- `index()`: get all active items + published events, render `home/index.php`

**Task 3.8 — EventController**
- `show(string $slug)`: get event + its active items, render `events/show.php`

**Task 3.9 — ItemController**
- `show(string $slug)`: get item + bid history + current user bid status, render `items/show.php`
- `submitPage()`: require donor|admin, get categories + events, render `items/submit.php`
- `submit()`: require donor|admin, call ItemService::submit(), flash + redirect

---

### Phase 4: Bidding

**Task 4.1 — BidRepository**
- `create(array $data): int`
- `findByItem(int $itemId): array` — ordered by amount DESC
- `findWinningBid(int $itemId): ?array` — highest amount for that item
- `findByBidder(int $userId): array` — all bids by a user
- `findBidderStatusForItems(int $userId, array $itemIds): array` — returns map of item_id → ['amount', 'is_leading', 'item_status']
- `getLeadingBidderForItem(int $itemId): ?array`
- `getPreviousHighBidder(int $itemId): ?array` — second highest (for outbid notifications)

**Task 4.2 — BidService**
- `place(int $itemId, int $bidderId, float $amount, bool $intendsGiftAid): array`
  1. Fetch item, verify status = 'active'
  2. Verify effective end time hasn't passed
  3. Verify bidder is not the donor
  4. Verify `amount >= current_bid + min_increment`
  5. Verify `amount >= starting_bid`
  6. Get previous high bidder (for outbid notification)
  7. Call `BidRepository::create()`
  8. Call `ItemRepository::updateCurrentBid()`
  9. If `intendsGiftAid` and `amount > market_value`: calculate gift aid amount
  10. Return `['success' => true, 'new_bid' => amount, 'previous_bidder_id' => ?int]`
- `placeBuyNow(int $itemId, int $bidderId): array`
  - Sets bid at `buy_now_price`, immediately ends item (status `ended`)
- `placeFloorBid(int $itemId, float $amount, string $paddleNumber): array` — for auctioneer

**Task 4.3 — BidController**
- `place()` (POST `/bids`): require bidder, call BidService, flash + redirect back to item
- `placeAjax()` (POST `/api/bids`): JSON response, for live mode
- `myBids()` (GET `/my-bids`): require bidder, get bids with statuses, render `bids/my-bids.php`
- `currentBid()` (GET `/api/items/:slug/bid`): public, returns JSON `{current_bid, bid_count, status, ends_at}`
- `myBidStatus()` (GET `/api/my-bids/status`): require bidder, returns JSON map of item_slug → status

**Test: `tests/BidServiceTest.php`**
- `testBidMustExceedCurrentBid()` — place bid at current_bid, expect exception
- `testBidMustMeetMinIncrement()` — place bid at current_bid + 0.50 when increment is 1.00
- `testDonorCannotBidOwnItem()` — expect exception
- `testItemMustBeActiveToAcceptBid()` — place bid on ended item
- `testValidBidUpdatesCurrentBid()` — happy path

---

### Phase 5: Gift Aid

**Task 5.1 — GiftAidRepository**
- `create(array $data): int`
- `findByUser(int $userId): array`
- `findByBid(int $bidId): ?array`
- `getLatestDeclarationForUser(int $userId): ?array`
- `listAll(int $limit, int $offset): array`
- `count(): int`
- `totalClaimable(): float`
- `exportAll(): array` — all declarations with user + item data for CSV

**Task 5.2 — GiftAidService**
- `calculate(float $bidAmount, float $marketValue): float`
  - Returns `max(0, ($bidAmount - $marketValue) * 0.25)`
- `saveDeclaration(int $bidId, int $userId, array $formData): void`
  - Validates: full_name, address, postcode, confirmed_taxpayer = 1
  - Calculates gift_aid_amount
  - Calls GiftAidRepository::create()
- `getExistingDeclaration(int $userId): ?array`
- `exportCsv(): string` — returns CSV string in HMRC R68 format

**Test: `tests/GiftAidServiceTest.php`**
- `testCalculationAboveMarketValue()` — bid £200, market value £100 → £25
- `testCalculationAtMarketValue()` — bid £100, market value £100 → £0
- `testCalculationBelowMarketValue()` — bid £50, market value £100 → £0

**Task 5.3 — GiftAidController**
- Wires into `BidController::place()` — after placing bid, if `intends_gift_aid = 1`, call GiftAidService
- `AdminController::giftAid()`: list declarations, stats
- `AdminController::exportGiftAid()`: output CSV download headers + GiftAidService::exportCsv()

---

### Phase 6: Auction Timing + Status Transitions

**Task 6.1 — AuctionService**
- `processEndedItems(): void`
  - Fetches all items where status = 'active' AND effective end < NOW()
  - For each: calls `endItem()`
- `endItem(int $itemId): void`
  - Updates item status `ended`
  - Finds winning bid via `BidRepository::findWinningBid()`
  - If winner found: creates payment record (`PaymentRepository::create()`), updates item status to `awaiting_payment`
  - If no bids: item status stays `ended` (no payment record)
  - If `auto_payment = 1` (from settings): calls `PaymentService::requestPayment()`
  - Logs to `audit_log`
- `processEventClose(int $eventId): void`
  - Fetches all active items for that event
  - Calls `endItem()` for each
- `effectiveEndTime(array $item): string`
  - Returns `$item['auction_end'] ?? $item['event_ends_at']`

**Task 6.2 — Hook into admin page load**
In `index.php` (or base Controller): after auth check on admin routes, call `AuctionService::processEndedItems()`. This is the "piggyback on admin traffic" approach. No cron needed.

**Test: `tests/AuctionServiceTest.php`**
- `testEndedItemGetsWinnerIdentified()`
- `testItemWithNoBidsStaysEnded()`
- `testAutoPaymentRequestTriggeredWhenSettingOn()`

---

### Phase 7: Stripe Payments

**Task 7.1 — SettingsRepository**
- `get(string $key): ?string`
- `set(string $key, string $value): void`
- `getMany(array $keys): array` — returns associative array

**Task 7.2 — SettingsService**
- `get(string $key): ?string` — decrypts encrypted values
- `set(string $key, string $value, bool $encrypt = false): void`
- `getStripeSecretKey(): ?string`
- `getStripePublishableKey(): ?string`

**Task 7.3 — PaymentRepository**
- `create(array $data): int`
- `findById(int $id): ?array`
- `findByItem(int $itemId): ?array`
- `updateStatus(int $id, string $status): void`
- `updateStripeIntentId(int $id, string $intentId): void`
- `markPaid(int $id): void`
- `listAll(string $status, int $limit, int $offset): array`
- `count(string $status): int`
- `totalRaised(): float`
- `totalGiftAid(): float`
- `findPendingAwaitingRequest(): array` — `payment_requested_at IS NULL`

**Task 7.4 — PaymentService**
- `requestPayment(int $paymentId): void`
  - Gets payment + item + winner from DB
  - Creates Stripe PaymentIntent via API: `amount` in pence, `currency` = `gbp`, `metadata` = `['payment_id' => $paymentId]`
  - Stores `stripe_payment_intent_id` via PaymentRepository
  - Sends "Payment Request" email to winner with link to `/payment/:item_slug`
  - Updates `payment_requested_at`
- `handleWebhook(string $payload, string $sigHeader): void`
  - Verifies Stripe signature
  - On `payment_intent.succeeded`: marks payment `paid`, updates item status to `sold`, logs to audit_log, sends confirmation email to winner + notification email to admin
  - On `payment_intent.payment_failed`: marks payment `failed`, notifies admin
- `getClientSecret(int $paymentId): string`
  - Retrieves or creates PaymentIntent, returns `client_secret`
- `markPaidManually(int $paymentId): void`

**Task 7.5 — PaymentController**
- `checkout(string $itemSlug)`: verify user is winner, get client secret, render `payment/checkout.php` with Stripe publishable key + client secret
- `processCheckout(string $itemSlug)`: handles form POST for non-JS fallback (Stripe Elements handles this client-side usually)
- `success(string $itemSlug)`: show success page
- `stripeWebhook()`: raw body + `Stripe-Signature` header → `PaymentService::handleWebhook()`

---

### Phase 8: Admin + Notifications

**Task 8.1 — NotificationService**
- `sendPaymentRequest(array $payment, array $item, array $winner): void`
- `sendPaymentConfirmation(array $payment, array $item, array $winner): void`
- `sendOutbidNotification(int $userId, array $item, float $newBid): void`
- `sendItemRejected(int $donorId, array $item, string $reason): void`
- Uses SMTP settings from SettingsService. Basic `mail()` or PHPMailer (use PHPMailer — add via composer).

**Task 8.2 — AdminController (full)**
All admin routes from the Routes section above, calling respective services.

**Task 8.3 — Admin views**
All admin views from the Views spec above.

---

### Phase 9: Live Auction Mode

**Task 9.1 — WebSocketService**
Wraps Galvani's WebSocket broadcasting.
- `broadcast(string $channel, string $event, array $data): void`
- Channel naming: `live-auction-{event_slug}`
- Events: `bid_placed`, `lot_opened`, `lot_closed`, `ping`

**Task 9.2 — AuctioneerController**
All routes from the Routes section above. Each action:
- Validates admin auth
- Calls BidService or ItemService
- Calls WebSocketService::broadcast()
- Returns JSON

**Task 9.3 — Auctioneer views**
Panel + Projector views as per spec above.

**Task 9.4 — `live-auction.js`**
WebSocket client as per spec above. Also handles bid submission for live mode (POST `/api/bids`, JSON response, no page reload).

---

### Phase 10: Polish

**Task 10.1 — CSRF on all forms**
Every `<form method="POST">` must have `<input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">`.
Stripe webhook route: skip CSRF check (raw Stripe payload).

**Task 10.2 — Mobile QA**
- Test every page at 375px width
- Test every popover on mobile
- Test bid form on mobile
- Test admin tables (horizontal scroll)

**Task 10.3 — Dark mode**
Every view must include `dark:` variants for all `bg-`, `text-`, `border-` classes. Follow style guide palette exactly.

**Task 10.4 — Sorting tables**
Add `data-sortable` to admin tables. Include `table-sort.js` (from booking app) via `$pageScripts`.

**Task 10.5 — `.env` + `php.ini`**
`.env`:
```
GALVANI_MYSQL=1
DB_DATABASE=auction_db
APP_KEY=generate_64_char_hex
APP_NAME="WFCS Auction"
```

---

## ACCEPTANCE CRITERIA (the full app is done when...)

- [ ] Public can browse items and events without logging in
- [ ] Bidder can register, log in, place bids, see Gift Aid calculation, view My Bids dashboard
- [ ] Bidder is notified (toast) when outbid on active item page
- [ ] Donor can submit items; items sit in pending until admin approves
- [ ] Admin can create events (online/silent/live), manage items, approve/reject
- [ ] Auction timing works: online items close at their own datetime, silent items close at event end
- [ ] Auto-payment toggle: when ON, payment request sent automatically; when OFF, admin clicks "Send Request"
- [ ] Winner receives email with Stripe payment link; pays; item marked sold
- [ ] Gift Aid calculated per bid; admin can export HMRC CSV
- [ ] Live mode: auctioneer panel opens/closes lots, records floor bids; projector view updates in real time
- [ ] All dialogs use Popover API — zero `alert()`/`confirm()`/`prompt()`
- [ ] All icons are inline Heroicons/Feather SVG — zero icon libraries
- [ ] No numeric IDs in any URL — slugs only
- [ ] Full dark mode throughout
- [ ] CSRF on all POST forms
- [ ] Mobile-first responsive on all pages

---

## EXECUTION ORDER SUMMARY

1. Foundation (schema, config, helpers, layout, entry point)
2. Auth (register, login, logout)
3. Public browsing (home, event, item detail — read only)
4. Bidding (place bid, bid validation, My Bids)
5. Gift Aid (declaration, calculation, admin list)
6. Donor submission + Admin approval workflow
7. Auction timing + status transitions
8. Stripe payments (checkout, webhook, payment dashboard)
9. Admin settings (toggles, Stripe keys, SMTP)
10. Notifications (email — payment request, outbid, confirmation)
11. Live auction mode (WebSocket, auctioneer panel, projector)
12. Polish (CSRF audit, mobile QA, dark mode audit, table sorting)
