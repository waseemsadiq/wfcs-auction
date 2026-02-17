<?php
/**
 * Auctioneer Control Panel
 *
 * Variables (from AuctioneerController::panel):
 *   $user           — authenticated admin user
 *   $liveEvent      — current live event array
 *   $items          — all items for the event (for queue)
 *   $liveItem       — current live item array or null
 *   $liveItemStatus — 'pending'|'open'|'sold'|'passed'
 *   $recentBids     — recent bids on current item
 *   $basePath       — global
 *   $csrfToken      — global
 */
global $basePath, $csrfToken;

$currentBid = $liveItem !== null ? (float)($liveItem['current_bid'] ?? 0.0) : 0.0;
// $bidCount is passed from the controller (accurate total, not just the fetched 10)
?>
<style>
  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.6); }
    50%       { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
  }
  .live-dot { animation: livePulse 1.6s ease-in-out infinite; }

  @keyframes bidIn {
    from { opacity: 0; transform: translateX(12px); }
    to   { opacity: 1; transform: translateX(0); }
  }
  .bid-row { animation: bidIn 0.3s cubic-bezier(0.16,1,0.3,1) both; }

  @keyframes counterPulse {
    0%   { transform: scale(1); }
    30%  { transform: scale(1.04); color: #45a2da; }
    100% { transform: scale(1); }
  }
  .bid-amount-anim { animation: counterPulse 0.6s cubic-bezier(0.16,1,0.3,1); }

  .state-btn { transition: background-color 0.15s, transform 0.1s; }
  .state-btn:active { transform: scale(0.97); }
  .queue-item { transition: background-color 0.15s; }
  .queue-item.current { border-left: 3px solid #45a2da; }
</style>

<!-- ══════════════════════════════════════ TOP BAR ══ -->
<div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-5 h-12 flex items-center justify-between flex-shrink-0">
  <div class="flex items-center gap-3">
    <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-6 w-auto dark:hidden" />
    <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-6 w-auto hidden dark:block opacity-80" />
    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden sm:block">Auctioneer Panel</span>
    <span class="text-xs text-slate-300 dark:text-slate-600">&mdash;</span>
    <span class="text-xs font-semibold text-slate-500 dark:text-slate-300 hidden sm:block"><?= e($liveEvent['title'] ?? '') ?></span>
  </div>
  <div class="flex items-center gap-3">
    <span id="liveClock" class="text-sm font-mono font-bold text-slate-500 dark:text-slate-300 tabular-nums"></span>
    <div class="flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800/50 rounded-full">
      <span class="live-dot w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
      <span class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest">Live</span>
    </div>
    <button onclick="toggleDarkMode(event)" class="p-1.5 rounded-lg text-slate-400 hover:text-primary transition-colors" aria-label="Toggle dark mode">
      <svg id="iconMoon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="iconSun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
    <button onclick="executeResume()" id="btnResume" class="<?= $biddingPaused ? '' : 'hidden' ?> flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition-colors">
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      <span class="hidden sm:inline">Resume Bidding</span>
      <span class="sm:hidden">Resume</span>
    </button>
    <button popovertarget="pausePopover" id="btnPause" class="<?= $biddingPaused ? 'hidden' : '' ?> flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors">
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
      <span class="hidden sm:inline">Pause All Bidding</span>
      <span class="sm:hidden">Pause</span>
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════ PAUSED BANNER ══ -->
<div id="pausedBanner" class="<?= $biddingPaused ? '' : 'hidden' ?> bg-red-600 text-white text-center text-xs font-bold py-2 px-4 uppercase tracking-widest flex items-center justify-center gap-2">
  <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
  Bidding is paused — all incoming bids are blocked
  <button onclick="executeResume()" class="ml-3 px-2.5 py-0.5 bg-white/20 hover:bg-white/30 rounded text-white text-xs font-bold transition-colors">Resume Now</button>
</div>

<!-- ══════════════════════════════════════ MAIN GRID ══ -->
<div class="flex-1 grid grid-cols-1 lg:grid-cols-[1fr_300px] xl:grid-cols-[1fr_340px] min-h-0 overflow-hidden">

  <!-- LEFT: Current item + queue -->
  <div class="flex flex-col min-h-0 overflow-hidden">
    <div class="flex-1 flex flex-col px-6 py-5">

      <?php if ($liveItem !== null): ?>
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs font-bold text-primary uppercase tracking-widest">
          Current Lot &mdash; #<?= (int)($liveItem['lot_number'] ?? 0) ?> of <?= count($items) ?>
        </span>
        <span id="bidCountBadge" class="text-xs font-semibold text-slate-500 bg-slate-200 dark:bg-slate-800 px-2.5 py-1 rounded-full"><?= $bidCount ?> bids</span>
      </div>

      <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white leading-tight mb-2"><?= e($liveItem['title']) ?></h2>
      <p class="text-sm text-slate-500 mb-6">
        <?= e($liveItem['category_name'] ?? '') ?>
        <?php if (!empty($liveItem['market_value'])): ?>
        &middot; Market value: £<?= number_format((float)$liveItem['market_value'], 0) ?>
        <?php endif; ?>
        <?php if (!empty($liveItem['starting_bid'])): ?>
        &middot; Reserve: £<?= number_format((float)$liveItem['starting_bid'], 0) ?>
        <?php endif; ?>
      </p>

      <!-- Current bid -->
      <div class="bg-slate-100 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-5">
        <div class="flex items-end justify-between gap-4 flex-wrap">
          <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Current Highest Bid</p>
            <p class="text-6xl sm:text-7xl font-black text-primary leading-none tabular-nums" id="currentBid">
              £<?= number_format($currentBid, 0) ?>
            </p>
          </div>
          <?php if (!empty($recentBids)): ?>
          <div class="text-right">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Current Leader</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-slate-200 font-mono"><?= e($recentBids[0]['bidder'] ?? '') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= e($recentBids[0]['time'] ?? '') ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- State buttons -->
      <div class="mb-5">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Advance Auction State</p>
        <div class="grid grid-cols-3 gap-3">
          <button id="btnOnce" onclick="advanceState('once')" class="state-btn flex flex-col items-center gap-1.5 px-4 py-4 rounded-xl bg-amber-500/10 border border-amber-500/30 hover:bg-amber-500/20 text-amber-400 font-bold text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Going Once
          </button>
          <button id="btnTwice" onclick="advanceState('twice')" class="state-btn flex flex-col items-center gap-1.5 px-4 py-4 rounded-xl bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 text-orange-400 font-bold text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Going Twice
          </button>
          <button id="btnSold" onclick="advanceState('sold')" class="state-btn flex flex-col items-center gap-1.5 px-4 py-4 rounded-xl bg-green-500/10 border border-green-500/30 hover:bg-green-500/20 text-green-400 font-bold text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            SOLD
          </button>
        </div>
        <div id="stateIndicator" class="hidden mt-3 flex items-center justify-center gap-2 py-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20">
          <span class="live-dot w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
          <span id="stateLabel" class="text-sm font-bold text-amber-400 uppercase tracking-widest">Going Once…</span>
        </div>
      </div>

      <!-- Actions row -->
      <div class="grid grid-cols-2 gap-3 mt-auto">
        <button onclick="closeLot('passed')" class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-bold rounded-xl transition-colors">
          <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Pass / No Sale
        </button>
        <button onclick="closeLot('sold')" class="flex items-center justify-center gap-2 px-4 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Mark Sold &amp; Next
        </button>
      </div>
      <?php else: ?>
      <div class="flex-1 flex flex-col items-center justify-center text-center px-8">
        <div class="w-16 h-16 rounded-2xl bg-slate-200 dark:bg-slate-800 flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Item Selected</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Select an item from the queue below to begin auctioning.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Item Queue -->
    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 px-4 py-3 overflow-x-auto flex-shrink-0">
      <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2.5">Item Queue</p>
      <div class="flex gap-2 min-w-max">
        <?php foreach ($items as $item): ?>
        <?php
            $isLive   = $liveItem !== null && (int)$item['id'] === (int)($liveItem['id'] ?? 0);
            $isSold   = in_array($item['status'], ['sold', 'awaiting_payment'], true);
            $isPassed = $item['status'] === 'ended';
        ?>
        <?php if ($isLive): ?>
        <div class="queue-item current flex items-center gap-2 px-3 py-2 rounded-lg bg-primary/10 border border-primary/30 pl-3">
          <span class="live-dot w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
          <span class="text-xs text-slate-900 dark:text-white font-semibold whitespace-nowrap"><?= e($item['title']) ?></span>
          <?php if ((float)($item['current_bid'] ?? 0) > 0): ?>
          <span class="text-xs font-bold text-primary">£<?= number_format((float)$item['current_bid'], 0) ?></span>
          <?php endif; ?>
        </div>
        <?php elseif ($isSold): ?>
        <div class="queue-item flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 opacity-50">
          <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span class="text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap"><?= e($item['title']) ?></span>
          <?php if ((float)($item['current_bid'] ?? 0) > 0): ?>
          <span class="text-xs font-bold text-green-600 dark:text-green-500">£<?= number_format((float)$item['current_bid'], 0) ?></span>
          <?php endif; ?>
        </div>
        <?php elseif ($isPassed): ?>
        <div class="queue-item flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 opacity-50">
          <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          <span class="text-xs text-slate-500 whitespace-nowrap"><?= e($item['title']) ?></span>
          <span class="text-xs text-slate-400">Passed</span>
        </div>
        <?php else: ?>
        <button type="button" onclick="selectItem(<?= (int)$item['id'] ?>)" class="queue-item flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 cursor-pointer hover:border-slate-400 dark:hover:border-slate-600 transition-colors">
          <svg class="w-3.5 h-3.5 text-slate-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span class="text-xs text-slate-700 dark:text-slate-300 whitespace-nowrap"><?= e($item['title']) ?></span>
          <?php if ((float)($item['starting_bid'] ?? 0) > 0): ?>
          <span class="text-xs text-slate-500">£<?= number_format((float)$item['starting_bid'], 0) ?></span>
          <?php endif; ?>
        </button>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: Bid Feed sidebar -->
  <div class="hidden lg:flex flex-col border-l border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 min-h-0 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Live Bid Feed</h3>
        <p class="text-xs text-slate-500 mt-0.5">Most recent bids</p>
      </div>
      <div class="flex items-center gap-1.5">
        <span class="live-dot w-1.5 h-1.5 rounded-full bg-primary"></span>
        <span class="text-xs font-bold text-primary uppercase tracking-widest">Live</span>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2" id="bidFeed">
      <?php foreach ($recentBids as $i => $bid): ?>
      <div class="bid-row flex items-start gap-3 p-3 rounded-xl <?= $i === 0 ? 'bg-primary/10 border border-primary/20' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/50' ?>">
        <div class="w-7 h-7 rounded-full <?= $i === 0 ? 'bg-primary/20' : 'bg-slate-200 dark:bg-slate-700' ?> flex items-center justify-center flex-shrink-0 mt-0.5">
          <span class="text-xs font-bold <?= $i === 0 ? 'text-primary' : 'text-slate-600 dark:text-slate-300' ?>"><?= e(mb_strtoupper(mb_substr((string)($bid['bidder'] ?? '?'), 0, 1))) ?></span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <span class="font-mono text-sm font-semibold <?= $i === 0 ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' ?>"><?= e($bid['bidder'] ?? '') ?></span>
            <span class="font-bold <?= $i === 0 ? 'text-primary' : 'text-slate-500 dark:text-slate-300' ?> text-sm"><?= e($bid['amount_formatted'] ?? '') ?></span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5 truncate"><?= e($liveItem['title'] ?? '') ?></p>
          <p class="text-xs text-slate-400 dark:text-slate-600"><?= e($bid['time'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($recentBids)): ?>
      <p class="text-xs text-slate-400 dark:text-slate-600 text-center py-4">No bids yet</p>
      <?php endif; ?>
    </div>

    <!-- Sidebar footer -->
    <div class="border-t border-slate-200 dark:border-slate-800 px-5 py-4 flex-shrink-0 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Bids this lot</span>
        <span class="text-xs font-bold text-slate-900 dark:text-white" id="sidebarBidCount"><?= $bidCount ?></span>
      </div>
      <?php if ($liveItem !== null && !empty($liveItem['starting_bid'])): ?>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Opening bid</span>
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">£<?= number_format((float)$liveItem['starting_bid'], 0) ?></span>
      </div>
      <?php endif; ?>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Status</span>
        <span id="sidebarStatus" class="text-xs font-semibold <?= $liveItemStatus === 'open' ? 'text-green-600 dark:text-green-400' : 'text-slate-500' ?>">
          <?= e(ucfirst($liveItemStatus)) ?>
        </span>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════ PAUSE POPOVER ══ -->
<div id="pausePopover" popover class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 p-6 max-w-sm w-full mx-auto my-auto">
  <div class="flex items-center gap-3 mb-4">
    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-red-500 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
    </div>
    <div>
      <h3 class="text-base font-bold text-slate-900 dark:text-white">Pause all bidding?</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Bidders will see a paused message</p>
    </div>
  </div>
  <p class="text-sm text-slate-600 dark:text-slate-300 mb-6 leading-relaxed">
    This will immediately halt all incoming bids for all lots. Use this for announcements or technical issues. You can resume at any time.
  </p>
  <div class="flex gap-3">
    <button popovertarget="pausePopover" popovertargetaction="hide" class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">Cancel</button>
    <button onclick="executePause()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Pause Now</button>
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

// State advance (visual only)
var states = {
  once:  { label: 'Going Once\u2026',  cls: 'bg-amber-500/10 border-amber-500/20',   textCls: 'text-amber-400',  dotCls: 'bg-amber-400'  },
  twice: { label: 'Going Twice\u2026', cls: 'bg-orange-500/10 border-orange-500/20', textCls: 'text-orange-400', dotCls: 'bg-orange-400' },
  sold:  { label: 'SOLD!',             cls: 'bg-green-500/10 border-green-500/20',   textCls: 'text-green-400',  dotCls: 'bg-green-400'  }
};
function advanceState(state) {
  var indicator = document.getElementById('stateIndicator');
  var label     = document.getElementById('stateLabel');
  var s = states[state];
  indicator.className = 'mt-3 flex items-center justify-center gap-2 py-2.5 rounded-lg border ' + s.cls;
  label.className     = 'text-sm font-bold uppercase tracking-widest ' + s.textCls;
  label.textContent   = s.label;
  indicator.classList.remove('hidden');
  ['once', 'twice', 'sold'].forEach(function(k) {
    var btn = document.getElementById('btn' + k.charAt(0).toUpperCase() + k.slice(1));
    if (btn) {
      btn.classList.toggle('ring-2', k === state);
      btn.classList.toggle('ring-white/20', k === state);
    }
  });
}

var basePath   = <?= json_encode($basePath, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var csrfToken  = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

// Select item
function selectItem(itemId) {
  var body = '_csrf_token=' + encodeURIComponent(csrfToken) + '&item_id=' + encodeURIComponent(itemId);
  fetch(basePath + '/auctioneer/set-item?_csrf_token=' + encodeURIComponent(csrfToken), {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body:    body
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      location.reload();
    } else {
      showToast(data.error || 'Failed to select item', 'error');
    }
  })
  .catch(function() { showToast('Network error', 'error'); });
}

// Open bidding
function openBidding() {
  var body = '_csrf_token=' + encodeURIComponent(csrfToken);
  fetch(basePath + '/auctioneer/open?_csrf_token=' + encodeURIComponent(csrfToken), {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body:    body
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      showToast('Bidding is now open', 'success');
      var ss = document.getElementById('sidebarStatus');
      if (ss) { ss.textContent = 'Open'; ss.className = 'text-xs font-semibold text-green-600 dark:text-green-400'; }
    } else {
      showToast(data.error || 'Failed to open bidding', 'error');
    }
  })
  .catch(function() { showToast('Network error', 'error'); });
}

// Close lot
function closeLot(result) {
  var body = '_csrf_token=' + encodeURIComponent(csrfToken) + '&result=' + encodeURIComponent(result);
  fetch(basePath + '/auctioneer/close?_csrf_token=' + encodeURIComponent(csrfToken), {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body:    body
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      showToast(result === 'sold' ? 'Lot marked as sold' : 'Lot passed \u2014 no sale', result === 'sold' ? 'success' : 'info');
      setTimeout(function() { location.reload(); }, 800);
    } else {
      showToast(data.error || 'Failed to close lot', 'error');
    }
  })
  .catch(function() { showToast('Network error', 'error'); });
}

// Pause bidding
function executePause() {
  document.getElementById('pausePopover').hidePopover();
  fetch(basePath + '/auctioneer/pause?_csrf_token=' + encodeURIComponent(csrfToken), {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body:    '_csrf_token=' + encodeURIComponent(csrfToken)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      showPausedState(true);
      showToast('Bidding paused — all incoming bids are now blocked', 'error');
    } else {
      showToast('Failed to pause bidding', 'error');
    }
  })
  .catch(function() { showToast('Network error', 'error'); });
}

