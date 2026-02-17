<?php
/**
 * My Bids page — track all bids for the authenticated user.
 *
 * Variables from controller:
 *   $basePath (global), $csrfToken (global)
 *   $user     — authenticated user array
 *   $bids     — array of enriched bid rows (from BidService::myBids)
 *   $total    — total bids count
 *   $page     — current page number
 *   $perPage  — items per page
 *   $pages    — total pages
 */
global $basePath, $csrfToken;

// Compute summary stats
$activeBids = 0;
$wonCount   = 0;
$totalBidAmount = 0.0;

foreach ($bids as $bid) {
    $label = $bid['label'] ?? '';
    if (in_array($label, ['Winning', 'Outbid'], true)) {
        $activeBids++;
    }
    if ($label === 'Won') {
        $wonCount++;
    }
    $totalBidAmount += (float)($bid['amount'] ?? 0);
}

// Partition bids into tab groups
$tabActive = [];
$tabWon    = [];
$tabOutbid = [];

foreach ($bids as $bid) {
    $label = $bid['label'] ?? '';
    if (in_array($label, ['Winning', 'Outbid'], true)) {
        $tabActive[] = $bid;
    }
    if ($label === 'Won') {
        $tabWon[] = $bid;
    }
    if ($label === 'Outbid') {
        $tabOutbid[] = $bid;
    }
}

// Outbid-only items where auction is still active (for "watched lots" alerts)
$outbidActive = array_filter($tabOutbid, fn($b) => ($b['item_status'] ?? '') === 'active');
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

  /* ─── Bid row card ─── */
  .bid-card {
    transition: transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;
  }
  .bid-card:hover { transform: translateY(-1px); box-shadow: 0 8px 24px -6px rgba(0,0,0,.10); }
  .dark .bid-card:hover { box-shadow: 0 8px 24px -6px rgba(0,0,0,.35); }

  /* ─── Tab active indicator ─── */
  .tab-btn {
    position: relative;
    transition: color 0.15s ease, background-color 0.15s ease;
  }
  .tab-btn::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 2px;
    background: #45a2da;
    border-radius: 2px 2px 0 0;
    transform: scaleX(0);
    transition: transform 0.2s ease;
  }
  .tab-btn.active::after { transform: scaleX(1); }
  .tab-btn.active { color: #45a2da; }

  /* ─── Tab panel ─── */
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  /* ─── Scrollbar ─── */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
  .dark ::-webkit-scrollbar-thumb { background: #334155; }
  [role="tablist"] { scrollbar-width: none; }
  [role="tablist"]::-webkit-scrollbar { display: none; }

  /* ─── Urgent pulse ─── */
  @keyframes urgentPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .6; }
  }
  .urgent-pulse { animation: urgentPulse 1.2s ease-in-out infinite; }

  /* ─── Countdown tick ─── */
  @keyframes tickPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .55; }
  }
  .tick { animation: tickPulse 1s ease-in-out infinite; }
</style>

<!-- Page header -->
<div class="fade-up mb-8">
  <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-1.5">My Bids</h1>
  <p class="text-base text-slate-500 dark:text-slate-400">Track your activity across all auctions.</p>
</div>

