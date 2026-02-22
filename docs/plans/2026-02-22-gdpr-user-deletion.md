# GDPR User Deletion Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a "Delete user" action to the admin users list that permanently erases all personal data for a non-admin user in GDPR-compliant cascade order.

**Architecture:** `UserService::deleteUser()` orchestrates the cascade, delegating all SQL to Repository methods. The confirmation UX uses the Popover API atom — no `confirm()` dialogs. A POST route handles the form submission.

**Tech Stack:** PHP MVC · MariaDB · Tailwind · Popover API · PHPUnit mocks

---

## Context for the implementer

### Rules you MUST follow
- NEVER `beginTransaction()` or `commit()` — autocommit only (Galvani)
- NEVER `new Database()` — always `Database::getInstance()`
- NEVER `LIMIT ?` — always `'LIMIT ' . (int)$n`
- NEVER `alert()` / `confirm()` — use Popover API
- NEVER `innerHTML` / `outerHTML` — use `createElement` / `textContent`
- NEVER `style=""` attributes — Tailwind classes only
- All SQL in Repositories only — never in Services or Controllers
- Test runner: `cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/`
- Current baseline: **44/44 tests passing**

### Key files
- `app/Repositories/UserRepository.php` — user DB methods
- `app/Repositories/BidRepository.php` — bid DB methods
- `app/Repositories/ItemRepository.php` — item DB methods
- `app/Repositories/PaymentRepository.php` — payment DB methods
- `app/Controllers/AdminController.php` — add `deleteUser()` here
- `app/Views/admin/users.php` — add delete button + popover here
- `app/Views/atoms/popover-shell.php` — read this before writing the popover
- `core/Router.php` — register the POST route here
- `database/schema.sql` — review FK constraints before writing SQL

### Cascade delete order (FK-safe)
The goal is to remove all rows that reference `users.id` before deleting the user row itself. Execute in this order:

