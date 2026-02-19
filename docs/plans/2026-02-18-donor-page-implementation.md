# Donor Page Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a `/my-donations` page showing every item a logged-in user has donated, with stats, status details, and masked winner names.

**Architecture:** New `DonorController` + `app/Views/donor/my-donations.php`, data sourced from a new `ItemRepository::forDonor()` method (single JOIN query). Winner names masked via a shared `maskName()` helper extracted from `BidRepository`. Nav link added for all logged-in users.

**Design doc:** `docs/plans/2026-02-18-donor-page-design.md`

**Tech Stack:** PHP MVC, MariaDB, TailwindCSS, Galvani runtime

---

### Task 1: Extract `maskName()` to shared helper

`maskName()` currently lives as a private method in `BidRepository`. It needs to be accessible from `ItemRepository` too. Extract it to a global helper function so both repositories can use it.

**Files:**
- Modify: `app/Helpers/functions.php`
- Modify: `app/Repositories/BidRepository.php`

**Step 1: Write a failing test for `maskName()` as a standalone function**

Add to a new test class `tests/Unit/HelpersTest.php`:

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testMaskNameFullName(): void
    {
        $this->assertSame('Jane D.', maskName('Jane Doe'));
    }

    public function testMaskNameSingleWord(): void
    {
        $this->assertSame('Jane', maskName('Jane'));
    }

    public function testMaskNameMultipleWords(): void
    {
        $this->assertSame('Jane D.', maskName('Jane Mary Doe'));
    }

    public function testMaskNameEmpty(): void
    {
        $this->assertSame('', maskName(''));
    }
}
```

**Step 2: Run test to verify it fails**

```bash
./galvani auction/run-tests.php -- tests/Unit/HelpersTest.php --no-coverage
```

Expected: FAIL — `Call to undefined function maskName()`

**Step 3: Add `maskName()` to `app/Helpers/functions.php`**

Append to the end of the file:

```php
/**
 * Mask a full name to "First L." for privacy (e.g. "Jane Doe" → "Jane D.").
 * Single-word names are returned unchanged.
 */
function maskName(string $name): string
{
    $name  = trim($name);
    $parts = preg_split('/\s+/', $name);

    if (empty($parts) || $parts === [false] || $name === '') {
        return $name;
    }

    if (count($parts) === 1) {
        return $parts[0];
    }

    $first = $parts[0];
    $last  = $parts[count($parts) - 1];

    return $first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
}
```

**Step 4: Run test to verify it passes**

```bash
./galvani auction/run-tests.php -- tests/Unit/HelpersTest.php --no-coverage
```

Expected: 4 tests PASS

**Step 5: Replace `BidRepository::maskName()` with the helper**

In `app/Repositories/BidRepository.php`, delete the private `maskName()` method (lines ~203–220) and update every call site from `$this->maskName(...)` to `maskName(...)`.

There are two call sites — search for `$this->maskName` in the file and replace both.

**Step 6: Run full unit suite to confirm nothing broke**

```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```

Expected: all existing tests still PASS, plus the 4 new helper tests.

**Step 7: Commit**

```bash
git add app/Helpers/functions.php app/Repositories/BidRepository.php tests/Unit/HelpersTest.php
git commit -m "refactor: extract maskName() to shared helper function"
```

---

### Task 2: Add `ItemRepository::forDonor()`

New query method that fetches all items donated by a user, with joined event and winner data.

**Files:**
- Modify: `app/Repositories/ItemRepository.php`

**Step 1: Add the method**

Find the end of the public methods section in `app/Repositories/ItemRepository.php` and add:

```php
/**
 * Fetch all items donated by a user, ordered oldest-first.
 * Winner name is masked to "First L." for privacy.
 */
public function forDonor(int $userId): array
{
    $rows = $this->db->query(
        'SELECT items.*,
                e.title  AS event_title,
                e.slug   AS event_slug,
                e.status AS event_status,
                w.name   AS winner_name
         FROM   items
         LEFT   JOIN events e ON items.event_id = e.id
         LEFT   JOIN users  w ON items.winner_id = w.id
         WHERE  items.donor_id = ?
         ORDER  BY items.created_at ASC',
        [$userId]
    );

    foreach ($rows as &$row) {
        if (!empty($row['winner_name'])) {
            $row['winner_name'] = maskName($row['winner_name']);
        }
    }
    unset($row);

    return $rows;
}
```

**Step 2: Smoke test via Galvani CLI (optional sanity check)**

If you want to verify the query works before building the controller, you can hit the DB directly:

```bash
echo "SELECT id, title, status, donor_id FROM items WHERE donor_id IS NOT NULL LIMIT 5;" \
  | mysql --socket=../data/mysql.sock -u root --skip-ssl auction
