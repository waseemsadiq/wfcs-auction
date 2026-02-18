<?php
/**
 * Admin Gift Aid view.
 *
 * Variables:
 *   $claims        — array of gift aid claims
 *   $total         — int total claims
 *   $page          — int
 *   $totalPages    — int
 *   $giftAidStats  — [total_claimed, total_payments_eligible, total_amount]
 *   $user          — admin
 *   $basePath      — global
 *   $csrfToken     — global
 */
global $basePath, $csrfToken;
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
  .stat-card { transition: box-shadow 0.2s ease; }
  .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.07); }
  .dark .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.20); }
  .form-popover::backdrop { background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); }
  .form-popover:popover-open { display: flex; flex-direction: column; }
  .popover-md { position: fixed; inset: 0; width: min(32rem, calc(100% - 2rem)); height: fit-content; max-height: 90vh; margin: auto; overflow: hidden; }
</style>

<!-- Page heading -->
<div class="fade-up mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gift Aid</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">HMRC declarations and claimable amounts</p>
  </div>
  <button onclick="document.getElementById('hmrc-export-popover').showPopover()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg transition-colors flex-shrink-0">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export for HMRC Submission
  </button>
</div>

<!-- Stats row -->
<div class="fade-up delay-1 grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5.5C13 4 11.5 3.5 10 3.5a4 4 0 0 0-4 4V17"/><line x1="5" y1="12" x2="14" y2="12"/><line x1="5" y1="17" x2="17" y2="17"/></svg>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white">£<?= number_format((float)($giftAidStats['total_claimed'] ?? 0), 2) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Claimable</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center mb-3">
      <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= (int)($giftAidStats['total_payments_eligible'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Declarations</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-5">
    <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
      <span class="text-base font-black text-slate-500 dark:text-slate-400">£</span>
    </div>
    <p class="text-2xl font-black text-slate-900 dark:text-white">£<?= number_format((float)($giftAidStats['total_amount'] ?? 0), 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Donation Amount</p>
  </div>
</div>

<!-- Claims table -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-8">
  <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/40">
    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Gift Aid Declarations (<?= (int)$total ?>)</h2>
  </div>
  <?php if (empty($claims)): ?>
  <div class="py-16 flex flex-col items-center text-slate-400">
    <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No Gift Aid claims yet</p>
    <p class="text-xs text-slate-400 mt-1">Claims will appear when winners with Gift Aid eligibility complete payment.</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Donor</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Item</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Bid Amount</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Gift Aid Amount</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($claims as $claim): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
          <td class="px-5 py-3.5">
            <p class="text-xs font-medium text-slate-900 dark:text-white"><?= e($claim['user_name'] ?? '—') ?></p>
            <p class="text-xs text-slate-400"><?= e($claim['user_email'] ?? '') ?></p>
          </td>
          <td class="px-4 py-3.5 text-xs text-slate-700 dark:text-slate-300"><?= e($claim['item_title'] ?? '—') ?></td>
          <td class="px-4 py-3.5 text-right text-xs font-bold text-slate-900 dark:text-white">£<?= number_format((float)($claim['payment_amount'] ?? 0), 2) ?></td>
          <td class="px-4 py-3.5 text-right text-xs font-bold text-green-600 dark:text-green-400">£<?= number_format((float)($claim['gift_aid_amount'] ?? 0), 2) ?></td>
          <td class="px-5 py-3.5 text-right text-xs text-slate-400"><?= e(date('j M Y', strtotime((string)($claim['payment_date'] ?? 'now')))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
    <p class="text-xs text-slate-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?></p>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- HMRC Export Popover -->
<div id="hmrc-export-popover" popover="manual" class="form-popover popover-md rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between flex-shrink-0">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Export Gift Aid Data</h3>
    <button type="button" onclick="document.getElementById('hmrc-export-popover').hidePopover()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="px-6 py-5">
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">This will download a CSV file with all Gift Aid declarations, formatted for HMRC submission.</p>
    <form method="POST" action="<?= e($basePath) ?>/admin/gift-aid/export">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <div class="flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('hmrc-export-popover').hidePopover()" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
        <button type="submit" class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download CSV
        </button>
      </div>
    </form>
  </div>
</div>