1. `password_reset_tokens` — DELETE WHERE user_id = $userId
2. `rate_limits` — DELETE WHERE identifier = $email (no FK — keyed by email string)
3. `bids` — DELETE WHERE user_id = $userId (bids PLACED by this user)
4. Items donated by user in **active** auctions — UPDATE SET donor_id = NULL (keep item running)
5. Get IDs of remaining donated items (not in active auctions)
6. DELETE FROM payments WHERE item_id IN (...) (others' payments for those items)
7. DELETE FROM bids WHERE item_id IN (...) (others' bids on those items)
8. DELETE FROM items WHERE donor_id = $userId (the non-active donated items)
9. DELETE FROM payments WHERE user_id = $userId (user's own payments for won items)
10. UPDATE items SET winner_id = NULL WHERE winner_id = $userId
11. UPDATE events SET created_by = $actingAdminId WHERE created_by = $userId (edge case: demoted admins who created events — prevents FK violation)
12. DELETE FROM users WHERE id = $userId

---

## Task 1: Repository cascade methods

**Files:**
- Modify: `app/Repositories/BidRepository.php`
- Modify: `app/Repositories/ItemRepository.php`
- Modify: `app/Repositories/PaymentRepository.php`
- Modify: `app/Repositories/UserRepository.php`

No tests for this task (pure SQL delegation — tested via UserService in Task 2).

**Step 1: Add to `BidRepository`**

```php
/**
 * Delete all bids placed by a user (cascade step for user deletion).
 */
public function deleteByUser(int $userId): void
{
    $this->db->execute('DELETE FROM bids WHERE user_id = ?', [$userId]);
}

/**
 * Delete all bids on a set of items (cascade step before deleting donated items).
 */
public function deleteByItems(array $itemIds): void
{
    if (empty($itemIds)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $this->db->execute(
        'DELETE FROM bids WHERE item_id IN (' . $placeholders . ')',
        $itemIds
    );
}
```

**Step 2: Add to `ItemRepository`**

```php
/**
 * Return IDs of items donated by $userId whose event is currently active.
 * These items will be anonymised (donor_id = NULL) rather than deleted.
 */
public function donorItemIdsInActiveAuctions(int $userId): array
{
    $rows = $this->db->query(
        'SELECT i.id FROM items i
         JOIN events e ON e.id = i.event_id
         WHERE i.donor_id = ? AND e.status = \'active\'',
        [$userId]
    );
    return array_column($rows, 'id');
}

/**
 * Return IDs of items donated by $userId that are NOT in an active auction.
 * These items will be deleted.
 */
public function donorItemIdsNotActive(int $userId): array
{
    $rows = $this->db->query(
        'SELECT i.id FROM items i
         LEFT JOIN events e ON e.id = i.event_id
         WHERE i.donor_id = ?
           AND (e.id IS NULL OR e.status != \'active\')',
        [$userId]
    );
    return array_column($rows, 'id');
}

/**
 * Strip donor link from items in active auctions (GDPR anonymisation).
 */
public function anonymiseDonor(int $userId): void
{
    $this->db->execute(
        'UPDATE items i
         JOIN events e ON e.id = i.event_id
         SET i.donor_id = NULL
         WHERE i.donor_id = ? AND e.status = \'active\'',
        [$userId]
    );
}

/**
 * Delete items by their IDs.
 */
public function deleteByIds(array $ids): void
{
    if (empty($ids)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $this->db->execute(
        'DELETE FROM items WHERE id IN (' . $placeholders . ')',
        $ids
    );
}

/**
 * Clear winner_id where the deleted user is the winner.
 * The item stays — we just remove the winner reference.
 */
public function clearWinner(int $userId): void
{
    $this->db->execute(
        'UPDATE items SET winner_id = NULL WHERE winner_id = ?',
        [$userId]
    );
}
```

**Step 3: Add to `PaymentRepository`**

```php
/**
 * Delete payment records for a set of items (e.g. donated items being deleted).
 */
public function deleteByItems(array $itemIds): void
{
    if (empty($itemIds)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $this->db->execute(
        'DELETE FROM payments WHERE item_id IN (' . $placeholders . ')',
        $itemIds
    );
}

/**
 * Delete all payment records for a user (e.g. items they won).
 */
public function deleteByUser(int $userId): void
{
    $this->db->execute('DELETE FROM payments WHERE user_id = ?', [$userId]);
}
```

**Step 4: Add to `UserRepository`**

```php
/**
 * Delete password reset tokens for a user.
 */
public function deletePasswordResets(int $userId): void
{
    $this->db->execute(
        'DELETE FROM password_reset_tokens WHERE user_id = ?',
        [$userId]
    );
}

/**
 * Delete rate limit records keyed by email.
 * rate_limits has no FK — uses identifier (email) + action strings.
 */
public function deleteRateLimits(string $email): void
{
    $this->db->execute(
        'DELETE FROM rate_limits WHERE identifier = ?',
        [$email]
    );
}

/**
 * Transfer events created_by to another user (handles demoted admins edge case).
 */
public function transferEvents(int $fromUserId, int $toUserId): void
{
    $this->db->execute(
        'UPDATE events SET created_by = ? WHERE created_by = ?',
        [$toUserId, $fromUserId]
    );
}

/**
 * Permanently delete a user row. All FK-referenced rows must be cleaned up first.
 */
public function delete(int $id): void
{
    $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
}
```

**Step 5: Commit**

```bash
git add app/Repositories/BidRepository.php app/Repositories/ItemRepository.php \
        app/Repositories/PaymentRepository.php app/Repositories/UserRepository.php
git commit -m "feat: add cascade delete repository methods for GDPR user deletion"
```

---

## Task 2: UserService::deleteUser() + tests

**Files:**
- Create: `app/Services/UserService.php`
- Create: `tests/Unit/UserServiceDeleteTest.php`

**Step 1: Write the failing tests**

Create `tests/Unit/UserServiceDeleteTest.php`:

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserServiceDeleteTest extends TestCase
{
    private function makeService(array $repos = []): \App\Services\UserService
    {
        return new \App\Services\UserService(
            $repos['users']    ?? $this->createMock(\App\Repositories\UserRepository::class),
            $repos['bids']     ?? $this->createMock(\App\Repositories\BidRepository::class),
            $repos['items']    ?? $this->createMock(\App\Repositories\ItemRepository::class),
            $repos['payments'] ?? $this->createMock(\App\Repositories\PaymentRepository::class)
        );
    }

    public function testDeleteBlocksAdminUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Admin accounts cannot be deleted');

        $service = $this->makeService();
        $service->deleteUser(
            ['id' => 5, 'role' => 'admin', 'email' => 'admin@example.com'],
            99 // actingAdminId
        );
    }

    public function testDeleteAnonymisesDonatedItemsInActiveAuctions(): void
    {
        $items = $this->createMock(\App\Repositories\ItemRepository::class);

        // Active donated items: IDs [3, 4]
        $items->method('donorItemIdsInActiveAuctions')->willReturn([3, 4]);
        $items->method('donorItemIdsNotActive')->willReturn([]);

        // Must call anonymiseDonor exactly once
        $items->expects($this->once())->method('anonymiseDonor');

        // deleteByIds must NOT be called (no non-active items)
        $items->expects($this->never())->method('deleteByIds');

        $users = $this->createMock(\App\Repositories\UserRepository::class);
        $users->method('findById')->willReturn(null); // post-delete check

        $service = $this->makeService(['items' => $items, 'users' => $users]);
        $service->deleteUser(
            ['id' => 7, 'role' => 'bidder', 'email' => 'user@example.com'],
            1
        );
    }

    public function testDeleteCallsFullCascadeInOrder(): void
    {
        $callOrder = [];

        $users = $this->createMock(\App\Repositories\UserRepository::class);
        $bids  = $this->createMock(\App\Repositories\BidRepository::class);
        $items = $this->createMock(\App\Repositories\ItemRepository::class);
        $pays  = $this->createMock(\App\Repositories\PaymentRepository::class);

        $items->method('donorItemIdsInActiveAuctions')->willReturn([]);
        $items->method('donorItemIdsNotActive')->willReturn([10, 11]);

        $users->method('deletePasswordResets')->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'password_resets'; });
        $users->method('deleteRateLimits')    ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'rate_limits'; });
        $bids ->method('deleteByUser')        ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'bids_by_user'; });
        $pays ->method('deleteByItems')       ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'payments_by_items'; });
        $bids ->method('deleteByItems')       ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'bids_by_items'; });
        $items->method('deleteByIds')         ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'items_delete'; });
        $pays ->method('deleteByUser')        ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'payments_by_user'; });
        $items->method('clearWinner')         ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'clear_winner'; });
        $users->method('transferEvents')      ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'transfer_events'; });
        $users->method('delete')              ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'delete_user'; });

        $service = new \App\Services\UserService($users, $bids, $items, $pays);
        $service->deleteUser(
            ['id' => 7, 'role' => 'bidder', 'email' => 'user@example.com'],
            1
        );

        // Assert critical ordering constraints
        $this->assertLessThan(
            array_search('delete_user', $callOrder),
            array_search('bids_by_user', $callOrder),
            'bids_by_user must come before delete_user'
        );
        $this->assertLessThan(
            array_search('items_delete', $callOrder),
            array_search('bids_by_items', $callOrder),
            'bids_by_items must come before items_delete'
        );
        $this->assertLessThan(
            array_search('items_delete', $callOrder),
            array_search('payments_by_items', $callOrder),
            'payments_by_items must come before items_delete'
        );
        $this->assertLessThan(
            array_search('delete_user', $callOrder),
            array_search('payments_by_user', $callOrder),
            'payments_by_user must come before delete_user'
        );
        $this->assertContains('delete_user', $callOrder);
    }
}
```

**Step 2: Run tests to confirm they fail**

```bash
cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/UserServiceDeleteTest.php
```

Expected: 3 failures (class not found).

**Step 3: Create `app/Services/UserService.php`**

```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\BidRepository;
use App\Repositories\ItemRepository;
use App\Repositories\PaymentRepository;