<?php if (!empty($outbidActive)): ?>
<!-- ── Outbid alerts (watched lots) ── -->
<div class="fade-up delay-1 mb-6">
  <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">Active — You've Been Outbid</h2>
  <div class="space-y-3">
    <?php foreach ($outbidActive as $alertBid): ?>
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-xl">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700">
          <?php if (!empty($alertBid['item_image'])): ?>
          <img src="<?= e($basePath . '/uploads/' . $alertBid['item_image']) ?>" alt="<?= e($alertBid['item_title'] ?? '') ?>" class="w-full h-full object-cover" />
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php endif; ?>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-0.5">
            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wide">Outbid</span>
          </div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= e($alertBid['item_title'] ?? '') ?></p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Your bid <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e(formatCurrency((float)$alertBid['amount'])) ?></span>
            <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
            Leading bid <span class="font-bold text-amber-700 dark:text-amber-400"><?= e(formatCurrency((float)($alertBid['current_bid'] ?? 0))) ?></span>
          </p>
        </div>
      </div>
      <a href="<?= e($basePath . '/items/' . $alertBid['item_slug']) ?>" class="flex-shrink-0 inline-flex items-center justify-center gap-1.5 px-5 py-2.5 text-sm font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 hover:bg-amber-200 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 rounded-xl transition-colors whitespace-nowrap">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
        Bid Again
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── Stats row ── -->
<div class="fade-up delay-1 grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

  <!-- Active Bids -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Active Bids</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white"><?= (int)$activeBids ?></p>
    </div>
  </div>

  <!-- Won -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15.5l-4-4 1.5-1.5L12 12.5l6.5-6.5L20 7.5 12 15.5z"/><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0"/></svg>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Won</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white"><?= (int)$wonCount ?></p>
    </div>
  </div>

  <!-- Total Bid -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm px-6 py-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5.5C13 4 11.5 3.5 10 3.5a4 4 0 0 0-4 4V17"/><line x1="5" y1="12" x2="14" y2="12"/><line x1="5" y1="17" x2="17" y2="17"/></svg>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Total Bid</p>
      <p class="text-3xl font-black text-slate-900 dark:text-white"><?= e(formatCurrency($totalBidAmount)) ?></p>
    </div>
  </div>

</div>

<!-- ── Tab bar ── -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-t-xl border border-slate-200 dark:border-slate-700/30 border-b-0 shadow-sm">
  <div class="flex border-b border-slate-200 dark:border-slate-700/50 overflow-x-auto" role="tablist">
    <button
      class="tab-btn active flex items-center gap-2 px-5 py-4 text-sm font-semibold whitespace-nowrap text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
      onclick="switchTab('active', this)"
      role="tab"
      aria-selected="true"
    >
      Active
      <?php if (count($tabActive) > 0): ?>
      <span class="px-1.5 py-0.5 text-xs font-bold rounded-md bg-primary/10 text-primary"><?= count($tabActive) ?></span>
      <?php endif; ?>
    </button>
    <button
      class="tab-btn flex items-center gap-2 px-5 py-4 text-sm font-semibold whitespace-nowrap text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
      onclick="switchTab('won', this)"
      role="tab"
      aria-selected="false"
    >
      Won
      <?php if (count($tabWon) > 0): ?>
      <span class="px-1.5 py-0.5 text-xs font-bold rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"><?= count($tabWon) ?></span>
      <?php endif; ?>
    </button>
    <button
      class="tab-btn flex items-center gap-2 px-5 py-4 text-sm font-semibold whitespace-nowrap text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
      onclick="switchTab('outbid', this)"
      role="tab"
      aria-selected="false"
    >
      Outbid
    </button>
    <button
      class="tab-btn flex items-center gap-2 px-5 py-4 text-sm font-semibold whitespace-nowrap text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
      onclick="switchTab('all', this)"
      role="tab"
      aria-selected="false"
    >
      All
      <?php if (count($bids) > 0): ?>
      <span class="px-1.5 py-0.5 text-xs font-bold rounded-md bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400"><?= count($bids) ?></span>
      <?php endif; ?>
    </button>
  </div>
</div>

