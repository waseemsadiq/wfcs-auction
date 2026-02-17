# View Partials & Component Specification

> **For implementation agents:** Every page MUST use these partials and atoms. Do not inline what's here. Do not invent alternatives. When in doubt, check the approved mockups in `docs/mockups/`.

---

## Architecture Overview

Three tiers:

- **Layouts** — full-page wrappers (head + header + footer + JS boilerplate)
- **Partials** — sizeable reusable sections (header, footer, mobile menu, toast)
- **Atoms** — the smallest indivisible UI elements (button, input, toggle, badge, etc.)

Atoms are included inside partials and views. Partials are included inside layouts. Views only contain page-specific content.

```
app/Views/
├── layouts/
│   ├── public.php        # Wraps all public-facing pages
│   └── admin.php         # Wraps all admin pages
├── partials/
│   ├── head.php                 # <head> element + shared CSS
│   ├── header-public.php        # Public header (logged-in/out states)
│   ├── header-admin.php         # Admin header + sub-nav
│   ├── mobile-menu.php          # Mobile overlay + sidebar (public only)
│   ├── footer.php               # Full Well Foundation footer (all pages)
│   ├── toast.php                # Toast notification HTML
│   ├── scripts-dark-mode.php    # Dark mode JS
│   └── scripts-mobile-menu.php  # Mobile menu JS (public only)
├── atoms/
│   ├── button.php         # All button variants
│   ├── input.php          # Text/email/password/tel/number inputs
│   ├── label.php          # Form field label
│   ├── select.php         # Dropdown select
│   ├── textarea.php       # Textarea
│   ├── toggle.php         # Toggle switch
│   ├── file-upload.php    # Drag-and-drop file zone
│   ├── badge.php          # Status / category / role badges
│   ├── stat-card.php      # Icon + label + value card
│   ├── item-card.php      # Auction item card (home / my-bids)
│   ├── page-header.php    # Page title + subtitle + action buttons
│   ├── breadcrumb.php     # Breadcrumb trail
│   ├── alert.php          # Inline info/success/warning/error box
│   ├── empty-state.php    # Empty list placeholder
│   ├── table-wrapper.php  # Admin table chrome (border, rounded, overflow)
│   └── popover-shell.php  # Popover API wrapper + standard header/footer
└── [page views]
```

---

## 1. Layouts

Layouts call `$content` to render the page body. Controllers pass variables into the view; the layout passes them through to partials.

### `layouts/public.php`

```php
<?php include 'partials/head.php'; ?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col transition-colors duration-300">
<?php include 'partials/header-public.php'; ?>
<?php include 'partials/mobile-menu.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-10">
  <?= $content ?>
</main>
<?php include 'partials/footer.php'; ?>
<?php include 'partials/toast.php'; ?>
<script>
<?php include 'partials/scripts-dark-mode.php'; ?>
<?php include 'partials/scripts-mobile-menu.php'; ?>
<?= $pageScripts ?? '' ?>
</script>
</body>
```

**Variables passed in:**
- `$pageTitle` — browser tab title
- `$user` — authenticated user object or `null` (logged out)
- `$activeNav` — one of: `'auctions'`, `'my-bids'`, `'donate'`
- `$content` — rendered page HTML
- `$pageScripts` — optional inline JS specific to this page
- `$mainWidth` — optional override: `'max-w-4xl'` or `'max-w-xl'` (default `max-w-6xl`)

> **Content widths:** 3 sizes only.
> - `max-w-6xl` — default: home, item-show, my-bids, submit-item
> - `max-w-4xl` — auth/legal/account: login, register, forgot-password, reset-password, account-settings, terms, privacy
> - `max-w-xl` — checkout only: payment-checkout

### `layouts/admin.php`

```php
<?php include 'partials/head.php'; ?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col transition-colors duration-300">
<?php include 'partials/header-admin.php'; ?>
<main class="flex-1 max-w-7xl mx-auto w-full px-6 py-8">
  <?= $content ?>
</main>
<?php include 'partials/footer.php'; ?>
<?php include 'partials/toast.php'; ?>
<script>
<?php include 'partials/scripts-dark-mode.php'; ?>
<?= $pageScripts ?? '' ?>
</script>
</body>
```

**Variables:**
- `$pageTitle`, `$user`, `$content`, `$pageScripts`
- `$activeNav` — one of: `'dashboard'`, `'auctions'`, `'items'`, `'users'`, `'payments'`, `'gift-aid'`, `'live-events'`, `'settings'`

---

## 2. Partials

### `partials/head.php`