class UserService
{
    public function __construct(
        private UserRepository    $users,
        private BidRepository     $bids,
        private ItemRepository    $items,
        private PaymentRepository $payments
    ) {}

    /**
     * Permanently delete a user and all their associated data (GDPR erasure).
     *
     * @param array $user          The user row to delete (must include id, role, email)
     * @param int   $actingAdminId The admin performing the deletion (for event transfer)
     * @throws \RuntimeException   If the target user is an admin
     */
    public function deleteUser(array $user, int $actingAdminId): void
    {
        $userId = (int)$user['id'];
        $email  = (string)($user['email'] ?? '');

        if (($user['role'] ?? '') === 'admin') {
            throw new \RuntimeException('Admin accounts cannot be deleted via this action.');
        }

        // 1. Password reset tokens
        $this->users->deletePasswordResets($userId);

        // 2. Rate limits (keyed by email, no FK)
        $this->users->deleteRateLimits($email);

        // 3. Bids placed by this user
        $this->bids->deleteByUser($userId);

        // 4. Donated items in active auctions — anonymise, not delete
        $this->items->anonymiseDonor($userId);

        // 5–8. Donated items not in active auctions — cascade delete
        $itemIds = $this->items->donorItemIdsNotActive($userId);
        if (!empty($itemIds)) {
            $this->payments->deleteByItems($itemIds);
            $this->bids->deleteByItems($itemIds);
            $this->items->deleteByIds($itemIds);
        }

        // 9. User's own payment records (for items they won)
        $this->payments->deleteByUser($userId);

        // 10. Clear winner_id on items they won
        $this->items->clearWinner($userId);

        // 11. Transfer any events they created (edge case: demoted admins)
        $this->users->transferEvents($userId, $actingAdminId);

        // 12. Delete the user row
        $this->users->delete($userId);
    }
}
```

**Step 4: Run tests — all should pass**

```bash
cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/UserServiceDeleteTest.php
```

Expected: `Tests: 3, Assertions: N, OK`

**Step 5: Run full suite**

```bash
cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/
```

Expected: 47/47 passing.

**Step 6: Commit**

```bash
git add app/Services/UserService.php tests/Unit/UserServiceDeleteTest.php
git commit -m "feat: add UserService::deleteUser() with cascade delete + tests"
```

---

## Task 3: Route + AdminController::deleteUser()

**Files:**
- Modify: `core/Router.php`
- Modify: `app/Controllers/AdminController.php`

**Step 1: Register the route in `core/Router.php`**

Find the block of admin routes (near the other `/admin/users` routes) and add:

```php
$router->post('/admin/users/{slug}/delete', [AdminController::class, 'deleteUser']);
```

**Step 2: Add `deleteUser()` to `AdminController`**

Find the `showUser()` method in `app/Controllers/AdminController.php` and add this method after it:

```php
// -------------------------------------------------------------------------
// POST /admin/users/:slug/delete
// -------------------------------------------------------------------------

