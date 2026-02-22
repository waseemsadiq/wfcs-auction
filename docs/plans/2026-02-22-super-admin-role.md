# Super Admin Role — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `super_admin` role above `admin`; restrict payments/gift-aid/settings access to super_admin only; seed 2 super_admin accounts; remove login page dev quick-login links; update MCP server and docs.

**Architecture:** Extend the `role` ENUM to include `super_admin`. A PHP `roleLevel()` helper maps role strings to integers (0–3). All access checks use `roleLevel >= N`. AdminApiController mirrors the same pattern. MCP types and tool registration updated to match.

**Tech Stack:** MariaDB ENUM · PHP MVC · Vanilla JS · TypeScript MCP server

**Critical rules (read before touching any file):**
- NEVER `new Database()` — always `Database::getInstance()`
- NEVER `beginTransaction()` / `commit()` — autocommit only
- NEVER `LIMIT ?` — always `'LIMIT ' . (int)$n`
- NEVER `parent::__construct()` in any controller — `Core\Controller` has no constructor
- SQL belongs in Repositories only — never in Services or Controllers
- Tests run via: `./galvani auction/run-tests.php -- tests/Unit/ --no-coverage` (NEVER `vendor/bin/phpunit` directly — Galvani can't strip the shebang)

---

### Task 1: Database — extend ENUM, migration file, seeds

**Files:**
- Modify: `database/schema.sql:11`
- Create: `database/migrations/001-super-admin-role.sql`
- Modify: `database/seeds.sql` (add 2 super_admin users near the top, after existing admin seed)

**Step 1: Update schema.sql line 11**

Change:
```sql
  role ENUM('bidder','donor','admin') NOT NULL DEFAULT 'bidder',
```
To:
```sql
  role ENUM('bidder','donor','admin','super_admin') NOT NULL DEFAULT 'bidder',
```

**Step 2: Create migration file for production**

Create `database/migrations/001-super-admin-role.sql`:
```sql
-- Migration: add super_admin role (run once on production)
-- Safe to run multiple times — IF NOT EXISTS guards the INSERT.

ALTER TABLE users
  MODIFY COLUMN role ENUM('bidder','donor','admin','super_admin') NOT NULL DEFAULT 'bidder';

-- Insert super_admin users (if they don't already exist)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at)
SELECT 'waseem-sadiq', 'Waseem Sadiq', 'admin@wfcs.co.uk',
       '$2y$12$ue9Dsx1cea8oAseGKuEelO3J1ahBMLhcVRKAow/sNXjR16FNB7GvG',
       'super_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@wfcs.co.uk');

INSERT INTO users (slug, name, email, password_hash, role, email_verified_at)
SELECT 'fahim-baqir', 'Fahim Baqir', 'fahimbaqir@gmail.com',
       '$2y$12$zGJ0lmQryVjtD8wS.WajPeojA67ipZ4/4NnBQ13Y6BBYtyMCEdlYi',
       'super_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'fahimbaqir@gmail.com');
```

**Step 3: Add super_admin users to seeds.sql**

In `database/seeds.sql`, after the existing admin user block (around line 23), add:
```sql
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('waseem-sadiq', 'Waseem Sadiq', 'admin@wfcs.co.uk',
   '$2y$12$ue9Dsx1cea8oAseGKuEelO3J1ahBMLhcVRKAow/sNXjR16FNB7GvG',
   'super_admin', NOW(), 0, NULL);

INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('fahim-baqir', 'Fahim Baqir', 'fahimbaqir@gmail.com',
   '$2y$12$zGJ0lmQryVjtD8wS.WajPeojA67ipZ4/4NnBQ13Y6BBYtyMCEdlYi',
   'super_admin', NOW(), 0, NULL);
```

**Step 4: Verify**

Run db-init to rebuild dev DB from new schema:
```bash
# From /Users/waseem/Sites/www/
./galvani auction/db-init.php
```
Expected: no errors. Check output for "super_admin" mention or confirm no SQL errors.

**Step 5: Commit**
```bash
git add database/schema.sql database/seeds.sql database/migrations/001-super-admin-role.sql
git commit -m "feat: add super_admin to role ENUM with migration and seeds"
```

---

### Task 2: roleLevel() helper + requireSuperAdmin() + tests

**Files:**
- Create: `tests/Unit/RoleLevelTest.php`
- Modify: `app/Helpers/functions.php` (around line 103 — `requireAdmin` function)

**Step 1: Write the failing tests**

Create `tests/Unit/RoleLevelTest.php`:
```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RoleLevelTest extends TestCase
{
    public function testSuperAdminLevel(): void
    {
        $this->assertSame(3, roleLevel('super_admin'));
    }

    public function testAdminLevel(): void
    {
        $this->assertSame(2, roleLevel('admin'));
    }

    public function testDonorLevel(): void
    {
        $this->assertSame(1, roleLevel('donor'));
    }

    public function testBidderLevel(): void
    {
        $this->assertSame(0, roleLevel('bidder'));
    }

    public function testUnknownRoleDefaultsToZero(): void
    {
        $this->assertSame(0, roleLevel(''));
        $this->assertSame(0, roleLevel('wizard'));
    }
}
```

**Step 2: Run tests — expect failure**
```bash
./galvani auction/run-tests.php -- tests/Unit/RoleLevelTest.php --no-coverage
```
Expected: FAIL — "Call to undefined function roleLevel()"

**Step 3: Add roleLevel() to functions.php**

In `app/Helpers/functions.php`, add this function **before** `requireAdmin()` (around line 100):
```php
/**
 * Map a role string to its numeric hierarchy level.
 * Use >= comparisons: >= 2 = admin or above, >= 3 = super_admin only.
 */
function roleLevel(string $role): int
{
    return match($role) {
        'super_admin' => 3,
        'admin'       => 2,
        'donor'       => 1,
        'bidder'      => 0,
        default       => 0,
    };
}
```

**Step 4: Update requireAdmin() to use roleLevel**

The existing `requireAdmin()` (around line 103) currently checks `$user['role'] !== 'admin'`.
Change it to accept both admin and super_admin:
```php
function requireAdmin(): array
{
    global $basePath;
    $user = requireAuth();
    if (roleLevel($user['role'] ?? '') < 2) {
        http_response_code(403);
        $errorView = dirname(__DIR__, 2) . '/app/Views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo 'Forbidden';
        }
        exit;
    }
    return $user;
}
```

**Step 5: Add requireSuperAdmin() immediately after requireAdmin()**
```php
/**
 * Require a super_admin user. Renders 403 on failure.
 */
function requireSuperAdmin(): array
{
    global $basePath;
    $user = requireAuth();
    if (roleLevel($user['role'] ?? '') < 3) {
        http_response_code(403);
        $errorView = dirname(__DIR__, 2) . '/app/Views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo 'Forbidden';
        }
        exit;
    }
    return $user;
}
```

**Step 6: Run tests — expect pass**
```bash
./galvani auction/run-tests.php -- tests/Unit/RoleLevelTest.php --no-coverage
```
Expected: 5 tests, 5 assertions, PASS.

**Step 7: Run full suite to confirm no regressions**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 8: Commit**
```bash
git add tests/Unit/RoleLevelTest.php app/Helpers/functions.php
git commit -m "feat: add roleLevel() helper, update requireAdmin(), add requireSuperAdmin()"
```

---

### Task 3: Update UserService + tests

**Files:**
- Modify: `app/Services/UserService.php:45`
- Modify: `tests/Unit/UserServiceDeleteTest.php`

**Background:** `UserService::deleteUser()` currently throws if target user has role `'admin'`. After this change:
- Throwing if role is `'super_admin'` (nobody can delete a super_admin)
- Allow if role is `'admin'` (super_admin can delete admins — enforced at controller level, not service level)
- The existing test `testDeleteBlocksAdminUser` must be updated to `testDeleteBlocksSuperAdminUser`

**Step 1: Update UserService::deleteUser() guard**

In `app/Services/UserService.php`, line 45 — change:
```php
        if (($user['role'] ?? '') === 'admin') {
            throw new \RuntimeException('Admin accounts cannot be deleted via this action.');
        }
```
To:
```php
        if (roleLevel($user['role'] ?? '') >= 3) {
            throw new \RuntimeException('Super admin accounts cannot be deleted.');
        }
```

Note: `roleLevel()` is a global helper defined in `app/Helpers/functions.php`. PHPUnit loads helpers via `tests/bootstrap.php` — confirm this file exists and includes functions.php before proceeding. If not, check `phpunit.xml` for the bootstrap path.

**Step 2: Update the existing blocking test**

In `tests/Unit/UserServiceDeleteTest.php`, rename and update `testDeleteBlocksAdminUser`:
```php
    public function testDeleteBlocksSuperAdminUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Super admin accounts cannot be deleted.');

        $service = $this->makeService();
        $service->deleteUser(
            ['id' => 5, 'role' => 'super_admin', 'email' => 'superadmin@example.com'],
            99
        );
    }
```

**Step 3: Add test confirming admin users CAN now be deleted**

Add this test immediately after the one above:
```php
    public function testDeleteAllowsAdminUser(): void
    {
        // Admin deletion is allowed at service level; authorization is the controller's job.
        $users = $this->createMock(\App\Repositories\UserRepository::class);
        $items = $this->createMock(\App\Repositories\ItemRepository::class);

        $items->method('donorItemIdsInActiveAuctions')->willReturn([]);
        $items->method('donorItemIdsNotActive')->willReturn([]);
        $users->expects($this->once())->method('delete');

        $service = $this->makeService(['users' => $users, 'items' => $items]);
        $service->deleteUser(
            ['id' => 10, 'role' => 'admin', 'email' => 'admin@example.com'],
            99
        );
    }
```

**Step 4: Run tests**
```bash
./galvani auction/run-tests.php -- tests/Unit/UserServiceDeleteTest.php --no-coverage
```
Expected: all tests pass (including the new `testDeleteAllowsAdminUser`).

**Step 5: Run full suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 6: Commit**
```bash
git add app/Services/UserService.php tests/Unit/UserServiceDeleteTest.php
git commit -m "feat: allow admin deletion in UserService; only super_admin is blocked"
```

---

### Task 4: AdminController — restricted routes + user management

**Files:**
- Modify: `app/Controllers/AdminController.php`
- Modify: `index.php`

**Background:** Five web routes need to be restricted to `requireSuperAdmin()`. The `deleteUser()` method needs updated guards. The `updateUser()` role-change logic needs updating. The export route for gift-aid is missing from index.php.

**Step 1: Restrict payments, gift-aid, settings to super_admin**

In `app/Controllers/AdminController.php`, change the following methods to call `requireSuperAdmin()` instead of `requireAdmin()`:

- `payments()` — line ~622
- `giftAid()` — line ~670
- `exportGiftAid()` — line ~704
- `settings()` — line ~848
- `saveSettings()` — method after settings

Find each `requireAdmin()` call at the top of these 5 methods and replace with `requireSuperAdmin()`.

**Step 2: Update deleteUser() guards**

In `app/Controllers/AdminController.php`, find `deleteUser(string $slug)` (line ~500).

Change this guard:
```php
        if ((string)($profile['role'] ?? '') === 'admin') {
            flash('Admin accounts cannot be deleted.', 'error');
            $this->redirect($basePath . '/admin/users');
        }
```
To:
```php
        if (roleLevel($profile['role'] ?? '') >= 3) {
            flash('Super admin accounts cannot be deleted.', 'error');
            $this->redirect($basePath . '/admin/users');
        }

        if (roleLevel($profile['role'] ?? '') >= 2 && roleLevel($actingAdmin['role'] ?? '') < 3) {
            flash('Only super admins can delete admin accounts.', 'error');
            $this->redirect($basePath . '/admin/users');
        }
```

**Step 3: Update updateUser() role-change logic**

In `app/Controllers/AdminController.php`, find `updateUser(string $slug)` (line ~540).

Find the `$allowed` array in the change-role section:
```php
        $newRole = trim($_POST['role'] ?? '');
        $allowed = ['bidder', 'donor'];
```
Change to:
```php
        $newRole = trim($_POST['role'] ?? '');
        // Super admins can promote to admin; regular admins cannot
        $allowed = roleLevel($admin['role'] ?? '') >= 3
            ? ['bidder', 'donor', 'admin']
            : ['bidder', 'donor'];
```

Also update the email-change guard (around line 558) to block super_admin profiles too:
```php
            if ($profile['role'] === 'admin' || $profile['role'] === 'super_admin') {
```

**Step 4: Register the missing gift-aid/export route in index.php**

In `index.php`, after the gift-aid GET route (line ~217), add:
```php
$router->post('/admin/gift-aid/export',                [$adminController, 'exportGiftAid']);
```

**Step 5: Run full test suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 6: Commit**
```bash
git add app/Controllers/AdminController.php index.php
git commit -m "feat: restrict payments/gift-aid/settings to super_admin; update delete/role guards"
```

---

### Task 5: AdminApiController — mirror restrictions

**Files:**
- Modify: `app/Controllers/AdminApiController.php`

**Background:** `AdminApiController` has its own private `requireAdmin()` method (currently checks `role !== 'admin'`). This needs to accept `roleLevel >= 2`. A new `requireSuperAdmin()` method is needed. Four endpoints switch to super_admin only. `deleteUser()` and `updateUser()` need their guards updated.

**Step 1: Update the private requireAdmin() method**

Current (lines 18–25):
```php
    private function requireAdmin(): array
    {
        $user = getAuthUser();
        if (!$user || $user['role'] !== 'admin') {
            $this->apiError('Admin access required.', 403);
        }
        return $user;
    }
```
Change to:
```php
    private function requireAdmin(): array
    {
        $user = getAuthUser();
        if (!$user || roleLevel($user['role'] ?? '') < 2) {
            $this->apiError('Admin access required.', 403);
        }
        return $user;
    }
```

**Step 2: Add private requireSuperAdmin() method immediately after requireAdmin()**
```php
    private function requireSuperAdmin(): array
    {
        $user = getAuthUser();
        if (!$user || roleLevel($user['role'] ?? '') < 3) {
            $this->apiError('Super admin access required.', 403);
        }
        return $user;
    }
```

**Step 3: Switch 4 endpoints to requireSuperAdmin()**

In each of these methods, replace the first `$this->requireAdmin()` call with `$this->requireSuperAdmin()`:
- `listPayments()` — line ~442
- `giftAidOverview()` — line ~472
- `getSettings()` — line ~515
- `updateSettings()` — line ~536

**Step 4: Update deleteUser() guard**

Find `deleteUser(string $slug)` in AdminApiController (line ~406).

Change:
```php
        if (($profile['role'] ?? '') === 'admin') {
            $this->apiError('Admin accounts cannot be deleted.', 422);
        }
```
To:
```php
        if (roleLevel($profile['role'] ?? '') >= 3) {
            $this->apiError('Super admin accounts cannot be deleted.', 422);
        }

        if (roleLevel($profile['role'] ?? '') >= 2 && roleLevel($actingAdmin['role'] ?? '') < 3) {
            $this->apiError('Only super admins can delete admin accounts.', 403);
        }
```

**Step 5: Update updateUser() role-change and email-change guards**

Find `updateUser(string $slug)` (line ~353).

Email-change guard (line ~367) — change:
```php
            if ($user['role'] === 'admin') {
```
To:
```php
            if (roleLevel($user['role'] ?? '') >= 2) {
```

Role-change allowed-roles (line ~383) — change:
```php
            $allowedRoles = ['bidder', 'donor', 'admin'];
            if (!in_array($body['role'], $allowedRoles, true)) {
                $this->apiError('Invalid role. Must be bidder, donor, or admin.');
            }
```
To:
```php
            $actingUser   = $this->requireAdmin(); // already called above, but re-fetch for level check
            $allowedRoles = roleLevel($actingAdmin['role'] ?? '') >= 3
                ? ['bidder', 'donor', 'admin']
                : ['bidder', 'donor'];
            if (!in_array($body['role'], $allowedRoles, true)) {
                $this->apiError('Invalid role. Allowed: ' . implode(', ', $allowedRoles) . '.');
            }
```

Wait — `$actingAdmin` is already declared at the top of `updateUser()` via `$this->requireAdmin()`. Use that variable directly. The method starts with `$this->requireAdmin()` — capture its return value so the role level is available:

At the top of `updateUser()`:
```php
    public function updateUser(string $slug): void
    {
        $actingAdmin = $this->requireAdmin();   // ← capture return value
        $body = $this->jsonBody();
        ...
```
Then use `$actingAdmin['role']` in the role-change block:
```php
            $allowedRoles = roleLevel($actingAdmin['role'] ?? '') >= 3
                ? ['bidder', 'donor', 'admin']
                : ['bidder', 'donor'];
            if (!in_array($body['role'], $allowedRoles, true)) {
                $this->apiError('Invalid role. Allowed: ' . implode(', ', $allowedRoles) . '.');
            }
```

**Step 6: Run full test suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 7: Commit**
```bash
git add app/Controllers/AdminApiController.php
git commit -m "feat: update AdminApiController for super_admin role hierarchy"
```

---

### Task 6: Admin nav — hide restricted tabs from admin role

**Files:**
- Modify: `app/Views/partials/header-admin.php`

**Background:** Payments, Gift Aid, and Settings tabs should only appear for super_admin users (roleLevel >= 3). The `$user` variable is already available in this partial (passed from every admin layout).

**Step 1: Update header-admin.php**

In `app/Views/partials/header-admin.php`, change the `$navItems` array to conditionally include restricted tabs.

Replace the static `$navItems` array (lines 5–14):
```php
$navItems = [
    'dashboard'   => ['label' => 'Dashboard',   'url' => '/admin/dashboard'],
    'auctions'    => ['label' => 'Auctions',     'url' => '/admin/auctions'],
    'items'       => ['label' => 'Items',        'url' => '/admin/items'],
    'users'       => ['label' => 'Users',        'url' => '/admin/users'],
    'payments'    => ['label' => 'Payments',     'url' => '/admin/payments'],
    'gift-aid'    => ['label' => 'Gift Aid',     'url' => '/admin/gift-aid'],
    'live-events' => ['label' => 'Live Events',  'url' => '/admin/live-events'],
    'settings'    => ['label' => 'Settings',     'url' => '/admin/settings'],
];
```
With:
```php
$isSuperAdmin = roleLevel($user['role'] ?? '') >= 3;
$navItems = [
    'dashboard'   => ['label' => 'Dashboard',   'url' => '/admin/dashboard'],
    'auctions'    => ['label' => 'Auctions',     'url' => '/admin/auctions'],
    'items'       => ['label' => 'Items',        'url' => '/admin/items'],
    'users'       => ['label' => 'Users',        'url' => '/admin/users'],
    ...($isSuperAdmin ? [
        'payments'    => ['label' => 'Payments',  'url' => '/admin/payments'],
        'gift-aid'    => ['label' => 'Gift Aid',  'url' => '/admin/gift-aid'],
    ] : []),
    'live-events' => ['label' => 'Live Events',  'url' => '/admin/live-events'],
    ...($isSuperAdmin ? [
        'settings'    => ['label' => 'Settings',  'url' => '/admin/settings'],
    ] : []),
];
```

**Step 2: Run full test suite (no regressions)**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 3: Commit**
```bash
git add app/Views/partials/header-admin.php
git commit -m "feat: hide payments/gift-aid/settings nav tabs from admin role"
```

---

### Task 7: Admin users view + user-detail view

**Files:**
- Modify: `app/Views/admin/users.php`
- Modify: `app/Views/admin/user-detail.php`

**Background:** The users list view has a delete button that currently only shows for non-admin users. With super_admin, admins can also be deleted (but not super_admins). The user-detail view has a role dropdown limited to bidder/donor — super_admins should also see an admin option.

The acting user is available as `$user` in both views (it's the authenticated admin, passed from the controller).

#### Part A: users.php

**Step 1: Update role badge to include super_admin styling**

In `app/Views/admin/users.php`, find the `$roleBadge` closure (around line 19).

The `match($role)` currently has `'admin'` and a default. Add `'super_admin'`:
```php
$roleBadge = function(string $role): string {
    return match($role) {
        'super_admin' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Super Admin</span>',
        'admin'       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Admin</span>',
        'donor'       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Donor</span>',
        'bidder'      => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Bidder</span>',
        default       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">' . e($role) . '</span>',
    };
};
```

**Step 2: Update delete button guard in table rows**

Find the delete button section in the table (around line 146) — currently:
```php
<?php if (($u['role'] ?? '') !== 'admin'): ?>
```
Change to show delete for all non-super_admin users when acting user has level >= 2,
but for admin targets only when acting user has level >= 3:
```php
<?php if (roleLevel($u['role'] ?? '') < 3 && (roleLevel($u['role'] ?? '') < 2 || roleLevel($user['role'] ?? '') >= 3)): ?>
```

**Step 3: Update delete button in the Popover loop**

The second `foreach` loop (around line 162) renders Popover confirmations. Find:
```php
<?php if (($u['role'] ?? '') !== 'admin'): ?>
```
Apply the same condition as Step 2.

**Step 4: Update role filter dropdown**

The `<select name="role">` filter (around line 53–58) already has bidder/donor/admin. Add super_admin:
```php
      <option value="super_admin" <?= ($roleFilter ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admins</option>
```

#### Part B: user-detail.php

**Step 5: Update role badge for super_admin**

Find the role badge block (around line 46–52). Currently it shows a badge for `'admin'` or falls through to a default. Add:
```php
<?php $r = $profile['role'] ?? 'bidder'; ?>
<?php if ($r === 'super_admin'): ?>
  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Super Admin</span>
<?php elseif ($r === 'admin'): ?>
  <!-- existing admin badge markup -->
```

**Step 6: Update role change form guard and options**

Find the role change form section (around line 75):
```php
    <?php if ($profile['role'] !== 'admin'): ?>
```
Change to: show the form for anyone who is NOT a super_admin (super_admin profiles can't have their role changed via UI):
```php
    <?php if ($profile['role'] !== 'super_admin'): ?>
```

Inside the role `<select>`, currently:
```php
            <option value="bidder" <?= $profile['role'] === 'bidder' ? 'selected' : '' ?>>Bidder</option>
            <option value="donor"  <?= $profile['role'] === 'donor'  ? 'selected' : '' ?>>Donor</option>
```
Add admin option visible only to super_admin acting users:
```php
            <option value="bidder" <?= $profile['role'] === 'bidder' ? 'selected' : '' ?>>Bidder</option>
            <option value="donor"  <?= $profile['role'] === 'donor'  ? 'selected' : '' ?>>Donor</option>
            <?php if (roleLevel($user['role'] ?? '') >= 3): ?>
            <option value="admin"  <?= $profile['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
            <?php endif; ?>
```

**Step 7: Update email change form guard**

Find the email change section (around line 91–92):
```php
    <?php if ($profile['role'] !== 'admin'): ?>
```
Change to block both admin and super_admin:
```php
    <?php if (roleLevel($profile['role'] ?? '') < 2): ?>
```

**Step 8: Update delete button guard**

Find where the delete button popover is rendered (look for `popovertarget` or delete button in user-detail.php — it may not exist there currently; check the view). The delete button is currently only on the users *list* view. Verify whether user-detail.php also has a delete button.

If it does, apply the same logic: show for non-super_admin targets, require super_admin acting user for admin targets.

If it doesn't, skip this step.

**Step 9: Run full test suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 10: Commit**
```bash
git add app/Views/admin/users.php app/Views/admin/user-detail.php
git commit -m "feat: update admin views for super_admin role (badges, delete guards, role dropdown)"
```

---

### Task 8: Remove login page dev quick-login

**Files:**
- Modify: `app/Views/auth/login.php`

**Background:** The login page has a "Dev accounts" section (lines ~188–205) with quick-login buttons that pre-fill email/password. The `devLogin()` JS function is at the bottom (~line 222–225). Both must be removed entirely.

**Step 1: Remove the Dev accounts block**

In `app/Views/auth/login.php`, remove the entire block:
```html
        <!-- Dev quick-login -->
        <div class="mt-8 pt-6 border-t border-dashed border-slate-200 dark:border-slate-700">
          <p class="text-center text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-3">Dev accounts</p>
          <div class="flex gap-2 flex-wrap justify-center">
            <button type="button" onclick="devLogin('admin@wellfoundation.org.uk')"
              class="...">
              Admin
            </button>
            <button type="button" onclick="devLogin('donor@example.com')"
              class="...">
              Donor
            </button>
            <button type="button" onclick="devLogin('bidder@example.com')"
              class="...">
              Bidder
            </button>
          </div>
        </div>
```

**Step 2: Remove the devLogin() JS function**

In the `<script>` block near the bottom, remove:
```js
function devLogin(email) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = 'Admin1234!';
```
and its closing `}`.

**Step 3: Run full test suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 4: Commit**
```bash
git add app/Views/auth/login.php
git commit -m "feat: remove dev quick-login from login page"
```

---

### Task 9: MCP server — types + tool registration + resources

**Files:**
- Modify: `mcp/packages/server/src/types.ts`
- Modify: `mcp/packages/server/src/index.ts`

**Background:** The MCP server registers tools based on the logged-in user's role. `types.ts` defines the `Role` type. `index.ts` controls which tools are registered and what the `role-permissions` resource reports.

After this task:
- `super_admin` gets all tools (same as current `admin` gets currently)
- `admin` gets all tools **except** `admin_payments`, `admin_gift_aid`, `admin_settings`
- Both `admin` and `super_admin` get `admin_reports`

**Step 1: Update types.ts**

In `mcp/packages/server/src/types.ts`, change line 1:
```typescript
export type Role = 'bidder' | 'donor' | 'admin' | 'super_admin';
```

**Step 2: Update index.ts tool registration**

In `mcp/packages/server/src/index.ts`, the current admin-only block (around line 49–57):
```typescript
  if (role === 'admin') {
    registerAdminAuctionTools(server, client);
    registerAdminItemTools(server, client);
    registerAdminUserTools(server, client);
    registerAdminPaymentTools(server, client);
    registerAdminReportTools(server, client);
    registerAdminSettingsTools(server, client);
    registerAdminLiveTools(server, client);
  }
```
Replace with:
```typescript
  const isAdmin      = role === 'admin'       || role === 'super_admin';
  const isSuperAdmin = role === 'super_admin';

  if (isAdmin) {
    registerAdminAuctionTools(server, client);
    registerAdminItemTools(server, client);
    registerAdminUserTools(server, client);
    registerAdminReportTools(server, client);
    registerAdminLiveTools(server, client);
  }

  if (isSuperAdmin) {
    registerAdminPaymentTools(server, client);
    registerAdminSettingsTools(server, client);
  }
```

Also update the bidding tools block (line ~39) to include super_admin:
```typescript
  if (role === 'bidder' || role === 'admin' || role === 'super_admin') {
```
And the donation tools block (line ~44):
```typescript
  if (role === 'donor' || role === 'admin' || role === 'super_admin') {
```

**Step 3: Update the role-permissions resource**

In the `role-permissions` resource handler (around line 83–92), add the super_admin entry:
```typescript
      const permissions: Record<string, string[]> = {
        bidder: ['verify_connection', 'browse_events', 'browse_items', 'my_profile', 'my_bids', 'place_bid'],
        donor:  ['verify_connection', 'browse_events', 'browse_items', 'my_profile', 'my_donations'],
        admin:  [
          'verify_connection', 'browse_events', 'browse_items',
          'my_profile', 'my_bids', 'place_bid', 'my_donations',
          'manage_auctions', 'manage_items', 'manage_users',
          'admin_reports', 'manage_live',
        ],
        super_admin: [
          'verify_connection', 'browse_events', 'browse_items',
          'my_profile', 'my_bids', 'place_bid', 'my_donations',
          'manage_auctions', 'manage_items', 'manage_users',
          'admin_payments', 'admin_gift_aid', 'admin_reports',
          'admin_settings', 'manage_live',
        ],
      };
```

**Step 4: Update the admin prompts block**

The prompts are currently registered only for `role === 'admin'` (line ~109). Change to:
```typescript
  if (role === 'admin' || role === 'super_admin') {
```

**Step 5: Build TypeScript**
```bash
cd /Users/waseem/Sites/www/auction/mcp && npm run build
```
Expected: clean compile, no errors.

**Step 6: Run PHP tests (no regressions)**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 7: Commit**
```bash
git add mcp/packages/server/src/types.ts mcp/packages/server/src/index.ts
git commit -m "feat: add super_admin to MCP role type and tool registration"
```

---

### Task 10: Docs — admin guide + MCP docs site

**Files:**
- Modify: `docs/admin/README.md`
- Modify: `mcp/auction-mcp-docs/admin/index.html`

**Constraint:** Do NOT mention specific super_admin user accounts, emails, or passwords anywhere in these files.

#### Part A: docs/admin/README.md

**Step 1: Add role hierarchy section**

After the "Admin Navigation" section and before "Managing Auctions", insert a new section:

```markdown
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
```

**Step 2: Update "Changing user roles" section**

Find the role dropdown list under "Changing user roles":
```markdown
   - **Bidder** — standard registered user, can bid
   - **Donor** — can submit items; can also bid
   - **Admin** — full admin access
```
Update to:
```markdown
   - **Bidder** — standard registered user, can bid
   - **Donor** — can submit items; can also bid
   - **Admin** — full admin access (payments, gift aid, and settings excluded)

> The Admin option in the dropdown is only visible to super admin users. Regular admins can only set Bidder or Donor.
```

**Step 3: Update "Deleting a user" section**

The existing section says "Admin accounts cannot be deleted." Update to reflect the new rule:

Change the note at the bottom from:
```markdown
> The Delete button is not shown for admin accounts. To remove an admin, first change their role to Bidder or Donor, then delete them.
```
To:
```markdown
> The Delete button is not shown for super admin accounts.
>
> Admin accounts can only be deleted by a super admin. Regular admins do not see the Delete button for admin accounts.
```

#### Part B: mcp/auction-mcp-docs/admin/index.html

**Step 4: Update the Available Tools table**

Find the `manage_users` row in the tool table:
```html
<tr><td><code>manage_users</code></td><td>Search, view, update user roles and email addresses, and permanently delete non-admin users (GDPR)</td></tr>
```
Update the description:
```html
<tr><td><code>manage_users</code></td><td>Search, view, update user roles and email addresses, and permanently delete users (GDPR). Super admins can also delete admin accounts.</td></tr>
```

**Step 5: Add a note about role-restricted tools**

After the tools table and before the "Browse the full prompt library" link, add a note:
```html
    <div class="section">
      <div class="section-title">Role Permissions</div>
      <p class="text-muted" style="font-size:.875rem;margin-bottom:.75rem">
        Some tools are only available to super admins:
      </p>
      <table class="tool-table">
        <thead>
          <tr><th>Tool</th><th>Admin</th><th>Super Admin</th></tr>
        </thead>
        <tbody>
          <tr><td><code>admin_payments</code></td><td>—</td><td>✓</td></tr>
          <tr><td><code>admin_gift_aid</code></td><td>—</td><td>✓</td></tr>
          <tr><td><code>admin_settings</code></td><td>—</td><td>✓</td></tr>
          <tr><td>All other admin tools</td><td>✓</td><td>✓</td></tr>
        </tbody>
      </table>
    </div>
```

**Step 6: Run full test suite**
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass.

**Step 7: Commit**
```bash
git add docs/admin/README.md mcp/auction-mcp-docs/admin/index.html
git commit -m "docs: document super_admin role in admin guide and MCP docs"
```

---

## Final verification

After all tasks complete, run the full test suite one last time:
```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```
Expected: all tests pass (should be 49+ unit tests).
