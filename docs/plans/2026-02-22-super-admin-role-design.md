# Super Admin Role — Design

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `super_admin` role that has all admin capabilities. The existing `admin` role retains all current access except payments, gift aid, and settings (web + API).

**Architecture:** Extend the `role` ENUM to include `super_admin`. Hierarchy lives in a single PHP `roleLevel()` helper function that maps role strings to integers (0–3). All access checks use `roleLevel >= N` comparisons. The `AdminApiController` mirrors the same pattern for API routes. The MCP server is updated to match.

**Tech Stack:** MariaDB ENUM · PHP MVC · Vanilla JS · TypeScript MCP server

---

## Role Hierarchy

| Role        | Level | Admin panel | Payments / Gift Aid / Settings | Delete admins |
|-------------|-------|-------------|-------------------------------|---------------|
| bidder      | 0     | No          | No                             | No            |
| donor       | 1     | No          | No                             | No            |
| admin       | 2     | Yes         | **No**                         | No            |
| super_admin | 3     | Yes         | **Yes**                        | Yes           |

---

## Access Control Rules

### Web routes restricted to super_admin only
- `GET /admin/payments`
- `GET /admin/gift-aid`
- `POST /admin/gift-aid/export` *(also: register this route — currently missing from index.php)*
- `GET /admin/settings`
- `POST /admin/settings`

### Web routes accessible to admin + super_admin (unchanged)
- All other `/admin/*` routes

### User deletion guards
- Regular admin: can delete bidders and donors (unchanged)
- Super admin: can delete bidders, donors, **and admins**
- Nobody: can delete super_admins (blocked)

### Role change (admin users panel)
- Acting admin (level 2): can set bidder or donor only
- Acting super_admin (level 3): can set bidder, donor, or admin
- Nobody can set super_admin via UI (requires direct DB access)

### Email change guard
- Currently blocked for `role === 'admin'`
- Extend to block for `role === 'super_admin'` too

### Admin nav
- Payments, Gift Aid, Settings tabs: visible only when `roleLevel >= 3`

### API endpoints restricted to super_admin only
- `GET /api/admin/v1/payments`
- `GET /api/admin/v1/gift-aid`
- `GET /api/admin/v1/settings`
- `PUT /api/admin/v1/settings`

### API endpoints accessible to admin + super_admin
- All other `/api/admin/v1/*` routes (including reports)

---

## Files Changed

### Database
- `database/schema.sql` — extend ENUM to `'bidder','donor','admin','super_admin'`
- `database/seeds.sql` — add 2 super_admin users (passwords hashed via Galvani)
- `database/migrations/001-super-admin-role.sql` — ALTER TABLE + INSERT for production

### PHP helpers
- `app/Helpers/functions.php`
  - Add `roleLevel(string $role): int`
  - Update `requireAdmin()` to check `roleLevel >= 2`
  - Add `requireSuperAdmin()` to check `roleLevel >= 3`

### Controllers
- `app/Controllers/AdminController.php`
  - `payments()`, `giftAid()`, `exportGiftAid()`, `settings()`, `saveSettings()` → `requireSuperAdmin()`
  - `deleteUser()` — block delete of super_admin; allow super_admin to delete admins
  - `updateUser()` — role change: use `roleLevel` to determine allowed target roles; block email change for super_admin profiles
- `app/Controllers/AdminApiController.php`
  - `requireAdmin()` → check `roleLevel >= 2`
  - Add `requireSuperAdmin()` → check `roleLevel >= 3`
  - `listPayments()`, `giftAidOverview()`, `getSettings()`, `updateSettings()` → `$this->requireSuperAdmin()`
  - `deleteUser()` — same guards as web controller
  - `updateUser()` — same role change rules as web controller

### index.php
- Register missing route: `POST /admin/gift-aid/export`
- No other route changes needed (roles handled inside controllers)

### Views
- `app/Views/partials/header-admin.php`
  - Hide Payments, Gift Aid, Settings nav items when `roleLevel($user['role']) < 3`
- `app/Views/admin/users.php`
  - Delete button on admin rows: show only when acting user is super_admin (`roleLevel >= 3`)
  - Role badge: add super_admin styling
  - Stats: rename "Admins" count or add super_admin count
- `app/Views/admin/user-detail.php`
  - Role dropdown: show `admin` option only when acting user is super_admin
  - Role change form: also show for admin-role profiles (when acting user is super_admin)
  - Email change form: also block for super_admin profiles
  - Delete button: show for admin profiles when acting user is super_admin

### Auth
- `app/Views/auth/login.php`
  - Remove "Dev quick-login" block (lines ~188–205) and `devLogin()` JS function

### MCP server
- `mcp/packages/server/src/types.ts` — add `'super_admin'` to `Role` type
- `mcp/packages/server/src/index.ts`
  - Admin tool registration: `role === 'admin' || role === 'super_admin'` for most tools
  - Payments, gift-aid, settings tools: `role === 'super_admin'` only
  - Reports: accessible to both admin and super_admin
  - Prompts: register for admin and super_admin
  - `role-permissions` resource: add super_admin entry
- `mcp/packages/server/src/api-client.ts` — no changes needed (API auth handles it)

### MCP docs
- `mcp/auction-mcp-docs/admin/index.html` — note role restrictions; update tool table
- `docs/admin/README.md` — document super_admin role, restricted sections, role hierarchy
