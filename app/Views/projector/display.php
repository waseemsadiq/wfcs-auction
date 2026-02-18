<?php
/**
 * Projector Display — Full-screen public view
 *
 * Variables (from AuctioneerController::projector):
 *   $liveEvent      — current live event array or null
 *   $liveItem       — current live item array or null
 *   $liveItemStatus — 'pending'|'open'|'sold'|'passed'
 *   $items          — all items in the event (queue)
 *   $basePath       — global
 */
global $basePath;

$currentBid     = $liveItem !== null ? (float)($liveItem['current_bid'] ?? 0.0) : 0.0;
$bidCount       = $liveItem !== null ? (int)($liveItem['bid_count'] ?? 0) : 0;
$lotNumber      = $liveItem !== null ? (int)($liveItem['lot_number'] ?? 0) : 0;
$totalLots      = count($items);
$eventTitle     = $liveEvent !== null ? (string)($liveEvent['title'] ?? '') : '';
$itemTitle      = $liveItem  !== null ? (string)($liveItem['title'] ?? '') : '';
$itemCategory   = $liveItem  !== null ? (string)($liveItem['category_name'] ?? '') : '';
$marketValue    = $liveItem  !== null ? (float)($liveItem['market_value'] ?? 0.0) : 0.0;
?>
<style>
  html, body { width: 100vw; height: 100vh; overflow: hidden; margin: 0; padding: 0; }

  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.7); }
    50%       { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
  }
  .live-dot { animation: livePulse 1.6s ease-in-out infinite; }

  @keyframes bidGlow {
    0%   { text-shadow: 0 0 0 rgba(0,0,0,0); }
    30%  { text-shadow: 0 0 40px rgba(69,162,218,0.35); }
    100% { text-shadow: 0 0 0 rgba(0,0,0,0); }
  }
  @keyframes bidGlowDark {
    0%   { text-shadow: 0 0 0 rgba(255,255,255,0); }
    30%  { text-shadow: 0 0 60px rgba(255,255,255,0.2), 0 0 20px rgba(69,162,218,0.4); }
    100% { text-shadow: 0 0 0 rgba(255,255,255,0); }
  }
  .bid-flash        { animation: bidGlow 1.4s ease-out forwards; }
  .dark .bid-flash  { animation: bidGlowDark 1.4s ease-out forwards; }

  @keyframes slideScan {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
  }
  .scan-line {
    animation: slideScan 3.5s linear infinite;
    background: linear-gradient(90deg, transparent, rgba(69,162,218,0.4), transparent);
    width: 33%;
  }

  @keyframes marqueeScroll {
    0%   { transform: translateX(100vw); }
    100% { transform: translateX(-100%); }
  }
  .marquee-inner { animation: marqueeScroll 32s linear infinite; white-space: nowrap; }

  @keyframes stateGlow {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.55; }
  }
  .state-pulse { animation: stateGlow 1.4s ease-in-out infinite; }

  .queue-item { transition: background-color 0.2s; }
  .queue-item.current { border-left: 3px solid #45a2da; }

  #bidAmount { font-size: clamp(5.5rem, 14vw, 10rem); }
</style>

<div class="bg-white dark:bg-slate-950 text-slate-900 dark:text-white font-sans flex flex-col transition-colors duration-300 w-full h-full">

<!-- ══════════════════════════════════════ TOP BAR ══ -->
<div class="flex items-center justify-between px-10 pt-8 pb-5 flex-shrink-0">
  <div class="flex items-center gap-5">
    <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-16 w-auto dark:hidden" />
    <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-16 w-auto hidden dark:block opacity-90" />
    <div class="h-10 w-px bg-slate-200 dark:bg-slate-700"></div>
    <div>
      <p class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Charity Auction</p>
      <p class="text-xl font-black text-slate-900 dark:text-white leading-tight"><?= e($eventTitle ?: 'WFCS Auction') ?></p>
    </div>
  </div>
  <div class="flex items-center gap-4">
    <span id="liveClock" class="text-3xl font-mono font-bold text-slate-400 tabular-nums"></span>
    <div class="flex items-center gap-2.5 px-5 py-2.5 bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 rounded-2xl">
      <span class="live-dot w-3 h-3 rounded-full bg-red-500 flex-shrink-0"></span>
      <span class="text-base font-black text-red-600 dark:text-red-400 uppercase tracking-widest">Live</span>
    </div>
    <button onclick="toggleDarkMode(event)" title="Toggle theme (T)" class="p-2.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-label="Toggle dark mode">
      <svg id="iconMoon" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="iconSun" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
    <button id="fsBtn" onclick="toggleFullscreen()" title="Toggle fullscreen (F)" class="p-2.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
      <svg id="fsExpand" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
      <svg id="fsCompress" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M21 8h-3a2 2 0 0 1-2-2V3"/><path d="M3 16h3a2 2 0 0 1 2 2v3"/><path d="M16 21v-3a2 2 0 0 1 2-2h3"/></svg>
    </button>
  </div>
</div>

<!-- Scan line divider -->
<div class="mx-10 h-px bg-slate-200 dark:bg-slate-800 relative overflow-hidden flex-shrink-0">
  <div class="scan-line absolute inset-y-0"></div>
</div>

<!-- ══════════════════════════════════════ MAIN STAGE ══ -->
<div class="flex-1 flex flex-col items-center justify-center px-10 py-6 min-h-0">

  <?php if ($liveItem !== null): ?>

  <!-- Lot indicator -->
  <p id="lotIndicator" class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] mb-4">
    Lot <?= $lotNumber ?> of <?= $totalLots ?> &nbsp;&mdash;&nbsp; Currently Bidding
  </p>

  <!-- Item name -->
  <h1 id="itemTitle" class="text-6xl font-black text-slate-900 dark:text-white text-center leading-tight mb-3 max-w-4xl">
    <?= e($itemTitle) ?>
  </h1>

  <!-- Category + value -->
  <p id="itemMeta" class="text-lg text-slate-400 dark:text-slate-500 mb-10">
    <?= e($itemCategory) ?>
    <?php if ($marketValue > 0): ?>
    &nbsp;&middot;&nbsp; Market value: £<?= number_format($marketValue, 0) ?>
    <?php endif; ?>
  </p>

  <!-- Bid amount (hero) -->
  <div class="flex flex-col items-center gap-2 mb-2">
    <p id="bidLabel" class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?= $bidCount > 0 ? 'Current Highest Bid' : 'Opening Bid' ?></p>
    <p id="bidAmount" class="bid-flash font-black text-slate-900 dark:text-white leading-none tabular-nums tracking-tight">
      £<?= number_format($currentBid, 0) ?>
    </p>
  </div>

  <!-- Bid count / first-bid prompt -->
  <div class="mt-4 mb-8 text-center">
    <p id="noBidsMsg" class="text-lg font-semibold text-slate-400 dark:text-slate-500 <?= $bidCount > 0 ? 'hidden' : '' ?>">Be the first to bid!</p>
    <div id="bidCountWrap" class="<?= $bidCount > 0 ? '' : 'hidden' ?>">
      <p class="text-xs text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-1">Total Bids</p>
      <p id="bidCount" class="text-2xl font-bold text-slate-600 dark:text-slate-300 font-mono"><?= $bidCount ?></p>
    </div>
  </div>

  <!-- Status banners -->
  <div id="statusOpen" class="<?= $liveItemStatus === 'open' ? 'flex' : 'hidden' ?> items-center gap-3 px-8 py-3.5 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800/50 rounded-2xl">
    <span class="live-dot w-3 h-3 rounded-full bg-green-500 flex-shrink-0"></span>
    <span class="text-lg font-bold text-green-600 dark:text-green-400 uppercase tracking-widest">Bidding Open</span>
  </div>

  <div id="statusPending" class="<?= $liveItemStatus === 'pending' ? 'flex' : 'hidden' ?> items-center gap-3 px-8 py-3.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
    <span class="w-3 h-3 rounded-full bg-slate-400 flex-shrink-0"></span>
    <span class="text-lg font-bold text-slate-500 uppercase tracking-widest">Coming Up Next</span>
  </div>

  <div id="statusSold" class="<?= $liveItemStatus === 'sold' ? 'flex' : 'hidden' ?> flex-col items-center gap-1">
    <div class="flex items-center gap-4 px-10 py-4 rounded-2xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30">
      <svg class="w-8 h-8 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <span class="text-3xl font-black text-green-600 dark:text-green-400 uppercase tracking-widest">Sold!</span>
    </div>
    <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Congratulations to the winner</p>
  </div>

  <div id="statusPassed" class="<?= $liveItemStatus === 'passed' ? 'flex' : 'hidden' ?> items-center gap-3 px-8 py-3.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
    <svg class="w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
    <span class="text-lg font-bold text-slate-500 uppercase tracking-widest">Lot Passed</span>
  </div>

  <?php else: ?>

  <!-- Waiting state -->
  <div class="text-center">
    <div class="w-24 h-24 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-6">
      <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-3">Auction Starting Soon</h1>
    <p class="text-lg text-slate-400 dark:text-slate-500"><?= e($eventTitle ?: 'WFCS Auction') ?></p>
  </div>

  <?php endif; ?>