public function deleteUser(string $slug): void
{
    global $basePath;
    $actingAdmin = requireAdmin();

    $userRepo = new \App\Repositories\UserRepository();
    $profile  = $userRepo->findBySlug($slug);

    if ($profile === null) {
        flash('User not found.', 'error');
        $this->redirect($basePath . '/admin/users');
    }

    if ((string)($profile['role'] ?? '') === 'admin') {
        flash('Admin accounts cannot be deleted.', 'error');
        $this->redirect($basePath . '/admin/users');
    }

    try {
        $service = new \App\Services\UserService(
            $userRepo,
            new \App\Repositories\BidRepository(),
            new \App\Repositories\ItemRepository(),
            new \App\Repositories\PaymentRepository()
        );
        $service->deleteUser($profile, (int)$actingAdmin['id']);
    } catch (\RuntimeException $e) {
        flash($e->getMessage(), 'error');
        $this->redirect($basePath . '/admin/users');
    }

    flash(e($profile['name']) . ' has been permanently deleted.', 'success');
    $this->redirect($basePath . '/admin/users');
}
```

**Step 3: Run full test suite**

```bash
cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/
```

Expected: still 47/47 passing.

**Step 4: Commit**

```bash
git add core/Router.php app/Controllers/AdminController.php
git commit -m "feat: add POST /admin/users/{slug}/delete route and controller"
```

---

## Task 4: Delete button + Popover confirmation in users view

**Files:**
- Modify: `app/Views/admin/users.php`

Read `app/Views/atoms/popover-shell.php` first to understand the atom API.

The Popover pattern:
- Each row gets a unique popover ID: `"del-" . e($u['slug'])`
- The delete button triggers it: `popovertarget="del-<slug>"`
- Inside the popover: warning message + a `<form method="POST">` with CSRF token and a red confirm button

**Step 1: Replace the `<td>` actions cell**

Find this line in the `<tbody>` loop (line ~143):

```php
<td class="px-5 py-3.5">
    <a href="<?= e($basePath) ?>/admin/users/<?= e($u['slug']) ?>" class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">View</a>
