<?php
/**
 * Admin Auctions list view.
 *
 * Variables:
 *   $events      — array of events (with item_count appended)
 *   $total       — int total events
 *   $page        — int current page
 *   $totalPages  — int total pages
 *   $user        — authenticated admin
 *   $basePath    — global
 *   $csrfToken   — global
 */
global $basePath, $csrfToken;

$statusBadge = function(string $status): string {
    return match($status) {
        'active'    => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Active</span>',
        'published' => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Published</span>',
        'draft'     => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Draft</span>',
        'ended'     => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Ended</span>',
        'closed'    => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Closed</span>',
        default     => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">' . e($status) . '</span>',
    };
};
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.06s; }

  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(69,162,218,.55); }
    50%       { box-shadow: 0 0 0 5px rgba(69,162,218,0); }
  }
  .live-dot { animation: livePulse 1.8s ease-in-out infinite; }

  .form-popover::backdrop { background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); }
  .form-popover:popover-open { display: flex; flex-direction: column; }
  .popover-xl { position: fixed; inset: 0; width: min(36rem, calc(100% - 2rem)); height: fit-content; max-height: 90vh; margin: auto; overflow: hidden; }
</style>

<!-- Page heading + action -->
<div class="fade-up flex items-start justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Auctions</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage all fundraising auctions and their lots.</p>
  </div>
  <button onclick="document.getElementById('create-event-popover').showPopover()" class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Auction
  </button>
</div>

<!-- Auctions table -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-8">
  <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">All Auctions</h2>
    <span class="text-xs text-slate-400"><?= (int)$total ?> auction<?= $total !== 1 ? 's' : '' ?></span>
  </div>
  <?php if (empty($events)): ?>
  <div class="py-16 flex flex-col items-center justify-center text-slate-400">
    <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No auctions yet</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Create your first auction to get started.</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Auction Name</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Date</th>
          <th class="text-center text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Lots</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Status</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($events as $event): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
          <td class="px-5 py-4">
            <div class="flex items-center gap-2">
              <?php if ($event['status'] === 'active'): ?>
              <span class="live-dot w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
              <?php endif; ?>
              <div>
                <p class="font-semibold text-slate-900 dark:text-white text-sm"><?= e($event['title']) ?></p>
                <?php if (!empty($event['venue'])): ?>
                <p class="text-xs text-slate-400 mt-0.5"><?= e($event['venue']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td class="px-4 py-4">
            <?php if (!empty($event['starts_at'])): ?>
            <p class="text-xs font-medium text-slate-700 dark:text-slate-300"><?= e(date('j M Y', strtotime((string)$event['starts_at']))) ?></p>
            <?php if (!empty($event['ends_at'])): ?>
            <p class="text-xs text-slate-400">to <?= e(date('j M Y', strtotime((string)$event['ends_at']))) ?></p>
            <?php endif; ?>
            <?php else: ?>
            <span class="text-xs text-slate-400">No date set</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-4 text-center">
            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= (int)($event['item_count'] ?? 0) ?></span>
          </td>
          <td class="px-4 py-4"><?= $statusBadge($event['status']) ?></td>
          <td class="px-5 py-4">
            <div class="flex items-center justify-end gap-2 flex-wrap">
              <?php if ($event['status'] === 'draft'): ?>
              <form method="POST" action="<?= e($basePath) ?>/admin/auctions/<?= e($event['slug']) ?>/publish">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Publish</button>
              </form>
              <?php elseif ($event['status'] === 'published'): ?>
              <form method="POST" action="<?= e($basePath) ?>/admin/auctions/<?= e($event['slug']) ?>/open">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">Open Bidding</button>
              </form>
              <?php elseif ($event['status'] === 'active'): ?>
              <a href="<?= e($basePath) ?>/auctioneer" class="px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Auctioneer Panel</a>
              <form method="POST" action="<?= e($basePath) ?>/admin/auctions/<?= e($event['slug']) ?>/end">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">End Auction</button>
              </form>
              <?php elseif (in_array($event['status'], ['ended', 'closed'], true)): ?>
              <a href="<?= e($basePath) ?>/admin/payments" class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">View Results</a>
              <?php endif; ?>
              <button
                onclick="openEditAuction('<?= e(addslashes($event['slug'])) ?>','<?= e(addslashes($event['title'])) ?>','<?= e(addslashes($event['venue'] ?? '')) ?>','<?= e(addslashes($event['description'] ?? '')) ?>','<?= e(substr((string)($event['starts_at'] ?? ''), 0, 10)) ?>','<?= e(substr((string)($event['ends_at'] ?? ''), 0, 10)) ?>')"
                class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Edit</button>
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
      <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Create Auction Popover -->
<div id="create-event-popover" popover="manual" class="form-popover popover-xl rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between flex-shrink-0">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Create Auction</h3>
    <button type="button" onclick="document.getElementById('create-event-popover').hidePopover()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="px-6 py-5 overflow-y-auto">
    <form method="POST" action="<?= e($basePath) ?>/admin/auctions">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Auction Name <span class="text-red-500">*</span></label>
          <input type="text" name="title" required placeholder="e.g. WFCS Eid Fundraiser 2026"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Venue <span class="text-xs text-slate-400 font-normal">(optional)</span></label>
          <input type="text" name="venue" placeholder="e.g. Radisson Blu, Glasgow"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Starts At</label>
            <input type="datetime-local" name="starts_at"
              class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ends At</label>
            <input type="datetime-local" name="ends_at"
              class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description <span class="text-xs text-slate-400 font-normal">(optional)</span></label>
          <textarea name="description" rows="3" placeholder="Describe the auction, venue, cause…"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none text-sm"></textarea>
        </div>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700/40">
          <button type="button" onclick="document.getElementById('create-event-popover').hidePopover()" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
          <button type="submit" class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Create Auction</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Auction Popover -->
<div id="edit-event-popover" popover="manual" class="form-popover popover-xl rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
  <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between flex-shrink-0">
    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Edit Auction</h3>
    <button type="button" onclick="document.getElementById('edit-event-popover').hidePopover()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="px-6 py-5 overflow-y-auto">
    <form id="edit-event-form" method="POST" action="">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Auction Name</label>
          <input type="text" id="edit-event-name" name="title"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Venue</label>
          <input type="text" id="edit-event-venue" name="venue"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Starts At</label>
            <input type="datetime-local" id="edit-event-starts" name="starts_at"
              class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ends At</label>
            <input type="datetime-local" id="edit-event-ends" name="ends_at"
              class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
          <textarea id="edit-event-description" name="description" rows="3"
            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none text-sm"></textarea>
        </div>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700/40">
          <button type="button" onclick="document.getElementById('edit-event-popover').hidePopover()" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
          <button type="submit" class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
function openEditAuction(slug, name, venue, description, starts, ends) {
  document.getElementById('edit-event-form').action = window._basePath + '/admin/auctions/' + slug;
  document.getElementById('edit-event-name').value = name;
  document.getElementById('edit-event-venue').value = venue;
  document.getElementById('edit-event-description').value = description;
  document.getElementById('edit-event-starts').value = starts ? starts + 'T00:00' : '';
  document.getElementById('edit-event-ends').value = ends ? ends + 'T00:00' : '';
  document.getElementById('edit-event-popover').showPopover();
}
JS;
?>
<script>window._basePath = '<?= e($basePath) ?>';</script>