// Resume bidding
function executeResume() {
  fetch(basePath + '/auctioneer/resume?_csrf_token=' + encodeURIComponent(csrfToken), {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
    body:    '_csrf_token=' + encodeURIComponent(csrfToken)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.ok) {
      showPausedState(false);
      showToast('Bidding resumed', 'success');
    } else {
      showToast('Failed to resume bidding', 'error');
    }
  })
  .catch(function() { showToast('Network error', 'error'); });
}

// Toggle paused UI state without a page reload
function showPausedState(paused) {
  var banner  = document.getElementById('pausedBanner');
  var btnPause  = document.getElementById('btnPause');
  var btnResume = document.getElementById('btnResume');
  if (banner)    banner.classList.toggle('hidden', !paused);
  if (btnPause)  btnPause.classList.toggle('hidden', paused);
  if (btnResume) btnResume.classList.toggle('hidden', !paused);
}

// Live polling — update bid feed and current bid every 2 seconds
var lastBidCount = <?= (int)$bidCount ?>;
var lastBid      = <?= (float)$currentBid ?>;

function buildBidRow(bid, isTop) {
  var row    = document.createElement('div');
  var letter = (bid.bidder || '?').charAt(0).toUpperCase();

  row.className = 'bid-row flex items-start gap-3 p-3 rounded-xl ' +
    (isTop ? 'bg-primary/10 border border-primary/20' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/50');

  var avatar = document.createElement('div');
  avatar.className = 'w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 ' +
    (isTop ? 'bg-primary/20' : 'bg-slate-200 dark:bg-slate-700');

  var avatarSpan = document.createElement('span');
  avatarSpan.className = 'text-xs font-bold ' + (isTop ? 'text-primary' : 'text-slate-600 dark:text-slate-300');
  avatarSpan.textContent = letter;
  avatar.appendChild(avatarSpan);

  var body = document.createElement('div');
  body.className = 'flex-1 min-w-0';

  var top = document.createElement('div');
  top.className = 'flex items-center justify-between gap-2';

  var nameSpan = document.createElement('span');
  nameSpan.className = 'font-mono text-sm font-semibold ' +
    (isTop ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200');
  nameSpan.textContent = bid.bidder;

  var amountSpan = document.createElement('span');
  amountSpan.className = 'font-bold text-sm ' + (isTop ? 'text-primary' : 'text-slate-500 dark:text-slate-300');
  amountSpan.textContent = bid.amount_formatted;

  top.appendChild(nameSpan);
  top.appendChild(amountSpan);

  var timeP = document.createElement('p');
  timeP.className = 'text-xs text-slate-400 dark:text-slate-600';
  timeP.textContent = bid.time;

  body.appendChild(top);
  body.appendChild(timeP);

  row.appendChild(avatar);
  row.appendChild(body);
  return row;
}

function poll() {
  fetch(basePath + '/api/live-status')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.live || !data.item_id) { return; }

      if (data.current_bid !== lastBid) {
        lastBid = data.current_bid;
        var bidEl = document.getElementById('currentBid');
        if (bidEl) {
          bidEl.textContent = data.current_bid_formatted;
          bidEl.classList.remove('bid-amount-anim');
          void bidEl.offsetWidth;
          bidEl.classList.add('bid-amount-anim');
        }
      }

      if (data.bid_count !== lastBidCount) {
        lastBidCount = data.bid_count;
        var countEl = document.getElementById('bidCountBadge');
        if (countEl) countEl.textContent = data.bid_count + ' bids';
        var sidebarCount = document.getElementById('sidebarBidCount');
        if (sidebarCount) sidebarCount.textContent = data.bid_count;

        var feed = document.getElementById('bidFeed');
        if (feed && data.recent_bids && data.recent_bids.length > 0) {
          while (feed.firstChild) { feed.removeChild(feed.firstChild); }
          data.recent_bids.forEach(function(bid, i) {
            feed.appendChild(buildBidRow(bid, i === 0));
          });
        }
      }
    })
    .catch(function() { /* silent */ });
}

setInterval(poll, 2000);
</script>