Contains: `<head>` open through `</head>`. Includes:
- charset, viewport, title (`$pageTitle`), favicon
- `../../css/output.css` link
- Shared `<style>` block:
  - `::view-transition` rules
  - `.nav-link` underline animation
  - `.toggle-input` / `.toggle-track` / `.toggle-knob` CSS (including explicit unchecked state)
  - Toast CSS (`#toast`, `#toast.show`, `@keyframes shrink`, `#toast-progress.running`)
  - `.form-popover::backdrop` and `.form-popover:popover-open` CSS
  - Scrollbar styles

> **Critical toggle CSS** — must include the explicit unchecked state or toggles slide the wrong way:
> ```css
> .toggle-input ~ .toggle-track .toggle-knob { transform: translateX(0); }
> .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
> .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }
> ```

---

### `partials/header-public.php`

**Logged-out state** (`$user === null`):
```
[Logo h-14] [Nav: Auctions | My Bids | Donate an Item] [Sign in] [Register] [theme toggle] [hamburger md:hidden]
```

**Logged-in state** (`$user` is set):
```
[Logo h-14] [Nav: Auctions | My Bids | Donate an Item] [user pill] [Sign out] [theme toggle] [hamburger md:hidden]
```

- Header: `bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/30 sticky top-0 z-40 h-20`
- Dual logos: `logo-blue.svg dark:hidden` + `logo-white.svg hidden dark:block`, size `h-14 w-auto`
- Nav links: `text-sm font-semibold uppercase tracking-widest` with `.nav-link` underline animation, active link uses `text-primary`
- User pill: `bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg px-3 py-1.5` with initial avatar circle + name
- Theme toggle: moon/sun SVG icons (id="iconMoon"/"iconSun"), `onclick="toggleDarkMode(event)"`

---

### `partials/header-admin.php`

```
[Logo h-9] [sub-nav: Dashboard|Auctions|Items|Users|Payments|Gift Aid|Live Events|Settings] [username] [Logout] [theme toggle]
```

- Header: `bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/30 sticky top-0 z-40`
- Dual logos: `logo-blue.svg dark:hidden` + `logo-white.svg hidden dark:block`, size `h-9 w-auto`
- Sub-nav: scrollable, `scrollbar-width: none` CSS, active item has `text-primary` + underline
- Active item determined by `$activeNav`
- Sub-nav links: Dashboard→admin-dashboard, Auctions→admin-auctions, Items→admin-items, Users→admin-users, Payments→admin-payments, Gift Aid→admin-gift-aid, Live Events→admin-live-event-settings, Settings→admin-settings
- No hamburger on admin

---

### `partials/mobile-menu.php`

Public pages only. Two elements:
1. Overlay: `<div id="mobile-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden" onclick="toggleMenu()">`
2. Sidebar: `<div id="mobile-menu" class="fixed top-0 right-0 h-full w-72 bg-white dark:bg-slate-800 z-50 shadow-2xl translate-x-full border-l border-slate-200 dark:border-slate-700 transition-transform duration-300 ease-in-out">`

> **Critical:** sidebar MUST have `transition-transform duration-300 ease-in-out` as Tailwind classes. NOT in a CSS `<style>` block. NOT `style.transform`. The JS uses `classList.toggle('translate-x-full', !menuOpen)`.

---

### `partials/footer.php`

Full Well Foundation footer — identical on public and admin pages:

```
© 2026 The Well Foundation. Building 2, Unit C, Ground Floor, 4 Parklands Way, Eurocentral, Holytown, ML1 4WR
Registered office: 211B Main Street, Bellshill, ML4 1AJ, Scotland. Charity Registration No. SC040105
[Terms] [Privacy]
Made with [outlined rose-400 heart SVG] by Waseem  ← links to waseemsadiq.com
```

- Background: `bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700/30`
- Heart: outlined SVG `fill="none" stroke="currentColor" stroke-width="1.75" class="text-rose-400"` — NOT filled, NOT emoji

---

### `partials/toast.php`

Full toast HTML. No simplifications. Exact pattern:

```html
<div id="toast" class="shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 overflow-hidden">
  <div id="toast-body" class="flex items-start gap-3 px-4 py-3.5 bg-white dark:bg-slate-800 border-l-4 border-l-green-500">
    <svg id="toast-icon" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" ...><!-- circle-check --></svg>
    <p id="toast-message" class="text-sm font-medium text-slate-700 dark:text-slate-200 flex-1 leading-snug"></p>
    <button onclick="hideToast()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors flex-shrink-0">
      <svg class="w-4 h-4" ...><!-- × --></svg>
    </button>
  </div>
  <div id="toast-progress" class="h-0.5 w-full origin-left bg-green-500"></div>
</div>
```