</div>

<!-- Divider -->
<div class="mx-10 h-px bg-slate-200 dark:bg-slate-800 flex-shrink-0"></div>

<!-- ══════════════════════════════════════ LOT QUEUE + TOTAL ══ -->
<?php if (!empty($items)): ?>
<div class="flex-shrink-0 px-10 py-4">
  <div class="flex items-center gap-3 overflow-x-hidden">
    <p class="text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest flex-shrink-0 mr-1">Lots</p>

    <?php foreach ($items as $item): ?>
    <?php
        $isLive   = $liveItem !== null && (int)$item['id'] === (int)($liveItem['id'] ?? 0);
        $isSold   = in_array($item['status'], ['sold', 'awaiting_payment'], true);
        $isPassed = $item['status'] === 'ended';
    ?>
    <?php if ($isLive): ?>
    <div class="queue-item current flex items-center gap-2.5 px-4 py-2 rounded-lg bg-primary/10 border border-primary/30 flex-shrink-0 pl-4">
      <span class="live-dot w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
      <span class="text-sm text-slate-900 dark:text-white font-semibold"><?= e($item['title']) ?></span>
      <?php if ((float)($item['current_bid'] ?? 0) > 0): ?>
      <span class="text-sm font-bold text-primary">£<?= number_format((float)$item['current_bid'], 0) ?></span>
      <?php endif; ?>
    </div>
    <?php elseif ($isSold): ?>
    <div class="queue-item flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 opacity-40 flex-shrink-0">
      <svg class="w-3.5 h-3.5 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($item['title']) ?></span>
      <?php if ((float)($item['current_bid'] ?? 0) > 0): ?>
      <span class="text-sm font-bold text-green-600 dark:text-green-500">£<?= number_format((float)$item['current_bid'], 0) ?></span>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="queue-item flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex-shrink-0">
      <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span class="text-sm text-slate-500 dark:text-slate-400"><?= e($item['title']) ?></span>
      <?php if ((float)($item['starting_bid'] ?? 0) > 0): ?>
      <span class="text-sm text-slate-400 dark:text-slate-500">£<?= number_format((float)$item['starting_bid'], 0) ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Total raised -->
    <?php
    $totalRaised = 0.0;
    foreach ($items as $item) {
        if (in_array($item['status'], ['sold', 'awaiting_payment'], true)) {
            $totalRaised += (float)($item['current_bid'] ?? 0);
        }
    }
    if ($totalRaised > 0):
    ?>
    <div class="ml-auto flex-shrink-0 pl-6 border-l border-slate-200 dark:border-slate-800 flex flex-col items-end">
      <p class="text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest">Total Raised</p>
      <p id="totalRaised" class="text-xl font-black text-primary tabular-nums">£<?= number_format($totalRaised, 0) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════ TICKER ══ -->
