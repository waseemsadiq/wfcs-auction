<?php
/**
 * Admin Live Events view.
 *
 * Variables:
 *   $liveEvent    — current live event (from settings) or null
 *   $allEvents    — all events for selection
 *   $user         — admin
 *   $basePath     — global
 *   $csrfToken    — global
 */
global $basePath, $csrfToken;
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.05s; }
  .delay-2  { animation-delay: 0.10s; }
  .delay-3  { animation-delay: 0.15s; }

  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(69,162,218,.55); }
    50%       { box-shadow: 0 0 0 5px rgba(69,162,218,0); }
  }
  .live-dot { animation: livePulse 1.8s ease-in-out infinite; }

  .settings-card {
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
  }
  .dark .settings-card {
    background: #1e293b;
    border-color: rgba(71,85,105,0.4);
  }
</style>

<!-- Page heading -->
<div class="fade-up max-w-4xl mx-auto">
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Live Event Settings</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Configure the active live event for the auctioneer panel.</p>
</div>

<div class="max-w-4xl mx-auto mt-6 space-y-6">

  <!-- Current live event status -->
  <?php if ($liveEvent): ?>
  <div class="fade-up delay-1 bg-slate-900 dark:bg-slate-700/60 rounded-xl border border-slate-700 dark:border-slate-600/50 px-6 py-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
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
      <p class="text-xs text-slate-400"><?= e($liveEvent['venue'] ?? 'No venue set') ?></p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
      <a href="<?= e($basePath) ?>/projector" target="_blank" class="px-4 py-2 text-xs font-semibold text-slate-200 border border-slate-600 hover:border-slate-400 hover:text-white rounded-lg transition-colors">Projector</a>
      <a href="<?= e($basePath) ?>/auctioneer" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Auctioneer Panel</a>
      <form method="POST" action="<?= e($basePath) ?>/admin/live-events/stop">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <button type="submit" class="cursor-pointer px-4 py-2 text-xs font-semibold text-white border border-red-500/50 bg-red-500/20 hover:bg-red-500/30 rounded-lg transition-colors">End Live</button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-6 py-5 flex items-center gap-4">
    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No live event active</p>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Select an event below to go live.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Go Live card -->
  <div class="fade-up delay-2 settings-card">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      </div>
      <h2 class="text-base font-semibold text-slate-900 dark:text-white">Start Live Auction</h2>
    </div>
    <form method="POST" action="<?= e($basePath) ?>/admin/live-events/start" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <div>
        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Select Auction Event</label>
        <select name="event_id" required
          class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
          <option value="">Choose event…</option>
          <?php foreach ($allEvents as $ev): ?>
          <?php if (in_array($ev['status'], ['published', 'active'], true)): ?>
          <option value="<?= (int)$ev['id'] ?>" <?= isset($liveEvent) && (int)($liveEvent['id'] ?? 0) === (int)$ev['id'] ? 'selected' : '' ?>>
            <?= e($ev['title']) ?> (<?= e(ucfirst($ev['status'])) ?>)
          </option>
          <?php endif; ?>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Only published and active events are shown.</p>
      </div>
      <div>
        <button type="submit" class="flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Go Live
        </button>
      </div>
    </form>
  </div>

  <!-- Active events list -->
  <div class="fade-up delay-3 settings-card">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-slate-600 dark:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <h2 class="text-base font-semibold text-slate-900 dark:text-white">Available Events</h2>
    </div>
    <?php $publishedEvents = array_filter($allEvents, fn($e) => in_array($e['status'], ['published', 'active'], true)); ?>
    <?php if (empty($publishedEvents)): ?>
    <p class="text-sm text-slate-500 dark:text-slate-400">No published or active events available.</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
      <a href="<?= e($basePath) ?>/admin/auctions" class="text-primary hover:underline">Create an auction</a> and publish it first.
    </p>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($publishedEvents as $ev): ?>
      <div class="flex items-center justify-between gap-3 p-3 bg-slate-50 dark:bg-slate-700/40 rounded-lg">
        <div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($ev['title']) ?></p>
          <?php if (!empty($ev['venue'])): ?>
          <p class="text-xs text-slate-400 mt-0.5"><?= e($ev['venue']) ?></p>
          <?php endif; ?>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $ev['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' ?>">
          <?= e(ucfirst($ev['status'])) ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>
