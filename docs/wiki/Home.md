# WFCS Auction — Wiki Home

Welcome to the WFCS Auction wiki. This is the central reference for contributors, developers, and administrators.

---

## What this app does

WFCS Auction is a charity auction platform built for [The Well Foundation (WFCS)](https://wellfoundation.org.uk). It enables the charity to run both online and live in-person auctions, accept payments via Stripe, and claim Gift Aid on eligible donations.

**Key features:**

- Public auction browsing and online bidding
- Donor submission portal for item donations
- Stripe Checkout integration for winner payments
- Gift Aid tracking with HMRC-compatible CSV export
- Live event auctioneer panel with projector display
- Admin panel for managing events, items, users, and payments
- REST API for external integrations

---

## Tech stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+, MVC pattern |
| Runtime (dev) | Galvani (async multi-threaded PHP + embedded MariaDB) |
| Runtime (prod) | LAMP (Apache + MySQL + PHP) |
| Frontend | TailwindCSS v4, Vanilla JS |
| Payments | Stripe Checkout |
| Email | PHPMailer (SMTP) |
| Auth | JWT (stored in HttpOnly cookie) |
| Tests | PHPUnit 11 |

---

## Quick links

| Document | Description |
|----------|-------------|
| [Getting Started](Getting-Started.md) | Set up your local development environment |
| [Architecture](Architecture.md) | Technical overview of the system design |
| [Developer Guide](../developer/README.md) | Full developer reference |
| [Admin Guide](../admin/README.md) | Guide for charity staff running the platform |
| [REST API Reference](../api/README.md) | API endpoints, authentication, error codes |

---

## Charity details

**The Well Foundation (WFCS)**
Registered charity in Scotland — No: SC040105
Contact: info@wellfoundation.org.uk

---

## Content guidelines

This is a Muslim charity auction. The platform must never list items involving alcohol, pork, gambling, or anything inconsistent with Islamic values.

All currency is displayed in **pounds sterling (£)**. No other currency symbol is ever used.
