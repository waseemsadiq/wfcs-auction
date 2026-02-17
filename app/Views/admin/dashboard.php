<?php
/**
 * Admin Dashboard view.
 *
 * Variables available:
 *   $stats         — assoc array: event_count, active_item_count, bid_count_today,
 *                    total_revenue, gift_aid_total, pending_payment_count, pending_item_count,
 *                    bidder_count, bidder_gift_aid_count
 *   $recentBids    — array of recent bids (with item_title, category_name, user_name, amount, created_at)
 *   $liveEvent     — the currently active/live event or null
 *   $user          — authenticated admin user
 *   $basePath      — base URL path (global)
 */
global $basePath, $csrfToken;
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
  .delay-3  { animation-delay: 0.18s; }

  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(69,162,218,.55); }
    50%       { box-shadow: 0 0 0 5px rgba(69,162,218,0); }
  }
  .live-dot { animation: livePulse 1.8s ease-in-out infinite; }

  .stat-card { transition: box-shadow 0.2s ease; }
  .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.07); }
  .dark .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.20); }
</style>

<!-- Page heading -->
<div class="fade-up mb-4">
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard</h1>
</div>

<?php if ($liveEvent): ?>
<!-- Live Event Banner -->
<div class="fade-up delay-1 bg-slate-900 dark:bg-slate-700/60 rounded-xl border border-slate-700 dark:border-slate-600/50 px-6 py-5 flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
  <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center flex-shrink-0">
    <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
  </div>
  <div class="flex-1 min-w-0">
    <div class="flex items-center gap-2 mb-0.5">
      <p class="text-sm font-bold text-white"><?= e($liveEvent['title']) ?></p>
      <span class="flex-shrink-0 flex items-center gap-1.5 px-2 py-0.5 bg-primary/20 rounded-full">
        <span class="live-dot w-1.5 h-1.5 rounded-full bg-primary"></span>
        <span class="text-xs font-bold text-primary uppercase tracking-widest">Live</span>
      </span>
    </div>
    <p class="text-xs text-slate-400"><?= e($liveEvent['venue'] ?? 'Online') ?> &middot; <?= (int)($stats['active_item_count'] ?? 0) ?> lots &middot; <?= (int)($stats['bidder_count'] ?? 0) ?> bidders registered</p>
  </div>
  <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
    <a href="<?= e($basePath) ?>/admin/auctions" class="px-4 py-2 text-xs font-semibold text-white border border-slate-600 hover:border-slate-400 rounded-lg transition-colors">Manage Auction</a>
    <a href="<?= e($basePath) ?>/auctioneer" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Auctioneer Panel</a>
  </div>
</div>
<?php endif; ?>