<div class="bg-slate-100 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 h-9 flex items-center overflow-hidden flex-shrink-0 relative">
  <div class="marquee-inner text-xs font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-widest">
    <?= e($eventTitle ?: 'WFCS Auction') ?> &nbsp;&bull;&nbsp; All proceeds to The Well Foundation (Charity SC040105) &nbsp;&bull;&nbsp; Bid via the WFCS Auction app &nbsp;&bull;&nbsp; Auction app built with
    <svg class="inline-block w-3 h-3 align-middle text-rose-400 mx-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    by Waseem &nbsp;&bull;&nbsp;
  </div>
</div>

</div>

<script>
// Live clock
function updateClock() {
  var now = new Date();
  var h = String(now.getHours()).padStart(2, '0');
  var m = String(now.getMinutes()).padStart(2, '0');
  var s = String(now.getSeconds()).padStart(2, '0');
  var el = document.getElementById('liveClock');
  if (el) el.textContent = h + ':' + m + ':' + s;
}
updateClock();
setInterval(updateClock, 1000);

// Fullscreen
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    document.exitFullscreen();
  }
}
document.addEventListener('fullscreenchange', function() {
  var isFs = !!document.fullscreenElement;
  var expand   = document.getElementById('fsExpand');
  var compress = document.getElementById('fsCompress');
  if (expand)   expand.classList.toggle('hidden', isFs);
  if (compress) compress.classList.toggle('hidden', !isFs);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  if (e.key === 'f' || e.key === 'F') toggleFullscreen();
  if (e.key === 't' || e.key === 'T') toggleDarkMode(null);
});