- NO `rounded-xl` on outer div — sharp corners
- NO Tailwind animation classes — CSS-only via `#toast` / `#toast.show`

---

### `partials/scripts-dark-mode.php`

The complete dark mode JS — init IIFE + getCookie + performThemeToggle + updateDarkIcon + toggleDarkMode (with View Transitions API circle animation). See any approved mockup for the exact code.

---

### `partials/scripts-mobile-menu.php`

```js
const mobileMenu = document.getElementById('mobile-menu');
const mobileOverlay = document.getElementById('mobile-overlay');
let menuOpen = false;
function toggleMenu() {
  menuOpen = !menuOpen;
  mobileMenu.classList.toggle('translate-x-full', !menuOpen);
  mobileOverlay.classList.toggle('hidden', !menuOpen);
}
```

---

## 3. Atoms

Each atom is a PHP file in `app/Views/atoms/`. It reads variables from a `$props` array passed before including it:

```php
$props = ['variant' => 'primary', 'label' => 'Save changes'];
include VIEW_PATH . '/atoms/button.php';
```

Or via a thin helper so it's one line in a view:

```php
atom('button', ['variant' => 'primary', 'label' => 'Save changes']);
```

Every atom must support light **and** dark mode. No hardcoded dark-only colours.

---

### `atoms/stat-card.php`

Used on: dashboard, my-bids

```php
atom('stat-card', [
  'icon'     => '<svg...>',       // SVG markup
  'color'    => 'blue',           // blue | green | amber | purple
  'label'    => 'Active Bids',
  'value'    => '3',
  'subtitle' => 'across 2 auctions', // optional
]);
```

Output structure:
```html
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 px-6 py-5">
  <div class="w-12 h-12 rounded-xl bg-{color}-100 dark:bg-{color}-900/30 flex items-center justify-center mb-4">
    <svg class="w-6 h-6 text-{color}-600 dark:text-{color}-400">...</svg>
  </div>
  <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">{label}</p>
  <p class="text-3xl font-black text-slate-900 dark:text-white">{value}</p>
  <p class="text-sm text-slate-400 mt-0.5">{subtitle}</p>
</div>
```

---

### Status Badge

Used everywhere: tables, item cards, bid rows.

```php
atom('badge', ['status' => $status]);
// $status: 'active' | 'pending' | 'sold' | 'paid' | 'unpaid' | 'leading' | 'outbid' | 'ended' | 'admin' | 'bidder' | 'donor'
```

Color map:
| Status | Light | Dark |
|--------|-------|------|
| active / leading / paid | `bg-green-100 text-green-700` | `dark:bg-green-900/30 dark:text-green-400` |
| pending | `bg-amber-100 text-amber-700` | `dark:bg-amber-900/30 dark:text-amber-400` |
| sold / ended | `bg-slate-100 text-slate-600` | `dark:bg-slate-700 dark:text-slate-300` |
| outbid / unpaid | `bg-red-100 text-red-700` | `dark:bg-red-900/30 dark:text-red-400` |
| admin | `bg-red-100 text-red-700` | same |
| bidder | `bg-blue-100 text-blue-700` | same |
| donor | `bg-teal-100 text-teal-700` | same |

Output: `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ...">{label}</span>`

---

### Category Badge

Used on: item cards, item-show header.

```php
atom('badge', ['variant' => 'category', 'label' => $category]);
```

Output: `<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary text-white backdrop-blur-sm">{category}</span>`

> Always solid blue (`bg-primary text-white`) — NOT `bg-primary/10 text-primary`

---

### Form Input

Used on: all forms.

```php
atom('input', [
  'label'       => 'Email address',
  'name'        => 'email',
  'type'        => 'email',        // text | email | password | tel | number
  'value'       => $old['email'] ?? '',
  'placeholder' => 'you@example.com',
  'required'    => true,
  'help'        => 'We\'ll never share your email.', // optional
  'error'       => $errors['email'] ?? null,          // optional
]);
```

Input classes: `w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors`

---

### Toggle Switch

Used on: settings pages, Gift Aid, auto-payments.

```php
atom('toggle', [
  'id'      => 'email-notifications',
  'name'    => 'email_notifications',
  'checked' => $settings['email_notifications'] ?? false,
  'color'   => 'blue',   // blue (default) | green (auto-payments)
  'label'   => 'Email notifications', // optional visible label
]);
```

> Must include all three CSS rules (checked track, checked knob, explicit unchecked knob). See head.php.

---

### Popover Wrapper

Used on: all create/edit/view dialogs. No exceptions — never use `alert()`, `confirm()`, custom overlay divs.

