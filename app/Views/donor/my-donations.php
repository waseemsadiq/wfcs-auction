<?php
/**
 * My Donations page — track all donated items for the authenticated user.
 *
 * Variables from DonorController:
 *   $basePath (global)
 *   $user          — authenticated user array
 *   $donations     — array of item rows (title, status, image, current_bid,
 *                    updated_at, event_title, event_slug, event_status, winner_name)
 *   $totalDonated  — int   total items donated
 *   $totalSold     — int   items that reached sold status
 *   $totalRaised   — float sum of winning bids on sold items
 */
global $basePath;

$linkableEventStatuses = ['published', 'active', 'ended', 'closed'];
?>

<style>
  /* ─── Page-load animations ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0);   }
  }
  .fade-up  { animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.08s; }
  .delay-2  { animation-delay: 0.16s; }
  .delay-3  { animation-delay: 0.24s; }
  .delay-4  { animation-delay: 0.32s; }
  .delay-5  { animation-delay: 0.40s; }

  /* ─── Stat card hover ─── */
  .stat-card {
    transition: transform 0.22s cubic-bezier(0.16,1,0.3,1), box-shadow 0.22s ease;
  }
  .stat-card:hover { transform: translateY(-1px); box-shadow: 0 3px 8px -2px rgba(0,0,0,.07); }
  .dark .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.20); }

  /* ─── Donation row card ─── */
  .donation-card {
    transition: transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;
  }
  .donation-card:hover { transform: translateY(-1px); box-shadow: 0 8px 24px -6px rgba(0,0,0,.10); }
  .dark .donation-card:hover { box-shadow: 0 8px 24px -6px rgba(0,0,0,.35); }
</style>

<!-- Page header -->
<div class="fade-up mb-8">
  <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-1.5">My Donations</h1>
  <p class="text-base text-slate-500 dark:text-slate-400">Track the items you have donated and how they performed at auction.</p>
</div>

<?php if (empty($donations)): ?>

<!-- ── Empty state ── -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm">
  <?php
  $icon        = '<svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>';
  $title       = "You haven't donated any items yet.";
  $description = 'Every donation helps raise funds for WFCS.';
  $action      = '<a href="' . e($basePath . '/donate') . '" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Donate an item</a>';
  include __DIR__ . '/../atoms/empty-state.php';
  ?>
</div>

<?php else: ?>

<!-- ── Stats row ── -->
<div class="fade-up delay-1 grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

  <!-- Items donated -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Items donated</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white"><?= (int)$totalDonated ?></p>
    </div>
  </div>

  <!-- Items sold -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Items sold</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white"><?= (int)$totalSold ?></p>
    </div>
  </div>

  <!-- Total raised -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/20 flex items-center justify-center flex-shrink-0">
      <span class="text-xl font-black text-violet-600 dark:text-violet-400">£</span>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Total raised</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white">£<?= number_format((float)$totalRaised, 0) ?></p>
    </div>
  </div>

</div>

<!-- ── Donation list ── -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-700/50">

  <?php foreach ($donations as $i => $item):
    $status      = $item['status'] ?? 'pending';
    $currentBid  = (float)($item['current_bid'] ?? 0);
    $updatedAt   = $item['updated_at'] ?? '';
    $eventStatus = $item['event_status'] ?? '';
    $isLinkable  = in_array($eventStatus, $linkableEventStatuses, true);

    // Badge params
    $badgeColor = match($status) {
        'pending' => 'amber',
        'active'  => 'blue',
        'sold'    => 'green',
        default   => 'gray',  // ended + anything else
    };
    $badgeLabel = match($status) {
        'pending' => 'Pending review',
        'active'  => 'In auction',
        'ended'   => 'Auction ended',
        'sold'    => 'Sold',
        default   => ucfirst($status),
    };

    // Row background tint for sold items
    $rowBg = $status === 'sold' ? 'bg-green-50/40 dark:bg-green-900/10' : '';

    // Detail line
    $soldDate = '';
    if ($updatedAt) {
        $ts = strtotime($updatedAt);
        $soldDate = $ts ? date('j M Y', $ts) : '';
    }
  ?>
  <div class="donation-card fade-up delay-<?= min($i + 2, 5) ?> flex flex-col sm:flex-row sm:items-center gap-4 p-5 <?= e($rowBg) ?>">

    <!-- Thumbnail -->
    <div class="flex-shrink-0">
      <div class="w-full sm:w-16 h-28 sm:h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700">
        <?php if (!empty($item['image'])): ?>
        <img
          src="<?= e($basePath . '/uploads/thumbs/' . basename($item['image'])) ?>"
          alt="<?= e($item['title'] ?? '') ?>"
          class="w-full h-full object-cover"
        />
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center">
          <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Main info -->
    <div class="flex-1 min-w-0">

      <!-- Title -->
      <p class="text-sm font-bold text-slate-900 dark:text-white leading-snug mb-1 truncate">
        <?= e($item['title'] ?? '') ?>
      </p>

      <!-- Event name -->
      <p class="text-xs text-slate-400 dark:text-slate-500 mb-1.5">
        <?php if ($isLinkable && !empty($item['event_slug'])): ?>
        <a href="<?= e($basePath . '/events/' . $item['event_slug']) ?>" class="hover:text-primary transition-colors">
          <?= e($item['event_title'] ?? '') ?>
        </a>
        <?php else: ?>
        <?= e($item['event_title'] ?? '') ?>
        <?php endif; ?>
      </p>

      <!-- Detail line -->
      <p class="text-xs text-slate-500 dark:text-slate-400">
        <?php if ($status === 'pending'): ?>
        Awaiting review by our team
        <?php elseif ($status === 'active'): ?>
        In auction<?php if ($currentBid > 0): ?> · <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e('£' . number_format($currentBid, 0)) ?></span> current bid<?php endif; ?>
        <?php elseif ($status === 'ended'): ?>
        Auction ended — not sold
        <?php elseif ($status === 'sold'): ?>
        <span class="font-bold text-green-700 dark:text-green-400"><?= e('£' . number_format($currentBid, 0)) ?></span> raised
        <?php if (!empty($item['winner_name'])): ?>
        · Won by <?= e($item['winner_name']) ?>
        <?php endif; ?>
        <?php if ($soldDate): ?>
        · <?= e($soldDate) ?>
        <?php endif; ?>
        <?php endif; ?>
      </p>

    </div>

    <!-- Status badge -->
    <div class="flex-shrink-0">
      <?php
      $color = $badgeColor;
      $label = $badgeLabel;
      include __DIR__ . '/../atoms/badge.php';
      ?>
    </div>

  </div>
  <?php endforeach; ?>

</div><!-- /donation list -->

<?php endif; ?>
