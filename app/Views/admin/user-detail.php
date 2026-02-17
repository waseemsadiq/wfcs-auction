<?php
/**
 * Admin User Detail view.
 *
 * Variables:
 *   $profile    — user array
 *   $bids       — user's bids array
 *   $payments   — user's payments array
 *   $user       — authenticated admin
 *   $basePath   — global
 *   $csrfToken  — global
 */
global $basePath, $csrfToken;
?>
<style>
  @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .fade-up { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1 { animation-delay: 0.05s; }
  .delay-2 { animation-delay: 0.10s; }
</style>

<div class="fade-up mb-6 flex items-center gap-4">
  <a href="<?= e($basePath) ?>/admin/users" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </a>
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">User: <?= e($profile['name']) ?></h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

  <!-- Profile card -->
  <div class="fade-up bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-6">
    <div class="flex items-center gap-4 mb-5">
      <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
        <span class="text-xl font-black text-primary"><?= e(strtoupper(substr((string)($profile['name'] ?? 'U'), 0, 1))) ?></span>
      </div>
      <div>
        <p class="font-bold text-slate-900 dark:text-white"><?= e($profile['name']) ?></p>
        <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($profile['email']) ?></p>
      </div>
    </div>
    <dl class="space-y-3 text-sm">
      <div class="flex justify-between">
        <dt class="text-slate-500 dark:text-slate-400">Role</dt>
        <dd>
          <?php $r = $profile['role'] ?? 'bidder'; ?>
          <?php if ($r === 'admin'): ?>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Admin</span>
          <?php elseif ($r === 'donor'): ?>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">Donor</span>
          <?php else: ?>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Bidder</span>
          <?php endif; ?>
        </dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-slate-500 dark:text-slate-400">Email verified</dt>
        <dd class="text-xs font-medium <?= !empty($profile['email_verified_at']) ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' ?>">
          <?= !empty($profile['email_verified_at']) ? 'Yes' : 'No' ?>
        </dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-slate-500 dark:text-slate-400">Gift Aid</dt>
        <dd class="text-xs font-medium <?= !empty($profile['gift_aid_eligible']) ? 'text-green-600 dark:text-green-400' : 'text-slate-500 dark:text-slate-400' ?>">
          <?= !empty($profile['gift_aid_eligible']) ? 'Eligible' : 'Not eligible' ?>
        </dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-slate-500 dark:text-slate-400">Joined</dt>
        <dd class="text-slate-700 dark:text-slate-300"><?= e(date('j M Y', strtotime((string)($profile['created_at'] ?? 'now')))) ?></dd>
      </div>
    </dl>

    <!-- Change role form -->
    <?php if ($profile['role'] !== 'admin'): ?>
    <div class="mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/40">
      <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Change Role</p>
      <form method="POST" action="<?= e($basePath) ?>/admin/users/<?= e($profile['slug']) ?>">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="flex items-center gap-2">
          <select name="role" class="flex-1 px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors">
            <option value="bidder" <?= $profile['role'] === 'bidder' ? 'selected' : '' ?>>Bidder</option>
            <option value="donor"  <?= $profile['role'] === 'donor'  ? 'selected' : '' ?>>Donor</option>
          </select>
          <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Save</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bid history -->
  <div class="fade-up delay-1 lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/40">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Bid History (<?= count($bids) ?>)</h2>
    </div>
    <?php if (empty($bids)): ?>
    <div class="py-10 flex flex-col items-center text-slate-400">
      <p class="text-sm">No bids placed yet.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-100 dark:border-slate-700/40">
            <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Item</th>
            <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Amount</th>
            <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Status</th>
            <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
          <?php foreach ($bids as $bid): ?>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
            <td class="px-5 py-3">
              <a href="<?= e($basePath) ?>/items/<?= e($bid['item_slug'] ?? '') ?>" class="text-xs font-medium text-primary hover:underline"><?= e($bid['item_title'] ?? '—') ?></a>
            </td>
            <td class="px-4 py-3 text-right text-xs font-bold text-slate-900 dark:text-white">£<?= number_format((float)($bid['amount'] ?? 0), 0) ?></td>
            <td class="px-4 py-3">
              <?php $bidStatus = $bid['status'] ?? 'active'; ?>
              <span class="text-xs font-semibold <?= $bidStatus === 'winning' ? 'text-green-600 dark:text-green-400' : 'text-slate-500 dark:text-slate-400' ?>"><?= e(ucfirst($bidStatus)) ?></span>
            </td>
            <td class="px-5 py-3 text-right text-xs text-slate-400"><?= e(date('j M Y', strtotime((string)($bid['created_at'] ?? 'now')))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Payment history -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/40">
    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Payment History (<?= count($payments) ?>)</h2>
  </div>
  <?php if (empty($payments)): ?>
  <div class="py-10 flex flex-col items-center text-slate-400">
    <p class="text-sm">No payments yet.</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Item</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Event</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Amount</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Status</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($payments as $pay): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
          <td class="px-5 py-3 text-xs font-medium text-slate-900 dark:text-white"><?= e($pay['item_title'] ?? '—') ?></td>
          <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400"><?= e($pay['event_title'] ?? '—') ?></td>
          <td class="px-4 py-3 text-right text-xs font-bold text-slate-900 dark:text-white">£<?= number_format((float)($pay['amount'] ?? 0), 2) ?></td>
          <td class="px-4 py-3">
            <?php $ps = $pay['status'] ?? 'pending'; ?>
            <span class="text-xs font-semibold <?= $ps === 'completed' ? 'text-green-600 dark:text-green-400' : ($ps === 'pending' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') ?>"><?= e(ucfirst($ps)) ?></span>
          </td>
          <td class="px-5 py-3 text-right text-xs text-slate-400"><?= e(date('j M Y', strtotime((string)($pay['created_at'] ?? 'now')))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
