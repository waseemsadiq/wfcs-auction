<?php
/**
 * Admin Payments view.
 *
 * Variables:
 *   $payments      — array
 *   $total         — int total
 *   $page          — int
 *   $totalPages    — int
 *   $totalRevenue  — float
 *   $statusFilter  — string
 *   $events        — all events for filter
 *   $user          — admin
 *   $basePath      — global
 *   $csrfToken     — global
 */
global $basePath, $csrfToken;

$statusBadge = function(string $status): string {
    return match($status) {
        'completed' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Paid</span>',
        'pending'   => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Pending</span>',
        'failed'    => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Failed</span>',
        'refunded'  => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Refunded</span>',
        default     => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">' . e($status) . '</span>',
    };
};

$filterStatus  = $statusFilter  ?? '';
$filterEventId = (int)($filters['event_id'] ?? 0);
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1 { animation-delay: 0.06s; }
  .delay-2 { animation-delay: 0.12s; }
  .stat-card { transition: box-shadow 0.2s ease; }
  .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.07); }
  .dark .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.20); }
</style>

<!-- Page heading -->
<div class="fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Payments</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage winning bids, payment requests, and collected amounts.</p>
  </div>
</div>

<!-- Stats row -->
<div class="fade-up delay-1 grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5.5C13 4 11.5 3.5 10 3.5a4 4 0 0 0-4 4V17"/><line x1="5" y1="12" x2="14" y2="12"/><line x1="5" y1="17" x2="17" y2="17"/></svg>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white">£<?= number_format((float)$totalRevenue, 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Revenue</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= (int)$total ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Payments</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <p class="text-2xl font-black text-green-600 dark:text-green-400"><?= (int)($paymentStats['completed'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Completed</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= (int)($paymentStats['pending'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Pending</p>
  </div>
</div>

<!-- Filter bar -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-3 mb-4">
  <form method="GET" action="<?= e($basePath) ?>/admin/payments" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">
    <select name="status" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors">
      <option value="">All Statuses</option>
      <option value="pending"   <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
      <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
      <option value="failed"    <?= $filterStatus === 'failed'    ? 'selected' : '' ?>>Failed</option>
      <option value="refunded"  <?= $filterStatus === 'refunded'  ? 'selected' : '' ?>>Refunded</option>
    </select>
    <select name="event_id" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors">
      <option value="">All Events</option>
      <?php foreach ($events as $ev): ?>
      <option value="<?= (int)$ev['id'] ?>" <?= $filterEventId === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterStatus || $filterEventId): ?>
    <a href="<?= e($basePath) ?>/admin/payments" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 underline">Clear filters</a>
    <?php endif; ?>
  </form>
</div>

<!-- Payments table -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-8">
  <?php if (empty($payments)): ?>
  <div class="py-16 flex flex-col items-center text-slate-400">
    <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No payments found</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">User</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Item</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Event</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Amount</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Stripe ID</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Status</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($payments as $pay): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
          <td class="px-5 py-3.5">
            <p class="text-xs font-medium text-slate-900 dark:text-white"><?= e($pay['user_name'] ?? '—') ?></p>
            <p class="text-xs text-slate-400"><?= e($pay['user_email'] ?? '') ?></p>
          </td>
          <td class="px-4 py-3.5 text-xs text-slate-700 dark:text-slate-300"><?= e($pay['item_title'] ?? '—') ?></td>
          <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400"><?= e($pay['event_title'] ?? '—') ?></td>
          <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-900 dark:text-white">£<?= number_format((float)($pay['amount'] ?? 0), 2) ?></td>
          <td class="px-4 py-3.5 text-xs font-mono text-slate-400">
            <?php $sid = (string)($pay['stripe_session_id'] ?? ''); ?>
            <?= !empty($sid) ? e(substr($sid, 0, 12) . '…') : '—' ?>
          </td>
          <td class="px-4 py-3.5"><?= $statusBadge($pay['status'] ?? 'pending') ?></td>
          <td class="px-5 py-3.5 text-right text-xs text-slate-400"><?= e(date('j M Y', strtotime((string)($pay['created_at'] ?? 'now')))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
    <p class="text-xs text-slate-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?></p>
    <div class="flex gap-2">
      <?php $q = ['status' => $filterStatus, 'event_id' => $filterEventId ?: '']; ?>
      <?php if ($page > 1): ?>
      <a href="?<?= http_build_query(array_merge($q, ['page' => $page - 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_merge($q, ['page' => $page + 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