```

Expected: rows for the test donor created during the donate-form smoke test.

**Step 3: Commit**

```bash
git add app/Repositories/ItemRepository.php
git commit -m "feat: add ItemRepository::forDonor() for donor dashboard"
```

---

### Task 3: Create `DonorController`

New controller with one method: `myDonations()`.

**Files:**
- Create: `app/Controllers/DonorController.php`

**Step 1: Create the controller**

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Repositories\ItemRepository;

class DonorController extends Controller
{
    private ItemRepository $items;

    public function __construct()
    {
        $this->items = new ItemRepository();
    }

    public function myDonations(): void
    {
        $user = getAuthUser();

        if ($user === null) {
            global $basePath;
            $this->redirect($basePath . '/login');
            return;
        }

        $donations = $this->items->forDonor((int)$user['id']);

        // Compute stats from the returned array — no extra queries
        $totalDonated = count($donations);
        $totalSold    = 0;
        $totalRaised  = 0.0;

        foreach ($donations as $item) {
            if ($item['status'] === 'sold') {
                $totalSold++;
                $totalRaised += (float)($item['current_bid'] ?? 0);
            }
        }

        $content = $this->renderView('donor/my-donations', [
            'user'         => $user,
            'donations'    => $donations,
            'totalDonated' => $totalDonated,
            'totalSold'    => $totalSold,
            'totalRaised'  => $totalRaised,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'My Donations',
            'activeNav' => 'my-donations',
            'user'      => $user,
            'content'   => $content,
        ]);
    }
}
```

**Step 2: Register the route in `index.php`**

In `index.php`, find where the other user-facing routes are registered (near `/my-bids`). Add:

```php
$donorController = new \App\Controllers\DonorController();
$router->get('/my-donations', [$donorController, 'myDonations']);
```

**Step 3: Commit**

```bash
git add app/Controllers/DonorController.php index.php
git commit -m "feat: add DonorController and /my-donations route"
```

---

### Task 4: Create the view `app/Views/donor/my-donations.php`

**Files:**
- Create: `app/Views/donor/my-donations.php`

**Step 1: Create the directory and view**

```bash
mkdir -p app/Views/donor
```

Then create `app/Views/donor/my-donations.php` with the following content:

```php
<?php
/**
 * My Donations page
 *
 * Variables from controller:
 *   $basePath (global)
 *   $user          — authenticated user
 *   $donations     — array of item rows (with event_title, event_slug, winner_name)
 *   $totalDonated  — int
 *   $totalSold     — int
 *   $totalRaised   — float
 */
global $basePath;
?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
</style>

<?= atom('page-header', [
    'title'    => 'My Donations',
    'subtitle' => 'Items you\'ve donated to WFCS auctions',
]) ?>

<?php if (empty($donations)): ?>

  <?= atom('empty-state', [
      'icon'    => '<svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
      'title'   => 'No donations yet',
      'message' => 'You haven\'t donated any items yet. Every donation helps raise funds for WFCS.',
      'action'  => '<a href="' . e($basePath) . '/donate" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Donate an item</a>',
  ]) ?>

<?php else: ?>

  <!-- Stats bar -->
  <div class="grid grid-cols-3 gap-4 mb-8 fade-up">
    <?= atom('stat-card', ['label' => 'Items donated', 'value' => $totalDonated]) ?>
    <?= atom('stat-card', ['label' => 'Items sold',    'value' => $totalSold]) ?>
    <?= atom('stat-card', ['label' => 'Total raised',  'value' => '£' . number_format($totalRaised, 0)]) ?>
  </div>

  <!-- Donation list -->
  <div class="space-y-3 fade-up delay-1">
    <?php foreach ($donations as $item): ?>
    <?php
      $status    = $item['status'] ?? 'pending';
      $thumb     = !empty($item['image'])
                   ? e($basePath) . '/uploads/thumbs/' . rawurlencode(basename($item['image']))
                   : null;

      // Badge config
      $badgeType = match($status) {
          'sold'    => 'success',
          'active'  => 'info',
          'pending' => 'warning',
          default   => 'default',
      };
      $badgeLabel = match($status) {
          'sold'    => 'Sold',
          'active'  => 'In auction',
          'pending' => 'Pending review',
          'ended'   => 'Auction ended',
          default   => ucfirst($status),
      };

      // Detail line
      if ($status === 'sold') {
          $soldDate   = !empty($item['updated_at'])
                        ? date('j M Y', strtotime($item['updated_at']))
                        : '';
          $soldAmount = '£' . number_format((float)($item['current_bid'] ?? 0), 0);
          $winner     = !empty($item['winner_name']) ? $item['winner_name'] : 'Unknown';
          $detail     = $soldAmount . ' raised &middot; Won by ' . e($winner) . ($soldDate ? ' &middot; ' . $soldDate : '');
      } elseif ($status === 'active') {
          $currentBid = (float)($item['current_bid'] ?? 0);
          $detail     = 'In auction' . ($currentBid > 0 ? ' &middot; £' . number_format($currentBid, 0) . ' current bid' : '');
      } elseif ($status === 'ended') {
          $detail = 'Auction ended &mdash; not sold';
      } else {
          $detail = 'Awaiting review by our team';
      }

      // Auction link
      $eventTitle = $item['event_title'] ?? null;
      $eventSlug  = $item['event_slug']  ?? null;
      $eventStatus = $item['event_status'] ?? null;
      $eventHtml  = '';
      if ($eventTitle !== null) {
          $canLink = in_array($eventStatus, ['published', 'active', 'ended', 'closed'], true);
          $eventHtml = $canLink
              ? '<a href="' . e($basePath) . '/events/' . e($eventSlug) . '" class="text-xs font-medium text-primary hover:underline">' . e($eventTitle) . '</a>'
              : '<span class="text-xs text-slate-400 dark:text-slate-500">' . e($eventTitle) . '</span>';
      }
    ?>
    <div class="flex gap-4 p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">

      <!-- Thumbnail -->
      <div class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-700">
        <?php if ($thumb): ?>
        <img src="<?= $thumb ?>" alt="" class="w-full h-full object-cover" loading="lazy" />
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-slate-300 dark:text-slate-600">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <?php endif; ?>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= e($item['title']) ?></p>
          <?= atom('badge', ['type' => $badgeType, 'label' => $badgeLabel]) ?>
        </div>

        <?php if ($eventHtml !== ''): ?>
        <p class="mt-0.5"><?= $eventHtml ?></p>
        <?php endif; ?>

        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $detail ?></p>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>
```