```php
atom('popover-shell', [
  'id'    => 'create-item-popover',
  'width' => '36rem',   // optional, default '36rem'
  'title' => 'Add Item',
  'body'  => $bodyHtml,   // rendered inner HTML
  'footer' => $footerHtml, // rendered footer actions HTML
]);
// outputs: <div id="{id}" popover="manual" style="position:fixed;inset:0;width:min({width},calc(100% - 2rem));..." class="form-popover ...">
```

Structure inside every popover:
```html
<!-- Header -->
<div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-700/40 flex-shrink-0">
  <h2 class="text-base font-bold text-slate-900 dark:text-white">{title}</h2>
  <button onclick="document.getElementById('{id}').hidePopover()" ...><!-- × --></button>
</div>
<!-- Scrollable body -->
<div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
  {content}
</div>
<!-- Footer actions -->
<div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700/40 flex justify-end gap-3 flex-shrink-0">
  <button onclick="document.getElementById('{id}').hidePopover()" ...>Cancel</button>
  <button type="submit" ...>Save</button>
</div>
```

---

### Page Header

Used on admin pages with an action button.

```php
atom('page-header', [
  'title'    => 'Users',
  'subtitle' => '142 registered accounts',
  'actions'  => '<button popovertarget="invite-popover" ...>Invite Donor</button>', // optional
]);
```

Output:
```html
<div class="flex items-start justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-black text-slate-900 dark:text-white">{title}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{subtitle}</p>
  </div>
  <div class="flex items-center gap-3">{actions}</div>
</div>
```

---

### Admin Table Wrapper

Consistent table chrome used on every admin list page.

```php
// Wrap the <table> element with this atom — pass the full table markup as $content:
atom('table-wrapper', ['content' => $tableHtml]);
```

Output:
```html
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[820px]">...</table>
  </div>
</div>
```

Table header cells: `text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3 border-b border-slate-100 dark:border-slate-700/40`

Table body rows: `hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border-b border-slate-50 dark:border-slate-700/30`

---

### Alert / Info Box

Used for inline contextual messages.

```php
atom('alert', [
  'type'    => 'info',   // info | success | warning | error
  'message' => 'Your item has been submitted for review.',
  'icon'    => '<svg...>', // optional custom icon
]);
```

Color map:
| Type | Background | Border | Text | Icon |
|------|-----------|--------|------|------|
| info | `bg-primary/5 dark:bg-primary/10` | `border-primary/20` | slate | primary |
| success | `bg-green-50 dark:bg-green-900/15` | `border-green-200 dark:border-green-700/30` | green | green |
| warning | `bg-amber-50 dark:bg-amber-900/15` | `border-amber-200 dark:border-amber-700/30` | amber | amber |
| error | `bg-red-50 dark:bg-red-900/15` | `border-red-200 dark:border-red-700/30` | red | red |

---

### Empty State

Used when a list/table has no data.

```php
atom('empty-state', [
  'icon'        => '<svg...>',
  'title'       => 'No items yet',
  'description' => 'Items submitted for auction will appear here.',
  'action'      => '<a href="...">Add first item</a>', // optional
]);
```

Output: centered flex column, `py-20`, icon in `w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700`.

---

### Breadcrumb

Used on: item-show, any deep-linked public page.

```php
atom('breadcrumb', [
  'items' => [
    ['label' => 'Auctions', 'url' => '/auctions'],
    ['label' => 'Gleneagles Golf Round for Four'], // last item = no url
  ],
]);
```

---

## 4. Auctioneer Panel & Projector

These two pages are standalone — no layout wrapper. They use no header, footer, or standard nav. Build them as self-contained views that include only:
- `partials/head.php`
- `partials/scripts-dark-mode.php`
- Their own page-specific JS

---

## 5. What NEVER Goes Inline

If you are about to write any of the following directly in a view file, stop and use the partial/helper instead:

| Don't inline | Use instead |
|---|---|
| `<header>` markup | `header-public.php` or `header-admin.php` |
| `<footer>` markup | `footer.php` |
| Toast HTML | `toast.php` |
| Dark mode JS | `scripts-dark-mode.php` |
| `#mobile-menu` + overlay | `mobile-menu.php` |
| `alert()` / `confirm()` / custom overlay div | Popover API — `form-popover` pattern |
| `bg-primary/10 text-primary` on category badges | `bg-primary text-white` |
| A 6th content width | Only `max-w-6xl` / `max-w-4xl` / `max-w-xl` |
| Filled heart ❤️ in footer | Outlined SVG `stroke="currentColor" fill="none"` |
| `style.transform` for mobile menu | `classList.toggle('translate-x-full', ...)` |
| CSS `#mobile-menu { transition: ... }` in `<style>` | Tailwind classes on the element |