var basePath        = <?= json_encode($basePath, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var lastBid         = <?= json_encode($currentBid, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var lastItemId      = <?= json_encode($liveItem !== null ? (int)$liveItem['id'] : null, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var lastLiveStatus  = <?= json_encode($liveItemStatus, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

// Show the correct status banner
function showStatus(status) {
  var ids = ['statusOpen', 'statusPending', 'statusSold', 'statusPassed'];
  var map  = { open: 'statusOpen', pending: 'statusPending', sold: 'statusSold', passed: 'statusPassed' };
  ids.forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) { return; }
    el.classList.add('hidden');
    el.classList.remove('flex');
  });
  var target = map[status];
  if (target) {
    var el = document.getElementById(target);
    if (el) {
      el.classList.remove('hidden');
      el.classList.add('flex');
    }
  }
}

// Update bid count display — shows "Be the first to bid!" when count is 0
function updateBidDisplay(count) {
  var noBids   = document.getElementById('noBidsMsg');
  var wrap     = document.getElementById('bidCountWrap');
  var countEl  = document.getElementById('bidCount');
  var labelEl  = document.getElementById('bidLabel');
  var hasBids  = count > 0;
  if (noBids)  noBids.classList.toggle('hidden', hasBids);
  if (wrap)    wrap.classList.toggle('hidden', !hasBids);
  if (countEl) countEl.textContent = count;
  if (labelEl) labelEl.textContent = hasBids ? 'Current Highest Bid' : 'Opening Bid';
}

// Polling
function poll() {
  fetch(basePath + '/api/live-status')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.live) { return; }

      // Live status changed
      if (data.live_status !== lastLiveStatus) {
        lastLiveStatus = data.live_status;
        showStatus(data.live_status);
      }

      if (!data.item_id) { return; }

      // Item changed — reload to refresh all content
      if (data.item_id !== lastItemId) {
        location.reload();
        return;
      }

      // Bid amount changed
      if (data.current_bid !== lastBid) {
        lastBid = data.current_bid;
        var bidEl = document.getElementById('bidAmount');
        if (bidEl) {
          bidEl.textContent = data.current_bid_formatted;
          bidEl.classList.remove('bid-flash');
          void bidEl.offsetWidth;
          bidEl.classList.add('bid-flash');
        }
      }

      // Bid count + label
      if (data.bid_count !== undefined) {
        updateBidDisplay(data.bid_count);
      }
    })
    .catch(function() { /* silent */ });
}

setInterval(poll, 2000);
</script>
