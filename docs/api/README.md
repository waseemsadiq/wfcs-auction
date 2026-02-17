# REST API Reference

WFCS Auction exposes a versioned REST API at `/api/v1/` and a separate internal AJAX polling endpoint at `/api/`. All responses are JSON.

---

## Table of Contents

- [Authentication](#authentication)
- [Endpoints](#endpoints)
  - [Items](#items)
  - [Events](#events)
  - [Bids](#bids)
  - [Users](#users)
  - [Token](#token)
  - [Internal AJAX](#internal-ajax)
- [Error Responses](#error-responses)
- [Rate Limiting](#rate-limiting)
- [Webhooks](#webhooks)

---

## Authentication

### How tokens work

The API uses **JWT tokens** for authentication. Tokens are passed in one of two ways:

| Method | How to send |
|--------|-------------|
| POST body | Include a `token` field in the request body |
| Query string | Append `?token=YOUR_TOKEN` to the URL |

> **Important:** Do NOT use the `Authorization` header. The Galvani runtime drops custom HTTP headers before they reach PHP. Always pass the token as a query parameter or in the POST body.

### Token format

- JWT signed with HMAC-SHA256
- Payload includes: `id`, `email`, `name`, `role`, `slug`, `verified`, `exp`
- API tokens have a **1-year expiry** (365 days from generation)
- Web session tokens (stored in cookie) have a shorter expiry

### Getting a token

You must first authenticate via the web interface (log in at `/login`), then call the token generation endpoint:

```
GET /api/v1/token
```

This requires an active web session (cookie-based auth). The response contains a long-lived token you can use for API calls.

### Example: get your token

```bash
# 1. Log in via web and capture the auth cookie
curl -c cookies.txt -X POST https://example.com/login \
  -d "email=you@example.com&password=secret&_csrf_token=TOKEN"

# 2. Use the cookie session to get an API token
curl -b cookies.txt https://example.com/api/v1/token
```

Response:

```json
{
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 31536000
  }
}
```

### Using the token

```bash
# Via query string (GET requests)
curl "https://example.com/api/v1/users/me?token=eyJ..."

# Via POST body
curl -X POST https://example.com/api/v1/bids \
  -d "item_slug=rolex-submariner&amount=4500&buy_now=0&token=eyJ..."
```

---

## Endpoints

### Response envelope

All successful responses use this envelope:

```json
{
  "data": { ... },
  "meta": { ... }
}
```

Paginated responses include a `meta` object:

```json
{
  "data": [ ... ],
  "meta": {
    "total": 42,
    "page": 1,
    "per_page": 20,
    "pages": 3
  }
}
```

---

## Items

### List items

```
GET /api/v1/items
```

Returns a paginated list of publicly visible items (status: `active`, `ended`, or `sold`).

**Authentication:** Not required

**Query parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `q` | string | — | Search query (matches item title using LIKE) |
| `category` | string | — | Filter by category slug |
| `event` | string | — | Filter by event slug |
| `page` | integer | `1` | Page number |
| `per_page` | integer | `20` | Results per page (max 100) |

**Example request:**

```bash
curl "https://example.com/api/v1/items?q=watch&category=jewellery&page=1&per_page=10"
```

**Example response:**

```json
{
  "data": [
    {
      "id": 1,
      "slug": "rolex-submariner",
      "title": "Rolex Submariner",
      "description": "A stunning timepiece donated by a generous supporter.",
      "image": "/uploads/rolex.jpg",
      "lot_number": 1,
      "starting_bid": "500.00",
      "min_increment": "50.00",
      "buy_now_price": "4200.00",
      "market_value": "4200.00",
      "current_bid": "1250.00",
      "bid_count": 7,
      "status": "active",
      "ends_at": "2026-03-01 20:00:00",
      "category_name": "Jewellery & Watches",
      "event_title": "WFCS Spring Gala 2026",
      "event_slug": "spring-gala-2026"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "per_page": 10,
    "pages": 1
  }
}
```

---

### Get item detail

```
GET /api/v1/items/:slug
```

Returns full detail for a single item, including the last 5 bids in its history (with masked bidder names).

**Authentication:** Not required

**Path parameters:**

| Parameter | Description |
|-----------|-------------|
| `slug` | Item slug (e.g. `rolex-submariner`) |

**Example request:**

```bash
curl "https://example.com/api/v1/items/rolex-submariner"
```

**Example response:**

```json
{
  "data": {
    "id": 1,
    "slug": "rolex-submariner",
    "title": "Rolex Submariner",
    "description": "A stunning timepiece...",
    "image": "/uploads/rolex.jpg",
    "lot_number": 1,
    "starting_bid": "500.00",
    "min_increment": "50.00",
    "buy_now_price": "4200.00",
    "market_value": "4200.00",
    "current_bid": "1250.00",
    "bid_count": 7,
    "status": "active",
    "ends_at": "2026-03-01 20:00:00",
    "bid_history": [
      {
        "amount": "1250.00",
        "bidder": "Ah***",
        "created_at": "2026-02-15 14:22:11"
      }
    ]
  }
}
```

---

## Events

### List events

```
GET /api/v1/events
```

Returns a paginated list of publicly visible events (status: `published` or `active`).

**Authentication:** Not required

**Query parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `status` | string | — | Filter by status: `published` or `active` |
| `page` | integer | `1` | Page number |
| `per_page` | integer | `20` | Results per page (max 100) |

**Example request:**

```bash
curl "https://example.com/api/v1/events?status=active"
```

**Example response:**

```json
{
  "data": [
    {
      "id": 1,
      "slug": "spring-gala-2026",
      "title": "WFCS Spring Gala 2026",
      "description": "Our annual charity gala auction.",
      "status": "active",
      "starts_at": "2026-03-01 18:00:00",
      "ends_at": "2026-03-01 22:00:00",
      "venue": "The Grand Hall, Glasgow"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "per_page": 20,
    "pages": 1
  }
}
```

---

### Get event detail

```
GET /api/v1/events/:slug
```

Returns full detail for a single event. Events with status `draft` return 404.

**Authentication:** Not required

**Path parameters:**

| Parameter | Description |
|-----------|-------------|
| `slug` | Event slug (e.g. `spring-gala-2026`) |

**Example request:**

```bash
curl "https://example.com/api/v1/events/spring-gala-2026"
```

---

### Get event items

```
GET /api/v1/events/:slug/items
```

Returns all items belonging to a specific event, paginated.

**Authentication:** Not required

**Path parameters:**

| Parameter | Description |
|-----------|-------------|
| `slug` | Event slug |

**Query parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | `1` | Page number |
| `per_page` | integer | `50` | Results per page (max 100) |

**Example request:**

```bash
curl "https://example.com/api/v1/events/spring-gala-2026/items"
```

**Example response:**

```json
{
  "data": [ ... ],
  "meta": {
    "event_slug": "spring-gala-2026",
    "event_title": "WFCS Spring Gala 2026",
    "total": 12,
    "page": 1,
    "per_page": 50
  }
}
```

---

## Bids

### Place a bid

```
POST /api/v1/bids
```

Places a bid on an item. Requires authentication and a verified email address.

**Authentication:** Required

**Request body (application/x-www-form-urlencoded):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `item_slug` | string | Yes | Slug of the item to bid on |
| `amount` | float | Yes | Bid amount in pounds (e.g. `1300.00`) |
| `buy_now` | integer | No | Pass `1` to use the Buy Now price |
| `token` | string | Yes | Your API token |

**Example request:**

```bash
curl -X POST https://example.com/api/v1/bids \
  -d "item_slug=rolex-submariner&amount=1300&buy_now=0&token=eyJ..."
```

**Example response (201 Created):**

```json
{
  "data": {
    "bid_id": 8,
    "item_slug": "rolex-submariner",
    "amount": "1300.00",
    "is_buy_now": false,
    "new_current_bid": "1300.00",
    "bid_count": 8
  }
}
```

**Validation rules:**

- The item must be in `active` status
- The bid amount must exceed the current bid plus the minimum increment
- The user's email must be verified
- The user cannot bid on their own donated item
- Rate limited: 30 bids per minute per user

---

## Users

### Get current user

```
GET /api/v1/users/me
```

Returns the authenticated user's profile. Password hash is never included.

**Authentication:** Required

**Query parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | Your API token |

**Example request:**

```bash
curl "https://example.com/api/v1/users/me?token=eyJ..."
```

**Example response:**

```json
{
  "data": {
    "id": 42,
    "slug": "ahmed-khan",
    "name": "Ahmed Khan",
    "email": "ahmed@example.com",
    "role": "bidder",
    "email_verified": true,
    "gift_aid_eligible": false,
    "created_at": "2026-01-15 10:30:00"
  }
}
```

---

### Get my bids

```
GET /api/v1/users/me/bids
```

Returns the authenticated user's bid history, paginated.

**Authentication:** Required

**Query parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | Your API token |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Results per page (default: 20, max: 100) |

**Example request:**

```bash
curl "https://example.com/api/v1/users/me/bids?token=eyJ...&page=1&per_page=20"
```

**Example response:**

```json
{
  "data": [
    {
      "bid_id": 8,
      "item_slug": "rolex-submariner",
      "item_title": "Rolex Submariner",
      "amount": "1300.00",
      "is_buy_now": false,
      "is_winning": true,
      "item_status": "active",
      "created_at": "2026-02-15 14:22:11"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "per_page": 20,
    "pages": 1
  }
}
```

---

## Token

### Generate API token

```
GET /api/v1/token
```

Generates a long-lived API token (1-year expiry). Requires an active web session (browser cookie). This endpoint is intended for first-time setup — use the browser to log in, then call this once to get a token for ongoing API use.

**Authentication:** Requires active web session (cookie)

**Example request:**

```bash
curl -b cookies.txt "https://example.com/api/v1/token"
```

**Example response:**

```json
{
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 31536000
  }
}
```

The `expires_in` value is in seconds (31,536,000 = 365 days).

---

## Internal AJAX

### Get current bid (polling)

```
GET /api/current-bid/:slug
```

Returns the live current bid and bid count for an item. Used by the item detail page for real-time polling. No authentication required.

**Path parameters:**

| Parameter | Description |
|-----------|-------------|
| `slug` | Item slug |

**Example request:**

```bash
curl "https://example.com/api/current-bid/rolex-submariner"
```

**Example response:**

```json
{
  "data": {
    "slug": "rolex-submariner",
    "current_bid": 1300.00,
    "bid_count": 8,
    "status": "active"
  }
}
```

---

## Error Responses

All errors return JSON with an `error` key:

```json
{
  "error": "Human-readable error message."
}
```

### HTTP status codes

| Code | Meaning | Common causes |
|------|---------|---------------|
| `400` | Bad Request | Missing required field, invalid parameter format |
| `401` | Unauthorised | No token provided, token missing or invalid |
| `403` | Forbidden | Token valid but insufficient permissions (e.g. not an admin) |
| `404` | Not Found | Item/event/user does not exist or is not publicly visible |
| `422` | Unprocessable Entity | Business rule violation (e.g. bid too low, item not active, email not verified) |
| `429` | Too Many Requests | Rate limit exceeded — see retry message for wait time |

### Example 401 response

```json
{
  "error": "Authentication required."
}
```

### Example 422 response

```json
{
  "error": "Your bid must be at least £1,350.00 (current bid £1,300.00 + minimum increment £50.00)."
}
```

### Example 429 response

```json
{
  "error": "Too many attempts. Please try again in 5 minutes."
}
```

---

## Rate Limiting

Rate limits are enforced at the database level (DB-based `rate_limits` table). This works correctly in Galvani's multi-threaded environment where in-memory state is not shared between threads.

Identifiers are typically the user's slug (for authenticated requests) or IP address (for unauthenticated requests).

### Rate limit thresholds

| Action | Max attempts | Window | Block duration |
|--------|-------------|--------|----------------|
| `login` | 5 | 15 minutes | 30 minutes |
| `register` | 3 | 60 minutes | 60 minutes |
| `bid` | 30 | 60 seconds | 5 minutes |
| `api_token` | 10 | 60 minutes | 60 minutes |
| `password_reset` | 3 | 60 minutes | 60 minutes |
| `resend_verification` | 3 | 60 minutes | 60 minutes |

When a rate limit is exceeded, the API returns HTTP `429` with a message indicating how long to wait.

---

## Webhooks

### Stripe webhook

WFCS Auction handles Stripe webhooks to confirm payments after a successful Stripe Checkout session.

**Webhook URL format:**

```
POST https://yourdomain.com/webhook/stripe?webhook_secret=YOUR_TOKEN
```

> **Why a query parameter?** Stripe typically sends a `Stripe-Signature` header. However, the Galvani runtime (and some production reverse proxies) strip custom HTTP headers before they reach PHP. Instead, the app uses a shared secret passed as a query parameter to verify the webhook origin.

**Setting up the webhook in Stripe Dashboard:**

1. Go to **Stripe Dashboard → Developers → Webhooks**
2. Click **Add endpoint**
3. Enter your URL: `https://yourdomain.com/webhook/stripe?webhook_secret=YOUR_TOKEN`
4. Under "Events to listen for", select: `checkout.session.completed`
5. Generate a secure random token (e.g. `openssl rand -hex 32`) and use it as `YOUR_TOKEN`
6. Configure the same token in **Admin → Settings → Stripe → Webhook URL Token**

**Events handled:**

| Event | Action taken |
|-------|-------------|
| `checkout.session.completed` | Marks the payment as `completed`, marks the item as `sold`, triggers Gift Aid calculation if applicable |

**Webhook request body:**

The request body is the raw Stripe event JSON. The app extracts `checkout.session.completed` events and uses `client_reference_id` to match them to internal payment records.

**Response:**

- `200 OK` — event processed successfully
- `400 Bad Request` — missing or invalid webhook secret
- `404 Not Found` — no matching payment record found