</td>
```

Replace with:

```php
<td class="px-5 py-3.5 text-right">
  <div class="inline-flex items-center gap-2">
    <a href="<?= e($basePath) ?>/admin/users/<?= e($u['slug']) ?>"
       class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">View</a>
    <?php if (($u['role'] ?? '') !== 'admin'): ?>
    <button
      type="button"
      popovertarget="del-<?= e($u['slug']) ?>"
      class="px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
    >Delete</button>
    <?php endif; ?>
  </div>
</td>
```

**Step 2: Add popovers after the closing `</table>` tag (before `</div>` of overflow-x-auto)**

After the `</tbody></table>` and before the closing `</div>` of the overflow wrapper, add a popover for each user:

```php
<?php foreach ($users as $u): ?>
<?php if (($u['role'] ?? '') !== 'admin'): ?>
<?php echo atom('popover-shell', [
    'id'     => 'del-' . e($u['slug']),
    'title'  => 'Delete ' . e($u['name']) . '?',
    'width'  => '28rem',
    'body'   => '<p class="text-sm text-slate-600 dark:text-slate-300">This will permanently remove <strong>' . e($u['name']) . '</strong> (' . e($u['email']) . ') and all their data — bids, donations, and payment records.</p>'
              . '<p class="text-sm font-semibold text-red-600 dark:text-red-400 mt-3">This action cannot be undone.</p>',
    'footer' => '<button type="button" popovertarget="del-' . e($u['slug']) . '" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>'
              . '<form method="POST" action="' . e($basePath) . '/admin/users/' . e($u['slug']) . '/delete?_csrf_token=' . e($csrfToken) . '" class="inline">'
              . '<button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Yes, delete permanently</button>'
              . '</form>',
]); ?>
<?php endif; ?>
<?php endforeach; ?>
```

**Step 3: Verify in browser**

Start Galvani: `cd /Users/waseem/Sites/www && ./galvani`

Visit http://localhost:8080/auction/admin/users — log in as `admin@wellfoundation.org.uk` / `Admin1234!`

Check:
- [ ] Each non-admin row has a "Delete" button
- [ ] Admin row has no "Delete" button
- [ ] Clicking Delete opens the popover with the user's name and warning
- [ ] "Cancel" closes the popover
- [ ] "Yes, delete permanently" submits the form and redirects to /admin/users with a success flash

**Step 4: Run full test suite**

```bash
cd /Users/waseem/Sites/www && ./galvani auction/run-tests.php -- tests/Unit/
```

Expected: 47/47 passing.

**Step 5: Commit**

```bash
git add app/Views/admin/users.php
git commit -m "feat: add delete button with Popover confirmation to admin users list"
```

---

## Done

All 4 tasks complete. Final test count: **47/47 passing**.

The feature is live at `/admin/users`. Admin accounts are protected from deletion. Non-admin users can be permanently erased with full GDPR cascade. Donated items in active auctions are anonymised rather than deleted, keeping live auctions running.
