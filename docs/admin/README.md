# Admin Guide

User guide for charity staff managing the WFCS Auction platform. This guide covers everything you need to run a successful charity auction — from creating events to downloading Gift Aid reports.

---

## Table of Contents

- [Logging In](#logging-in)
- [Admin Navigation](#admin-navigation)
- [Managing Auctions](#managing-auctions)
- [Managing Items](#managing-items)
- [Managing Users](#managing-users)
- [Payments](#payments)
- [Gift Aid](#gift-aid)
- [Live Events — Auctioneer Panel](#live-events--auctioneer-panel)
- [Settings](#settings)

---

## Logging In

Navigate to `https://yourdomain.com/login` and sign in with your admin credentials.

Admin accounts have the `admin` role and are not created via the public registration page. Contact the system developer to create the first admin account directly in the database, or promote an existing user via Admin → Users.

Once logged in as an admin, you will see the full admin navigation bar across the top of every page.

---

## Admin Navigation

The admin panel has eight sections, accessible from the top navigation bar:

| Section | What it does |
|---------|-------------|
| **Dashboard** | Overview of active auctions, recent bids, recent payments |
| **Auctions** | Create and manage auction events |
| **Items** | Approve donor submissions and manage all items |
| **Users** | View and manage registered bidders and donors |
| **Payments** | Track payment status for all won items |
| **Gift Aid** | Download HMRC-compliant Gift Aid report |
| **Live Events** | Control the live auctioneer panel and projector display |
| **Settings** | Stripe, email, and webhook configuration |

---

## Role Hierarchy

The platform has four user roles with escalating permissions:

| Role          | Admin panel | Auctions / Items / Users | Payments / Gift Aid / Settings |
|---------------|-------------|--------------------------|-------------------------------|
| **Bidder**    | No          | Browsing only            | No                            |
| **Donor**     | No          | Browsing only            | No                            |
| **Admin**     | Yes         | Full control             | No                            |
| **Super Admin** | Yes       | Full control             | Yes                           |

Super admin accounts are created directly in the database. There is no UI for promoting a user to super admin.

### What admins can do

Admins have access to the full admin panel — Dashboard, Auctions, Items, Users, and Live Events — but cannot view or change Payments, Gift Aid records, or platform Settings.

### What super admins can do

Super admins have full access to everything, including:
- Payments — view all payment records
- Gift Aid — download HMRC reports
- Settings — configure Stripe, SMTP, and webhooks
- Delete admin accounts (admins cannot delete other admins)
- Change any non-super-admin user's role, including promoting to admin

---

## Managing Auctions

### Creating a new auction

1. Go to **Admin → Auctions**
2. Click **Create Auction**
3. Fill in the details:
   - **Title** — the auction name shown to bidders (e.g. "WFCS Spring Gala 2026")
   - **Description** — optional text shown on the auction listing page
   - **Venue** — physical location, if applicable (e.g. "The Grand Hall, Glasgow")
   - **Start date/time** — when bidding opens
   - **End date/time** — when bidding closes automatically
4. Click **Save** — the auction is created in **Draft** status

### Auction status flow

Events progress through a defined set of statuses. Each status controls what bidders can see and do.

```
Draft → Published → Active → Ended → Closed
```

| Status | Visibility | Bidding |
|--------|-----------|---------|
| **Draft** | Admin only | No |
| **Published** | Public — listed on the Auctions page | No |
| **Active** | Public | Yes — bidding is open |
| **Ended** | Public — shown as ended | No — bidding has closed |
| **Closed** | Public — archived | No |

### When to use each status

- **Draft** — while you are still setting up the auction and adding items. Bidders cannot see it.
- **Published** — when you want to announce the auction ahead of time. Bidders can browse items and save their interest, but cannot bid yet.
- **Active** — when bidding is open. The auction automatically transitions to **Ended** when the end date/time passes (no cron job needed).
- **Ended** — bidding is closed. Winners are notified by email and prompted to pay. Admins should review payments.
- **Closed** — fully archived. Use this once all payments are resolved.

### Changing an auction status

From **Admin → Auctions**, click the action buttons next to each auction:

- **Publish** — Draft → Published
- **Open** — Published → Active (also usable to start a live event early)
- **End** — Active → Ended (use this to close bidding early if needed)

The transition to **Ended** also happens automatically when the auction's end date/time passes.

---

## Managing Items

### Reviewing donor submissions

Donors submit items via the **Donate an Item** page. The form collects the donor's contact details (name, email, and optionally phone number, company name, company contact, and website) along with the item information. If the email address matches an existing account, the item is linked to that account automatically; otherwise a new donor account is created.

When a submission comes in you will receive an **email notification** with a thumbnail of the item image. The donor also receives a thank-you email confirming receipt.

Submitted items arrive with status `pending` and must be approved by an admin before they appear in the auction.

1. Go to **Admin → Items**
2. Items with status `pending` are listed at the top
3. Click **Edit** on an item to review its details
4. Set the **Lot Number** if you are running a live event
5. Optionally set the **Market Value** (used for Gift Aid calculations)
6. Click **Approve** to make the item visible in the auction
7. Click **Reject** if the item is not suitable — the donor will be notified by email

### Creating items directly

Admins can create items without a donor submission:

1. Go to **Admin → Items → Add Item**
2. Fill in all required fields (title, description, starting bid, category, event)
3. Upload an item image
4. Set the **Market Value** for Gift Aid purposes
5. Click **Save**

### Market value and Gift Aid

The **Market Value** field represents the fair market price of the item. This is important for Gift Aid calculations — Gift Aid can only be claimed on the portion of a bid that exceeds the market value of the item received. Always set this accurately.

### Lot numbers

Lot numbers are used for live events to identify items during the auctioneer's presentation. Assign sequential numbers before the event starts. Items are sorted by lot number in the auctioneer panel and on the projector display.

---

## Managing Users

### Viewing bidder profiles

1. Go to **Admin → Users**
2. You can search by name or email
3. Click a user's name to view their full profile, including:
   - Registration date
   - Email verification status
   - Bid history
   - Payment status

### Changing user roles

From a user's profile page (**Admin → Users → [User Name]**):

1. Select the new role from the **Role** dropdown:
   - **Bidder** — standard registered user, can bid
   - **Donor** — can submit items; can also bid
   - **Admin** — full admin access (payments, gift aid, and settings excluded)

> The Admin option in the dropdown is only visible to super admin users. Regular admins can only set Bidder or Donor.

2. Click **Save**

> Be careful when granting admin access. Admin accounts can manage auctions, items, users, and live events.

### Changing a user's email address

From a user's profile page (**Admin → Users → [User Name]**):

1. Enter the new email address in the **Change Email** field
2. Click **Save**

The user's account is immediately marked as unverified and a verification email is sent to the new address. They must click the link in that email before they can bid again.

> This form is not available for admin accounts. Admin users manage their own email via Account Settings.

### Deleting a user

The delete action permanently erases a user and all their personal data in compliance with GDPR Article 17 (right to erasure).

From the users list (**Admin → Users**):

1. Click the red **Delete** button next to the user's row
2. A confirmation panel will appear showing the user's name and email
3. Click **Yes, delete permanently** to proceed, or **Cancel** to go back

**What gets deleted:**

- The user account and all personal details
- All bids placed by the user
- Donated items not in an active auction (and all bids on those items)
- Payment records

**What gets anonymised instead of deleted:**

- Items the user donated to a currently **active** auction — these keep running with the donor link removed (donor shown as anonymous)

> **This action cannot be undone.** There is no recovery path once confirmed.

> The Delete button is not shown for super admin accounts.
>
> Admin accounts can only be deleted by a super admin. Regular admins do not see the Delete button for admin accounts.

---

## Payments

### Overview of payment flow

When an auction ends:

1. The system identifies winners for each item
2. Winners receive an email with a link to pay
3. The winner visits the payment page and is redirected to Stripe Checkout
4. Stripe processes the payment and sends a webhook to confirm
5. The app marks the payment as `completed` and the item as `sold`

### Payment statuses

| Status | Meaning |
|--------|---------|
| `pending` | Payment record created but winner has not yet paid |
| `completed` | Payment received via Stripe Checkout |
| `failed` | Stripe returned a failure event |
| `refunded` | Payment has been refunded (manual process) |

### Viewing payments

Go to **Admin → Payments** to see all payment records with their current status, winner name, item, and amount.

### What to do when a payment is stuck

If a payment stays in `pending` status for more than a few days:

1. Check that the winner received their email (Admin → Users → check their email)
2. Resend the payment link manually by copying the URL: `https://yourdomain.com/payment/[item-slug]`
3. Contact the winner directly using their registered email address

### Refunds

The app does not process refunds automatically. To refund a payment:

1. Log in to your **Stripe Dashboard** at dashboard.stripe.com
2. Find the payment by the Stripe Payment Intent ID (visible in Admin → Payments)
3. Issue the refund from the Stripe Dashboard
4. Manually update the payment status in the database, or contact your developer

---

## Gift Aid

### What is Gift Aid?

Gift Aid is a UK government scheme that allows UK-registered charities to claim an extra 25p for every £1 donated. In an auction context, Gift Aid can be claimed on the portion of a winning bid that exceeds the market value of the item — this is the "Gift Aid-able" donation element.

For example: if a bidder wins an item with a market value of £500 and bids £800, the charity can claim Gift Aid on the £300 difference.

To claim Gift Aid, the winning bidder must:
- Be a UK taxpayer
- Have made a Gift Aid declaration

### Enabling Gift Aid for a bidder

Bidders can opt in to Gift Aid and provide their declaration during registration or in their account settings. Admins can also update this from **Admin → Users → [User Name] → Gift Aid Eligible**.

### Downloading the HMRC Gift Aid report

1. Go to **Admin → Gift Aid**
2. Select the date range or auction event
3. Click **Download CSV**
4. The CSV is formatted to HMRC's Gift Aid submission requirements, including:
   - Donor name
   - Donor address (if provided)
   - Amount donated (bid minus market value)
   - Date of donation

Submit this file to HMRC via the Charities Online service at: https://www.gov.uk/claim-gift-aid

### Declaration requirements

To claim Gift Aid, you need a valid Gift Aid declaration from each donor. The app records a declaration when a bidder:

1. Ticks the Gift Aid checkbox and confirms they are a UK taxpayer
2. Provides their full name (as it appears on their tax record)

The declaration is time-stamped and stored against the user record. Keep these records for at least 6 years as required by HMRC.

---

## Live Events — Auctioneer Panel

The live event feature is designed for in-person auctions where an auctioneer manages bidding from a control panel while the current item is displayed on a large screen.

### Before the event

1. Ensure the auction event is set to **Active** status (or publish it so you can open it)
2. Go to **Admin → Live Events** and confirm the event is ready
3. Assign lot numbers to all items (Admin → Items)
4. Check that the projector URL is accessible: `https://yourdomain.com/projector`

### Starting the live event

1. Go to **Admin → Live Events → Start**
2. The auctioneer panel is available at: `https://yourdomain.com/auctioneer`
3. Share the projector URL with your AV team: `https://yourdomain.com/projector`

### Using the auctioneer panel

The auctioneer panel at `/auctioneer` provides:

- A list of all items in lot-number order
- Controls to set the **current item** (the one on display)
- **Open Bidding** / **Close Bidding** buttons for each item
- Live bid count and current bid for the active item

**Workflow for each lot:**

1. Click **Set as Current Item** to display the item on the projector
2. Click **Open Bidding** when ready — bidders can now bid from their phones
3. Watch bids come in live (the panel auto-refreshes)
4. When the hammer falls, click **Close Bidding** to end bidding on this item
5. Announce the winner, then move to the next lot

### The projector display

The projector view at `/projector` is a clean, full-screen display showing:

- The current item's image and title
- The current highest bid (updated every few seconds via polling)
- A QR code for bidders to scan and place their own bids

Share this URL with your AV team before the event. They can open it on a laptop connected to the projector. No login is required for the projector view.

---

## Settings

### Accessing settings

Go to **Admin → Settings**. Settings are stored in the database and take effect immediately without a server restart.

### Stripe configuration

Before you can accept payments, you must configure your Stripe keys:

1. Log in to [dashboard.stripe.com](https://dashboard.stripe.com)
2. Navigate to **Developers → API keys**
3. Copy your **Publishable key** (`pk_live_...` or `pk_test_...` for testing)
4. Copy your **Secret key** (`sk_live_...` or `sk_test_...`)
5. In Admin → Settings:
   - Paste the Publishable key into **Stripe Publishable Key**
   - Paste the Secret key into **Stripe Secret Key**

> Use `pk_test_` and `sk_test_` keys while testing. Switch to live keys only when you are ready to accept real payments.

### Stripe webhook setup

The webhook tells the app when a payment has been completed. You need to set this up in both Stripe and the app settings.

**Step 1: Generate a webhook token**

Generate a secure random token. You can use your terminal:

```bash
openssl rand -hex 32
```

This produces something like: `a3f8b2c1d4e5f67890abcdef1234567890abcdef1234567890abcdef12345678`

**Step 2: Configure in Admin → Settings**

Paste the token into **Stripe → Webhook URL Token** and click Save.

**Step 3: Configure in Stripe Dashboard**

1. Go to **Stripe Dashboard → Developers → Webhooks**
2. Click **Add endpoint**
3. Enter the URL:
   ```
   https://yourdomain.com/webhook/stripe?webhook_secret=YOUR_TOKEN
   ```
   Replace `YOUR_TOKEN` with the token you generated in Step 1.
4. Under **Events to listen for**, select: `checkout.session.completed`
5. Click **Add endpoint**
6. Note the `whsec_...` signing secret that Stripe shows you — you do not need this (the app uses the URL token instead)

> **Why a URL token instead of the Stripe signature?** Some server configurations (including Galvani and certain reverse proxies) strip custom HTTP headers. Stripe normally sends a `Stripe-Signature` header, but the app cannot reliably read it. Instead, the app uses a shared secret in the URL query string to verify webhook authenticity.

**Step 4: Test the webhook**

In the Stripe Dashboard, find your webhook endpoint and click **Send test event**. Select `checkout.session.completed`. The app should return `200 OK`.

### SMTP email setup

Email is used for:
- Email verification on registration
- Password reset links
- Donor item submission — admin notification (with thumbnail) and donor thank-you
- Item approval and rejection notifications to donors
- Payment requests to auction winners
- Admin notifications

Configure SMTP in **Admin → Settings → Email**:

| Field | Description | Example |
|-------|-------------|---------|
| SMTP Host | Your mail server | `smtp.mailgun.org` |
| SMTP Port | Usually 587 (TLS) or 465 (SSL) | `587` |
| Username | Your SMTP username | `postmaster@mg.yourdomain.com` |
| Password | Your SMTP password | (your mail provider password) |
| From Address | The sender email address | `noreply@wellfoundation.org.uk` |
| From Name | The sender display name | `WFCS Auction` |

> For testing, services like [Mailtrap](https://mailtrap.io) or [Mailpit](https://github.com/axllent/mailpit) let you capture outgoing emails without delivering them to real addresses.

### Saving settings

All settings forms have a **Save** button. Settings take effect immediately.

---

## Charity Information

**The Well Foundation (WFCS)**
Building 2, Unit C, Ground Floor
4 Parklands Way, Eurocentral
Holytown, ML1 4WR

Registered office: 211B Main Street, Bellshill, ML4 1AJ, Scotland
Charity No: SC040105

Contact: info@wellfoundation.org.uk
