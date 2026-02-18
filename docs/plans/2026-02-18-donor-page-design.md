# Donor Page — Design Document
_2026-02-18_

## Overview

A `/my-donations` page for any logged-in user who has donated items. Shows all their donated items in chronological order (oldest first) with status, auction assignment, sale price, and masked winner. Mirrors the visual style of the My Bids page.

---

## Route & Access

- `GET /my-donations` — requires login, redirects to `/login` if unauthenticated
- Visible to any logged-in user (bidder, donor, admin)
- Nav link "My Donations" added for all logged-in users in header and mobile menu
- Empty state shown if the user has never donated

---

## Stats Bar

Three stat cards displayed at the top (hidden if no donations):

| Stat | Source |
|------|--------|
| Items donated | COUNT(*) WHERE donor_id = user.id |
| Items sold | COUNT(*) WHERE donor_id = user.id AND status = 'sold' |
| Total raised | SUM(current_bid) WHERE donor_id = user.id AND status = 'sold' |

---

## Item List

Ordered by `items.created_at ASC` (donation date, oldest first).

Each item card shows:
- Thumbnail (if uploaded), otherwise placeholder icon
- Item title
- Status badge (see below)
- Auction name — linked to event if status is published/active, plain text otherwise
- Status-specific detail line (see below)

### Status Badge & Detail Line

| Item status | Badge | Detail line |
|-------------|-------|-------------|
| `pending` | Yellow "Pending review" | "Awaiting review by our team" |
| `active` | Blue "In auction" | "In auction · £X,XXX current bid" |
| `ended` (unsold) | Grey "Auction ended" | "Auction ended — not sold" |
| `sold` | Green "Sold" | "£X,XXX raised · Won by Jane D. · 14 Feb 2026" |

### Winner Display (sold items)

Winner masked as "First L." — same `maskName()` logic used in the bid feed and auctioneer panel. Sourced from `items.winner_id → users.name`. Sold date proxied from `items.updated_at`.

---

## Architecture

### New files
- `app/Controllers/DonorController.php` — `myDonations()` method
- `app/Views/donor/my-donations.php` — page view

### Modified files
- `app/Repositories/ItemRepository.php` — add `forDonor(int $userId): array`
- `app/Views/partials/header-public.php` — add "My Donations" nav link for logged-in users
- `app/Views/partials/mobile-menu.php` — same
- `index.php` — register `GET /my-donations` route

### ItemRepository::forDonor()

Single query with LEFT JOINs:

```sql
SELECT items.*,
       e.title AS event_title,
       e.slug  AS event_slug,
       e.status AS event_status,
       w.name  AS winner_name
FROM   items
LEFT   JOIN events e ON items.event_id = e.id
LEFT   JOIN users  w ON items.winner_id = w.id
WHERE  items.donor_id = ?
ORDER  BY items.created_at ASC
```

Winner name masked in PHP with same `maskName()` logic (first word + first initial of last word + ".").

### Stats computed in PHP from the returned array — no extra queries.

---

## Navigation

Add "My Donations" link between "My Bids" and "Donate an Item" in both:
- `app/Views/partials/header-public.php`
- `app/Views/partials/mobile-menu.php`

Shown only when `$user !== null`. Active state key: `'my-donations'`.

---

## Empty State

If no items donated: friendly message — "You haven't donated any items yet." with a CTA button linking to `/donate`.

---

## Design Notes

- Visual style mirrors My Bids page (fade-up animations, stat cards, form-section cards)
- No real-time polling needed — static page
- Winner name never shown in full — always "First L." format
- Sold date = `items.updated_at` (best available proxy, no separate `sold_at` column)