<!-- ── Tab panels ── -->
<div class="bg-white dark:bg-slate-800 rounded-b-xl border border-slate-200 dark:border-slate-700/30 border-t-0 shadow-sm overflow-hidden">

  <!-- ── ACTIVE tab ── -->
  <div id="tab-active" class="tab-panel active divide-y divide-slate-100 dark:divide-slate-700/50">
    <?php if (empty($tabActive)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 15s1.5-2 4-2 4 2 4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
      </div>
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-2">No active bids yet</p>
      <p class="text-sm text-slate-400 dark:text-slate-500 max-w-xs leading-relaxed">Browse the live lots and place your first bid to get started.</p>
      <a href="<?= e($basePath . '/auctions') ?>" class="mt-6 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Browse Live Lots</a>
    </div>
    <?php else: ?>
    <?php foreach ($tabActive as $i => $bid):
      $label       = $bid['label'] ?? 'Outbid';
      $isWinning   = $label === 'Winning';
      $isOutbid    = $label === 'Outbid';
      $rowBg       = $isWinning ? '' : ($isOutbid ? 'bg-amber-50/40 dark:bg-amber-900/10' : '');
      $badgeCls    = $isWinning
        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
        : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400';
      $dotCls      = $isWinning ? 'bg-green-500' : 'bg-amber-500';
      $actionCls   = $isWinning
        ? 'text-white bg-primary hover:bg-primary-hover'
        : 'text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 hover:bg-amber-200 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-700';
      $actionLabel = $isWinning ? 'View Lot' : 'Bid Again';
      $currentBidCls = $isWinning
        ? 'font-bold text-slate-900 dark:text-white'
        : 'font-bold text-amber-700 dark:text-amber-400';
    ?>
    <div class="bid-card fade-up delay-<?= min($i + 2, 5) ?> flex flex-col sm:flex-row sm:items-center gap-4 p-5 <?= e($rowBg) ?>">
      <!-- Thumbnail -->
      <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="flex-shrink-0">
        <div class="relative w-full sm:w-20 h-36 sm:h-20 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700">
          <?php if (!empty($bid['item_image'])): ?>
          <img src="<?= e($basePath . '/uploads/' . $bid['item_image']) ?>" alt="<?= e($bid['item_title'] ?? '') ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php endif; ?>
        </div>
      </a>

      <!-- Main info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start gap-2 mb-1.5 flex-wrap">
          <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="text-sm font-bold text-slate-900 dark:text-white hover:text-primary transition-colors leading-snug">
            <?= e($bid['item_title'] ?? '') ?>
          </a>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-1.5"><?= e($bid['event_title'] ?? '') ?></p>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
          <div class="flex items-center gap-1">
            <span class="text-slate-400 dark:text-slate-500">Your bid</span>
            <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e(formatCurrency((float)$bid['amount'])) ?></span>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-slate-400 dark:text-slate-500">Current</span>
            <span class="<?= e($currentBidCls) ?>"><?= e(formatCurrency((float)($bid['current_bid'] ?? 0))) ?></span>
          </div>
        </div>
      </div>

      <!-- Status + action -->
      <div class="flex sm:flex-col items-center sm:items-end gap-3 sm:gap-2 flex-shrink-0">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= e($badgeCls) ?>">
          <span class="w-1.5 h-1.5 rounded-full <?= e($dotCls) ?> flex-shrink-0"></span>
          <?= e($label) ?>
        </span>
        <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="px-4 py-2 text-xs font-bold rounded-lg shadow-sm transition-colors whitespace-nowrap <?= e($actionCls) ?>">
          <?= e($actionLabel) ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /tab-active -->

  <!-- ── WON tab ── -->
  <div id="tab-won" class="tab-panel divide-y divide-slate-100 dark:divide-slate-700/50">
    <?php if (empty($tabWon)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 15s1.5-2 4-2 4 2 4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
      </div>
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-2">Nothing won yet</p>
      <p class="text-sm text-slate-400 dark:text-slate-500 max-w-xs leading-relaxed">Keep bidding! Won items will appear here once an auction closes.</p>
      <a href="<?= e($basePath . '/auctions') ?>" class="mt-6 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Browse Live Lots</a>
    </div>
    <?php else: ?>
    <?php foreach ($tabWon as $bid): ?>
    <div class="bid-card flex flex-col sm:flex-row sm:items-center gap-4 p-5 bg-green-50/40 dark:bg-green-900/10">
      <!-- Thumbnail -->
      <div class="flex-shrink-0">
        <div class="relative w-full sm:w-20 h-36 sm:h-20 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700">
          <?php if (!empty($bid['item_image'])): ?>
          <img src="<?= e($basePath . '/uploads/' . $bid['item_image']) ?>" alt="<?= e($bid['item_title'] ?? '') ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Main info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start gap-2 mb-1.5 flex-wrap">
          <span class="text-sm font-bold text-slate-900 dark:text-white leading-snug">
            <?= e($bid['item_title'] ?? '') ?>
          </span>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-1.5"><?= e($bid['event_title'] ?? '') ?></p>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
          <div class="flex items-center gap-1">
            <span class="text-slate-400 dark:text-slate-500">Won at</span>
            <span class="font-bold text-green-700 dark:text-green-400"><?= e(formatCurrency((float)$bid['amount'])) ?></span>
          </div>
        </div>
      </div>

      <!-- Status + action -->
      <div class="flex sm:flex-col items-center sm:items-end gap-3 sm:gap-2 flex-shrink-0">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          Won
        </span>
        <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="px-4 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition-colors whitespace-nowrap">
          View Item
        </a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /tab-won -->

  <!-- ── OUTBID tab ── -->
  <div id="tab-outbid" class="tab-panel divide-y divide-slate-100 dark:divide-slate-700/50">
    <?php if (empty($tabOutbid)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 15s1.5-2 4-2 4 2 4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
      </div>
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-2">No items here</p>
      <p class="text-sm text-slate-400 dark:text-slate-500 max-w-xs leading-relaxed">
        Items appear here when you've been outbid and didn't place a further bid. Keep bidding to stay in the running!
      </p>
      <a href="<?= e($basePath . '/auctions') ?>" class="mt-6 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Browse Live Lots</a>
    </div>
    <?php else: ?>
    <?php foreach ($tabOutbid as $bid): ?>
    <div class="bid-card flex flex-col sm:flex-row sm:items-center gap-4 p-5 bg-amber-50/40 dark:bg-amber-900/10">
      <!-- Thumbnail -->
      <div class="flex-shrink-0">
        <div class="relative w-full sm:w-20 h-36 sm:h-20 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700">
          <?php if (!empty($bid['item_image'])): ?>
          <img src="<?= e($basePath . '/uploads/' . $bid['item_image']) ?>" alt="<?= e($bid['item_title'] ?? '') ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Main info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start gap-2 mb-1.5 flex-wrap">
          <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="text-sm font-bold text-slate-900 dark:text-white hover:text-primary transition-colors leading-snug">
            <?= e($bid['item_title'] ?? '') ?>
          </a>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-1.5"><?= e($bid['event_title'] ?? '') ?></p>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
          <div class="flex items-center gap-1">
            <span class="text-slate-400 dark:text-slate-500">Your bid</span>
            <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e(formatCurrency((float)$bid['amount'])) ?></span>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-slate-400 dark:text-slate-500">Current</span>
            <span class="font-bold text-amber-700 dark:text-amber-400"><?= e(formatCurrency((float)($bid['current_bid'] ?? 0))) ?></span>
          </div>
          <?php
            $statusBid = $bid['item_status'] ?? 'active';
            $statusBadgeCls = match($statusBid) {
                'active' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                'ended'  => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
                'sold'   => 'bg-primary/10 text-primary',
                default  => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
            };
            $statusBadgeLabel = match($statusBid) {
                'active' => 'Live',
                'ended'  => 'Ended',
                'sold'   => 'Sold',
                default  => ucfirst($statusBid),
            };
          ?>
          <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-semibold <?= e($statusBadgeCls) ?>"><?= e($statusBadgeLabel) ?></span>
        </div>
      </div>

      <!-- Status + action -->
      <div class="flex sm:flex-col items-center sm:items-end gap-3 sm:gap-2 flex-shrink-0">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
          Outbid
        </span>
        <?php if ($statusBid === 'active'): ?>
        <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="px-4 py-2 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 hover:bg-amber-200 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-700 rounded-lg transition-colors whitespace-nowrap">
          Bid Again
        </a>
        <?php else: ?>
        <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="px-4 py-2 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors whitespace-nowrap">
          View Lot
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /tab-outbid -->

  <!-- ── ALL tab ── -->
  <div id="tab-all" class="tab-panel divide-y divide-slate-100 dark:divide-slate-700/50">
    <?php if (empty($bids)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 15s1.5-2 4-2 4 2 4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
      </div>
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-2">No bids yet</p>
      <p class="text-sm text-slate-400 dark:text-slate-500 max-w-xs leading-relaxed">You haven't placed any bids. Browse the live lots to get started.</p>
      <a href="<?= e($basePath . '/auctions') ?>" class="mt-6 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Browse Live Lots</a>
    </div>
    <?php else: ?>
    <?php foreach ($bids as $bid):
      $bidLabel = $bid['label'] ?? 'Outbid';
      $allBadgeCls = match($bidLabel) {
          'Winning' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
          'Won'     => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
          'Outbid'  => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
          'Ended'   => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
          'Lost'    => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
          default   => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
      };
      $allRowBg = ($bidLabel === 'Won') ? 'bg-green-50/40 dark:bg-green-900/10' : '';
    ?>
    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5 <?= e($allRowBg) ?>">
      <div class="flex-shrink-0">
        <div class="w-full sm:w-16 h-28 sm:h-16 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-700">
          <?php if (!empty($bid['item_image'])): ?>
          <img src="<?= e($basePath . '/uploads/' . $bid['item_image']) ?>" alt="<?= e($bid['item_title'] ?? '') ?>" class="w-full h-full object-cover" />
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <a href="<?= e($basePath . '/items/' . $bid['item_slug']) ?>" class="text-sm font-semibold text-slate-900 dark:text-white hover:text-primary transition-colors mb-1 truncate block">
          <?= e($bid['item_title'] ?? '') ?>
        </a>
        <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 flex-wrap">
          <?php if ($bidLabel === 'Won'): ?>
          <span>Won at: <span class="font-bold text-green-700 dark:text-green-400"><?= e(formatCurrency((float)$bid['amount'])) ?></span></span>
          <?php else: ?>
          <span>Your bid: <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e(formatCurrency((float)$bid['amount'])) ?></span></span>
          <span>Current: <span class="font-semibold text-slate-900 dark:text-white"><?= e(formatCurrency((float)($bid['current_bid'] ?? 0))) ?></span></span>
          <?php endif; ?>
          <span class="text-slate-400"><?= e($bid['event_title'] ?? '') ?></span>
        </div>
      </div>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0 <?= e($allBadgeCls) ?>"><?= e($bidLabel) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /tab-all -->

</div><!-- /tab panels wrapper -->

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="fade-up delay-5 mt-6 flex justify-center gap-2">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
  <a
    href="<?= e($basePath . '/my-bids?page=' . $p) ?>"
    class="px-3.5 py-1.5 text-sm font-medium rounded-lg <?= $p === $page ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors"
  ><?= (int)$p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Footer note -->
<p class="fade-up delay-5 text-xs text-slate-400 dark:text-slate-500 text-center mt-6 leading-relaxed">
  Outbid notifications are sent by email immediately.
  <a href="<?= e($basePath . '/account/settings') ?>" class="text-primary hover:underline font-medium">Manage notification preferences</a> in account settings.
</p>

<?php
$pageScripts = <<<'JS'
  // ── Tab switching ──
  function switchTab(name, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => {
      b.classList.remove('active');
      b.setAttribute('aria-selected', 'false');
    });
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    btn.setAttribute('aria-selected', 'true');
    const panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.add('active');
  }
JS;
?>
