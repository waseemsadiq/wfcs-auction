<?php
/**
 * Admin Items (Lots) list view.
 *
 * Variables:
 *   $items       — array of items (with category_name, event_title, donor_name, bid_count, reserve_price)
 *   $total       — int total items
 *   $page        — int current page
 *   $totalPages  — int
 *   $events      — all events for filter dropdown
 *   $categories  — all categories for filter dropdown
 *   $filters     — ['status','event_id','category_id','q'] from GET
 *   $user        — admin
 *   $basePath    — global
 *   $csrfToken   — global
 */
global $basePath, $csrfToken;

$statusBadge = function(string $status): string {
    return match($status) {
        'active'  => '<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Active</span>',
        'pending' => '<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Pending Approval</span>',
        'sold'    => '<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Sold</span>',
        'ended'   => '<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Ended</span>',
        default   => '<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">' . e($status) . '</span>',
    };
};

$filterStatus   = $filters['status']      ?? '';
$filterEventId  = (int)($filters['event_id']   ?? 0);
$filterCatId    = (int)($filters['category_id'] ?? 0);
$filterQ        = $filters['q'] ?? '';
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }

  /* Popovers */
  .form-popover::backdrop { background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); }
  .form-popover:popover-open { display: flex; flex-direction: column; }

  /* Popover positioning */
  .popover-sm  { position: fixed; inset: 0; width: min(28rem, calc(100% - 2rem)); height: fit-content; max-height: 90vh; margin: auto; overflow: hidden; }
  .popover-md  { position: fixed; inset: 0; width: min(32rem, calc(100% - 2rem)); height: fit-content; max-height: 90vh; margin: auto; overflow: hidden; }
  .popover-lg  { position: fixed; inset: 0; width: min(40rem, calc(100% - 2rem)); height: fit-content; max-height: 90vh; margin: auto; overflow: hidden; }

  /* Item row */
  .item-row { transition: background-color 0.12s ease; }
  .item-row.pending-row { background-color: rgba(255,251,235,0.3); }
  .dark .item-row.pending-row { background-color: rgba(120,60,0,0.05); }

  /* Toggle switch */
  .toggle-input ~ .toggle-track .toggle-knob { transform: translateX(0); }
  .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
  .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }

  /* Status filter pills */
  .status-filter-btn { transition: background-color 0.15s, color 0.15s; }
  .status-filter-btn.active-filter { background-color: #45a2da; color: #fff; }
</style>

<!-- Page header -->
<div class="fade-up flex items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Auction Lots</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5"><?= (int)$total ?> lot<?= $total !== 1 ? 's' : '' ?> across all events</p>
  </div>
  <button onclick="document.getElementById('add-item-popover').showPopover()" class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Item
  </button>
</div>

<!-- Filter bar -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-3 mb-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">
  <form method="GET" action="<?= e($basePath) ?>/admin/items" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap flex-1">
    <!-- Search -->
    <div class="relative flex-1 min-w-0 w-full sm:w-auto sm:max-w-xs">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" name="q" value="<?= e($filterQ) ?>" placeholder="Search lots&hellip;" class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" />
    </div>
    <!-- Event filter -->
    <select name="event_id" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors">
      <option value="">All Auctions</option>
      <?php foreach ($events as $ev): ?>
      <option value="<?= (int)$ev['id'] ?>" <?= $filterEventId === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <!-- Category filter -->
    <select name="category_id" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= (int)$cat['id'] ?>" <?= $filterCatId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterStatus || $filterEventId || $filterCatId || $filterQ): ?>
    <a href="<?= e($basePath) ?>/admin/items" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors underline">Clear filters</a>
    <?php endif; ?>
  </form>

  <!-- Status pills (JS-driven client-side filter) -->
  <div class="flex items-center gap-1.5 flex-wrap">
    <button onclick="setStatusFilter(this,'all')" class="status-filter-btn active-filter px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">All</button>
    <button onclick="setStatusFilter(this,'active')" class="status-filter-btn px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">Active</button>
    <button onclick="setStatusFilter(this,'pending')" class="status-filter-btn px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">Pending</button>
    <button onclick="setStatusFilter(this,'ended')" class="status-filter-btn px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">Ended</button>
    <button onclick="setStatusFilter(this,'sold')" class="status-filter-btn px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">Sold</button>
  </div>
</div>

<!-- Batch actions bar (hidden by default) -->
<div id="batchBar" class="hidden fade-up mb-3 bg-slate-900 dark:bg-slate-700 rounded-xl px-4 py-3 flex items-center gap-3 flex-wrap">
  <span id="batchCount" class="text-xs font-bold text-slate-300 mr-1">0 selected</span>
  <button class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-green-300 bg-green-900/40 hover:bg-green-900/70 rounded-lg transition-colors border border-green-700/50">
    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    Approve Selected
  </button>
  <button class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-300 bg-amber-900/40 hover:bg-amber-900/70 rounded-lg transition-colors border border-amber-700/50">
    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    End Selected
  </button>
  <button popovertarget="confirm-delete-popover" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-300 bg-red-900/40 hover:bg-red-900/70 rounded-lg transition-colors border border-red-700/50">
    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    Delete Selected
  </button>
  <button onclick="clearSelection()" class="ml-auto text-xs font-medium text-slate-400 hover:text-slate-200 transition-colors">Clear</button>
</div>

<!-- Items table -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-8">
  <?php if (empty($items)): ?>
  <div class="py-16 flex flex-col items-center justify-center text-slate-400">
    <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No items found</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Try adjusting the filters above.</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[960px]">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="px-4 py-3 w-10">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="toggle-input sr-only" />
              <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
                <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
              </div>
            </label>
          </th>
          <th class="px-2 py-3 w-12"></th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Item Name</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Category</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Donor</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Starting</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Current</th>
          <th class="text-center text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Bids</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Reserve</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Status</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($items as $item): ?>
        <?php $isPending = ($item['status'] ?? '') === 'pending'; ?>
        <?php $reserveMet = !empty($item['reserve_price']) && ((float)($item['current_bid'] ?? 0)) >= (float)$item['reserve_price']; ?>
        <tr class="item-row <?= $isPending ? 'pending-row hover:bg-amber-50/50 dark:hover:bg-amber-900/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/30' ?> transition-colors"
            data-status="<?= e($item['status'] ?? '') ?>">
          <td class="px-4 py-3.5">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" onchange="handleRowCheck()" class="row-check toggle-input sr-only" data-slug="<?= e($item['slug']) ?>" />
              <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
                <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
              </div>
            </label>
          </td>
          <td class="px-2 py-3.5">
            <?php if (!empty($item['image'])): ?>
            <img src="<?= e('/uploads/' . $item['image']) ?>" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0 bg-slate-100 dark:bg-slate-700" />
            <?php else: ?>
            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <?php endif; ?>
          </td>
          <td class="px-3 py-3.5">
            <p class="font-semibold text-slate-900 dark:text-white text-sm"><?= e($item['title']) ?></p>
            <?php if (!empty($item['lot_number'])): ?>
            <p class="text-xs text-slate-400">Lot #<?= (int)$item['lot_number'] ?></p>
            <?php endif; ?>
          </td>
          <td class="px-3 py-3.5">
            <?php if (!empty($item['category_name'])): ?>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300"><?= e($item['category_name']) ?></span>
            <?php else: ?>
            <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>
          <td class="px-3 py-3.5 text-xs text-slate-500 dark:text-slate-400"><?= e($item['donor_name'] ?? '—') ?></td>
          <td class="px-3 py-3.5 text-right text-xs font-medium text-slate-600 dark:text-slate-300">£<?= number_format((float)($item['starting_bid'] ?? 0), 0) ?></td>
          <td class="px-3 py-3.5 text-right">
            <?php $hasBids = (int)($item['bid_count'] ?? 0) > 0; ?>
            <span class="text-sm font-bold <?= $hasBids ? 'text-slate-900 dark:text-white' : 'text-slate-400' ?>">
              £<?= number_format((float)($item['current_bid'] ?? 0), 0) ?>
            </span>
          </td>
          <td class="px-3 py-3.5 text-center">
            <span class="text-xs font-semibold <?= $hasBids ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400' ?>"><?= (int)($item['bid_count'] ?? 0) ?></span>
          </td>
          <td class="px-3 py-3.5 text-right text-xs text-slate-500 dark:text-slate-400">
            <?= !empty($item['reserve_price']) ? '£' . number_format((float)$item['reserve_price'], 0) : '—' ?>
          </td>
          <td class="px-3 py-3.5">
            <div class="flex items-center gap-1.5 flex-wrap">
              <?= $statusBadge($item['status'] ?? 'pending') ?>
              <?php if ($reserveMet): ?>
              <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600 dark:text-green-400">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Reserve Met
              </span>
              <?php elseif (!$hasBids && $item['status'] === 'active'): ?>
              <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">No bids</span>
              <?php endif; ?>
            </div>
          </td>
          <td class="px-4 py-3.5">
            <div class="flex items-center justify-end gap-1.5">
              <?php if ($isPending): ?>
              <form method="POST" action="<?= e($basePath) ?>/admin/items/<?= e($item['slug']) ?>/approve">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700/40 hover:bg-green-100 dark:hover:bg-green-900/40 rounded-lg transition-colors">Approve</button>
              </form>
              <form method="POST" action="<?= e($basePath) ?>/admin/items/<?= e($item['slug']) ?>/reject">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors">Reject</button>
              </form>
              <?php else: ?>
              <a href="<?= e($basePath) ?>/admin/items/<?= e($item['slug']) ?>/edit" class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Edit</a>
              <a href="<?= e($basePath) ?>/auctions/<?= e($item['event_slug'] ?? '') ?>/<?= e($item['slug']) ?>" target="_blank" class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">View</a>
              <?php endif; ?>
            </div>
          </td>
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
      <a href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════ POPOVERS ══ -->

<!-- Confirm Delete -->
<div id="confirm-delete-popover" popover="manual" class="form-popover popover-sm rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="p-6">
    <div class="flex items-start gap-4 mb-5">
      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
        <svg class="w-5 h-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Delete selected items?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">This cannot be undone. All bid history for these lots will also be removed.</p>
      </div>
    </div>
    <div class="flex items-center justify-end gap-3">
      <button onclick="document.getElementById('confirm-delete-popover').hidePopover()" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white border border-slate-200 dark:border-slate-600 rounded-lg transition-colors">Cancel</button>
      <button onclick="document.getElementById('confirm-delete-popover').hidePopover()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
    </div>
  </div>
</div>

<!-- Confirm Approve -->
<div id="confirm-approve-popover" popover="manual" class="form-popover popover-sm rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="p-6">
    <div class="flex items-start gap-4 mb-5">
      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Approve <span id="approve-item-name"></span>?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">This lot will go live for bidding immediately. The donor will be notified.</p>
      </div>
    </div>
    <div class="flex items-center justify-end gap-3">
      <button onclick="document.getElementById('confirm-approve-popover').hidePopover()" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white border border-slate-200 dark:border-slate-600 rounded-lg transition-colors">Cancel</button>
      <form id="approve-form" method="POST" action="" class="inline">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">Approve</button>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Reject -->
<div id="confirm-reject-popover" popover="manual" class="form-popover popover-sm rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="p-6">
    <div class="flex items-start gap-4 mb-4">
      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
        <svg class="w-5 h-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Reject <span id="reject-item-name"></span>?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">The donor will be notified. Optionally add a reason below.</p>
      </div>
    </div>
    <form id="reject-form" method="POST" action="">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <textarea id="reject-reason" name="reason" rows="3" placeholder="Reason for rejection (optional)&hellip;" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary resize-none mb-4"></textarea>
      <div class="flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('confirm-reject-popover').hidePopover()" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white border border-slate-200 dark:border-slate-600 rounded-lg transition-colors">Cancel</button>
        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Reject</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Item -->
<div id="add-item-popover" popover="manual" class="form-popover popover-lg rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex-shrink-0">
    <h2 class="text-base font-bold text-slate-900 dark:text-white">Add Auction Lot</h2>
    <button onclick="document.getElementById('add-item-popover').hidePopover()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <form method="POST" action="<?= e($basePath) ?>/admin/items" enctype="multipart/form-data" class="overflow-y-auto flex-1">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="px-6 py-5">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Item Name</label>
          <input type="text" name="title" placeholder="e.g. Rolex Submariner" required class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Category</label>
          <select name="category_id" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors">
            <option value="">Select&hellip;</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Auction</label>
          <select name="event_id" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors">
            <option value="">Select&hellip;</option>
            <?php foreach ($events as $ev): ?>
            <option value="<?= (int)$ev['id'] ?>"><?= e($ev['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Donor</label>
          <input type="text" name="donor_name" placeholder="Donor name or organisation" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" />
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
          <textarea name="description" rows="3" placeholder="Describe the lot for bidders&hellip;" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary resize-none transition-colors"></textarea>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Starting Bid (£)</label>
          <input type="number" name="starting_bid" min="0" step="1" placeholder="0" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Reserve Price (£)</label>
          <input type="number" name="reserve_price" min="0" step="1" placeholder="0" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors" />
        </div>
        <div class="col-span-2">
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Images</label>
          <div class="border-2 border-dashed border-slate-200 dark:border-slate-600 rounded-xl p-6 text-center">
            <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <p class="text-sm text-slate-400 mb-1">Drop images here, or <label for="add-item-image" class="text-primary font-medium cursor-pointer">browse</label></p>
            <p class="text-xs text-slate-400">Max 5MB per image</p>
            <input type="file" id="add-item-image" name="image" accept="image/*" class="sr-only" />
          </div>
        </div>
      </div>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex-shrink-0 flex justify-end gap-3">
      <button type="button" onclick="document.getElementById('add-item-popover').hidePopover()" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Cancel</button>
      <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Add Lot</button>
    </div>
  </form>
</div>

<script>
// ── Status filter pills ──────────────────────────────────────────────────────
function setStatusFilter(btn, status) {
  document.querySelectorAll('.status-filter-btn').forEach(b => b.classList.remove('active-filter'));
  btn.classList.add('active-filter');
  document.querySelectorAll('.item-row').forEach(row => {
    if (status === 'all' || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// ── Batch selection ──────────────────────────────────────────────────────────
function toggleSelectAll(master) {
  const checks = document.querySelectorAll('.row-check');
  checks.forEach(c => { c.checked = master.checked; });
  updateBatchBar();
}

function handleRowCheck() {
  const all = document.querySelectorAll('.row-check');
  const checked = document.querySelectorAll('.row-check:checked');
  document.getElementById('selectAll').checked = all.length === checked.length;
  updateBatchBar();
}

function updateBatchBar() {
  const count = document.querySelectorAll('.row-check:checked').length;
  const bar = document.getElementById('batchBar');
  document.getElementById('batchCount').textContent = count + ' selected';
  if (count > 0) {
    bar.classList.remove('hidden');
  } else {
    bar.classList.add('hidden');
  }
}

function clearSelection() {
  document.querySelectorAll('.row-check, #selectAll').forEach(c => { c.checked = false; });
  document.getElementById('batchBar').classList.add('hidden');
}
</script>