**Step 2: Verify the `atom()` calls match existing atoms**

Check `app/Views/atoms/` for: `page-header.php`, `empty-state.php`, `stat-card.php`, `badge.php`. All should exist from Phase 1c. If `empty-state` doesn't support an `action` key, check its signature and adapt.

**Step 3: Commit**

```bash
git add app/Views/donor/my-donations.php
git commit -m "feat: add My Donations view"
```

---

### Task 5: Add nav link in header and mobile menu

**Files:**
- Modify: `app/Views/partials/header-public.php`
- Modify: `app/Views/partials/mobile-menu.php`

**Step 1: Add to `header-public.php`**

Find the existing "My Bids" nav link and add "My Donations" immediately after it:

```php
<?php if ($user !== null): ?>
<a href="<?= e($basePath) ?>/my-donations"
   class="nav-link <?= ($activeNav ?? '') === 'my-donations' ? 'active text-primary' : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white' ?> text-sm font-semibold uppercase tracking-widest transition-colors pb-0.5">My Donations</a>
<?php endif; ?>
```

**Step 2: Add to `mobile-menu.php`**

Find the existing "My Bids" mobile link and add "My Donations" immediately after:

```php
<?php if ($user !== null): ?>
<a href="<?= e($basePath) ?>/my-donations"
   class="block px-3 py-2.5 text-sm font-semibold rounded-lg <?= ($activeNav ?? '') === 'my-donations' ? 'text-primary bg-primary/5' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors">My Donations</a>
<?php endif; ?>
```

**Step 3: Commit**

```bash
git add app/Views/partials/header-public.php app/Views/partials/mobile-menu.php
git commit -m "feat: add My Donations nav link for logged-in users"
```

---

### Task 6: Smoke test

**Step 1: Run the unit test suite to confirm nothing broken**

```bash
./galvani auction/run-tests.php -- tests/Unit/ --no-coverage
```

Expected: all tests pass (29 existing + 4 new helper tests = 33 total).

**Step 2: Browse to the page as a donor**

Start Galvani (`./galvani` from `/Users/waseem/Sites/www/`), then:

1. Log in as `jane.donor@example.com` (created during donate-form smoke test — password needs to be set first via the reset link, or temporarily set one directly in DB)
2. Navigate to `http://localhost:8080/auction/my-donations`
3. Verify:
   - Stats bar shows 1 donated, 0 sold, £0 raised
   - "Signed Football Shirt" appears with yellow "Pending review" badge
   - "Awaiting review by our team" detail line
   - No crash, no PHP warnings

**Step 3: Verify empty state**

Log in as a user who has never donated (e.g. `admin@wellfoundation.org.uk`) and navigate to `/my-donations`. Should see the empty state with "Donate an item" CTA.

**Step 4: Verify nav link visible when logged in**

Check that "My Donations" appears in the desktop nav and mobile menu when logged in, and is absent when logged out.

**Step 5: Commit any fixes, then final commit**

```bash
git add -p   # stage only relevant changes
git commit -m "fix: donor page smoke test fixes"
```