<!-- Stats row -->
<div class="fade-up delay-2 grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

  <!-- Total Revenue -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="flex items-start justify-between mb-3">
      <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5.5C13 4 11.5 3.5 10 3.5a4 4 0 0 0-4 4V17"/><line x1="5" y1="12" x2="14" y2="12"/><line x1="5" y1="17" x2="17" y2="17"/></svg>
      </div>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white">£<?= number_format((float)($stats['total_revenue'] ?? 0), 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Raised</p>
  </div>

  <!-- Active Lots -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="flex items-start justify-between mb-3">
      <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
        <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      </div>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= (int)($stats['active_item_count'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Active Lots</p>
  </div>

  <!-- Registered Bidders -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="flex items-start justify-between mb-3">
      <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
        <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= (int)($stats['bidder_count'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Registered Bidders</p>
    <?php if (!empty($stats['bidder_gift_aid_count'])): ?>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"><?= (int)$stats['bidder_gift_aid_count'] ?> Gift Aid eligible</p>
    <?php endif; ?>
  </div>

  <!-- Payments Pending -->
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-700/40 p-5">
    <div class="flex items-start justify-between mb-3">
      <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <?php if (($stats['pending_payment_count'] ?? 0) > 0): ?>
      <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded-full">Action needed</span>
      <?php endif; ?>
    </div>
    <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= (int)($stats['pending_payment_count'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Payments Pending</p>
  </div>
</div>

<!-- Two-column layout -->
<div class="fade-up delay-3 grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

  <!-- Left: Bid Activity table (2/3) -->
  <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700/40">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Bid Activity</h2>
      <span class="text-xs text-slate-400"><?= (int)($stats['bid_count_today'] ?? 0) ?> bids today</span>
    </div>
    <?php if (empty($recentBids)): ?>
    <div class="py-12 flex flex-col items-center justify-center text-slate-400">
      <svg class="w-10 h-10 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      <p class="text-sm">No bids yet</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-100 dark:border-slate-700/40">
            <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Item</th>
            <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Bidder</th>
            <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Amount</th>
            <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Time</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
          <?php foreach ($recentBids as $bid): ?>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-5 py-3.5">
              <p class="font-medium text-slate-900 dark:text-white text-xs"><?= e($bid['item_title']) ?></p>
              <p class="text-xs text-slate-400"><?= e($bid['category_name'] ?? '') ?></p>
            </td>
            <td class="px-4 py-3.5 text-xs font-mono text-slate-500 dark:text-slate-400">
              <?php
              $name = (string)($bid['bidder_name'] ?? '');
              $parts = explode(' ', $name);
              $masked = !empty($parts[0]) ? strtoupper(substr($parts[0], 0, 1)) . '***' . (count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '') : '***';
              echo e($masked);
              ?>
            </td>
            <td class="px-4 py-3.5 text-right">
              <span class="text-xs font-bold text-slate-900 dark:text-white">£<?= number_format((float)($bid['amount'] ?? 0), 0) ?></span>
            </td>
            <td class="px-5 py-3.5 text-right text-xs text-slate-400"><?= e(date('g:i A', strtotime((string)($bid['created_at'] ?? 'now')))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right sidebar (1/3) -->
  <div class="flex flex-col gap-4">

    <!-- Stats summary -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Overview</h3>
      <div class="space-y-3">
        <div class="flex justify-between items-center">
          <span class="text-xs text-slate-500 dark:text-slate-400">Total Auctions</span>
          <span class="text-xs font-bold text-slate-900 dark:text-white"><?= (int)($stats['event_count'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-xs text-slate-500 dark:text-slate-400">Active Lots</span>
          <span class="text-xs font-bold text-slate-900 dark:text-white"><?= (int)($stats['active_item_count'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-xs text-slate-500 dark:text-slate-400">Bids Today</span>
          <span class="text-xs font-bold text-slate-900 dark:text-white"><?= (int)($stats['bid_count_today'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-xs text-slate-500 dark:text-slate-400">Gift Aid Total</span>
          <span class="text-xs font-bold text-green-600 dark:text-green-400">£<?= number_format((float)($stats['gift_aid_total'] ?? 0), 2) ?></span>
        </div>
      </div>
    </div>

    <!-- Pending Actions -->
    <?php $pendingItems = (int)($stats['pending_item_count'] ?? 0); $pendingPayments = (int)($stats['pending_payment_count'] ?? 0); ?>
    <?php if ($pendingItems > 0 || $pendingPayments > 0): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-700/40 p-5">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Pending Actions</h3>
      <ul class="space-y-2.5 mb-4">
        <?php if ($pendingPayments > 0): ?>
        <li class="flex items-start gap-2.5">
          <div class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0 mt-0.5">
            <span class="text-xs font-bold text-amber-600 dark:text-amber-400"><?= $pendingPayments ?></span>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">Winners awaiting payment request</p>
        </li>
        <?php endif; ?>
        <?php if ($pendingItems > 0): ?>
        <li class="flex items-start gap-2.5">
          <div class="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
            <span class="text-xs font-bold text-primary"><?= $pendingItems ?></span>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">Item<?= $pendingItems > 1 ? 's' : '' ?> awaiting admin approval</p>
        </li>
        <?php endif; ?>
      </ul>
      <div class="flex flex-col gap-2">
        <?php if ($pendingPayments > 0): ?>
        <a href="<?= e($basePath) ?>/admin/payments" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/40 rounded-lg transition-colors border border-amber-200 dark:border-amber-700/40">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          View Pending Payments
        </a>
        <?php endif; ?>
        <?php if ($pendingItems > 0): ?>
        <a href="<?= e($basePath) ?>/admin/items" class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors border border-primary/20">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Approve Items
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-green-200 dark:border-green-700/40 p-5">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
          <svg class="w-4 h-4 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <p class="text-sm font-semibold text-green-700 dark:text-green-400">All clear</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">No pending actions</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick links -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Quick Actions</h3>
      <div class="flex flex-col gap-2">
        <a href="<?= e($basePath) ?>/admin/auctions" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg transition-colors">
          <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Manage Auctions
        </a>
        <a href="<?= e($basePath) ?>/admin/items" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg transition-colors">
          <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          Manage Items
        </a>
        <a href="<?= e($basePath) ?>/admin/gift-aid" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-lg transition-colors">
          <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Gift Aid Report
        </a>
      </div>
    </div>

  </div>
</div>
