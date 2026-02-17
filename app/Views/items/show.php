<?php
/**
 * Item detail page — image, title, bid panel, bid history, countdown
 *
 * Variables from controller:
 *   $basePath (global), $csrfToken (global)
 *   $user     — authenticated user or null
 *   $item     — item row with joined fields (category_name, event_title, event_slug, donor_name)
 *   $bids     — array of recent bids (empty until Phase 7)
 */
global $basePath, $csrfToken;

$currentBid  = (float)($item['current_bid'] ?? $item['starting_bid'] ?? 0);
$bidCount    = (int)($item['bid_count'] ?? 0);
$minBid      = $currentBid + (float)($item['min_increment'] ?? 1);
$isActive    = ($item['status'] ?? '') === 'active';
$isEnded     = in_array($item['status'] ?? '', ['ended', 'sold'], true);
$hasBuyNow   = !empty($item['buy_now_price']) && $item['buy_now_price'] > 0;

$statusLabel = match($item['status'] ?? '') {
    'active' => 'Live',
    'ended'  => 'Ended',
    'sold'   => 'Sold',
    default  => ucfirst($item['status'] ?? ''),
};
$statusClasses = match($item['status'] ?? '') {
    'active'  => 'bg-green-500/90 text-white',
    'ended'   => 'bg-slate-500/90 text-white',
    'sold'    => 'bg-primary/90 text-white',
    default   => 'bg-slate-500/90 text-white',
};
?>

<style>
  /* ─── Fade-up ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0);   }
  }
  .fade-up  { animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.08s; }
  .delay-2  { animation-delay: 0.16s; }
  .delay-3  { animation-delay: 0.24s; }
  .delay-4  { animation-delay: 0.32s; }

  /* ─── Hero image gradient overlay ─── */
  .hero-image-overlay::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,.65) 0%, rgba(0,0,0,.25) 40%, rgba(0,0,0,.05) 100%);
    pointer-events: none;
  }

  /* ─── Bid panel sticky ─── */
  @media (min-width: 1024px) {
    .bid-panel-sticky { position: sticky; top: 5.5rem; }
  }

  /* ─── Countdown tick ─── */
  @keyframes tickPulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .55; }
  }
  .tick { animation: tickPulse 1s ease-in-out infinite; }

  /* ─── Bid history row ─── */
  .bid-row { transition: background-color 0.15s ease; }

  /* ─── Mobile bid bar ─── */
  .mobile-bid-bar {
    position: fixed;
    bottom: 1.25rem;
    left: 1rem;
    right: 1rem;
    z-index: 50;
  }
</style>

<!-- Hero image (full-bleed, breaks out of layout max-w) -->
<div class="-mx-6 -mt-10 mb-8 relative hero-image-overlay">
  <div class="w-full h-[42vh] min-h-64 max-h-96 overflow-hidden bg-slate-900 lg:h-[48vh] lg:max-h-[520px]">
    <?php if (!empty($item['image'])): ?>
    <img
      src="<?= e($basePath) ?>/uploads/<?= e($item['image']) ?>"
      alt="<?= e($item['title'] ?? '') ?>"
      class="w-full h-full object-cover object-center"
    />
    <?php else: ?>
    <div class="w-full h-full flex items-center justify-center text-slate-600">
      <svg class="w-24 h-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
    </div>
    <?php endif; ?>
  </div>
  <!-- Overlaid badges -->
  <?php if (!empty($item['category_name'])): ?>
  <span class="absolute top-4 left-4 text-xs font-semibold px-2.5 py-1 rounded-full bg-primary text-white backdrop-blur-sm z-10"><?= e($item['category_name']) ?></span>
  <?php endif; ?>
  <span class="absolute top-4 right-4 text-xs font-semibold px-2.5 py-1 rounded-full backdrop-blur-sm z-10 <?= $statusClasses ?>"><?= e($statusLabel) ?></span>
</div>

<!-- Breadcrumb -->
<?php
$breadcrumbItems = [['label' => 'Auctions', 'url' => $basePath . '/auctions']];
if (!empty($item['event_slug'])) {
    $breadcrumbItems[] = ['label' => $item['event_title'] ?? 'Event', 'url' => $basePath . '/auctions/' . $item['event_slug']];
}
$breadcrumbItems[] = ['label' => $item['title'] ?? ''];
echo atom('breadcrumb', ['items' => $breadcrumbItems]);
?>

<!-- Two-column grid -->
<div class="lg:grid lg:grid-cols-[1fr_380px] lg:gap-10 xl:gap-14 pb-32 lg:pb-10">

  <!-- ── LEFT COLUMN ── -->
  <div class="space-y-8">

    <!-- Title block -->
    <div class="fade-up delay-1">
      <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white leading-tight tracking-tight mb-3">
        <?= e($item['title'] ?? '') ?>
      </h1>
      <div class="flex items-center flex-wrap gap-3 text-sm text-slate-500 dark:text-slate-400">
        <?php if (!empty($item['lot_number'])): ?>
        <span class="font-semibold text-slate-700 dark:text-slate-300">Lot #<?= (int)$item['lot_number'] ?></span>
        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
        <?php endif; ?>
        <?php if (!empty($item['donor_name'])): ?>
        <span class="flex items-center gap-1.5">
          <svg class="w-4 h-4 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          Donated by <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($item['donor_name']) ?></span>
        </span>
        <?php endif; ?>
        <?php if (!empty($item['event_title']) && !empty($item['event_slug'])): ?>
        <a href="<?= e($basePath) ?>/auctions/<?= e($item['event_slug']) ?>" class="flex items-center gap-1.5 text-primary hover:underline">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?= e($item['event_title']) ?>
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($item['description'])): ?>
    <div class="fade-up delay-2 space-y-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
      <?php foreach (explode("\n\n", $item['description']) as $para): ?>
      <p><?= nl2br(e(trim($para))) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- What you're bidding on -->
    <?php if (!empty($item['what_included'])): ?>
    <div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm p-6">
      <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        What you're bidding on
      </h2>
      <ul class="space-y-2.5">
        <?php foreach (array_filter(array_map('trim', explode("\n", $item['what_included']))) as $line): ?>
        <li class="flex items-start gap-2.5 text-sm text-slate-600 dark:text-slate-400">
          <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span><?= e($line) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Gift Aid box -->
    <?php if (!empty($item['market_value']) && (float)$item['market_value'] > 0):
        $marketValue      = (float)$item['market_value'];
        $giftAidPreview   = $currentBid > $marketValue
            ? round(($currentBid - $marketValue) * 0.25, 2)
            : 0.0;
    ?>
    <div class="fade-up delay-3 flex gap-4 p-5 bg-primary/5 dark:bg-primary/10 border border-primary/20 rounded-xl">
      <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-primary/15 flex items-center justify-center">
        <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </div>
      <div>
        <p class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Gift Aid eligible lot</p>
        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
          The market value of this lot has been assessed at <strong class="text-slate-800 dark:text-slate-200"><?= formatCurrency($marketValue) ?></strong>.
          If your winning bid exceeds this amount, the difference is treated as a charitable donation and The Well Foundation can reclaim 25p of tax on every £1.
        </p>
        <?php if ($giftAidPreview > 0): ?>
        <p class="text-xs font-semibold text-primary mt-2">
          If you win at the current bid, WFCS can claim <?= formatCurrency($giftAidPreview) ?> in Gift Aid.
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Bid history -->
    <div class="fade-up delay-3">
      <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">Bid History</h2>
      <?php if (!empty($bids)): ?>
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700/50">
              <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Bidder</th>
              <th class="text-right px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Amount</th>
              <th class="text-right px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 hidden sm:table-cell">Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bids as $bidIdx => $bid): ?>
            <tr class="bid-row border-b border-slate-50 dark:border-slate-700/30 hover:bg-slate-50 dark:hover:bg-slate-700/30 last:border-b-0">
              <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 <?= $bidIdx === 0 ? 'font-medium' : '' ?> flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full <?= $bidIdx === 0 ? 'bg-primary/10 text-primary' : 'bg-slate-100 dark:bg-slate-700 text-slate-500' ?> flex items-center justify-center text-xs font-bold flex-shrink-0">
                  <?= e(strtoupper(substr($bid['bidder_initial'] ?? 'B', 0, 1))) ?>
                </span>
                <?= e($bid['bidder_masked'] ?? 'Bidder') ?>
                <?php if ($bidIdx === 0): ?>
                <span class="text-xs font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-1.5 py-0.5 rounded-md">Leading</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3.5 text-right font-bold text-slate-900 dark:text-white"><?= formatCurrency((float)($bid['amount'] ?? 0)) ?></td>
              <td class="px-5 py-3.5 text-right text-slate-400 dark:text-slate-500 hidden sm:table-cell"><?= e($bid['time_ago'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm p-8 text-center">
        <p class="text-sm text-slate-400 dark:text-slate-500">No bids yet. Be the first to bid on this lot!</p>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /left column -->

  <!-- ── RIGHT COLUMN — Bid Panel (desktop) ── -->
  <div class="hidden lg:block mt-0">
    <div class="bid-panel-sticky">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/30 shadow-lg overflow-hidden">

        <!-- Current bid header -->
        <div class="px-6 pt-6 pb-5 border-b border-slate-100 dark:border-slate-700/50">
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">
            <?= $bidCount > 0 ? 'Current bid' : 'Starting bid' ?>
          </p>
          <p id="current-bid" class="text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-2"><?= formatCurrency($currentBid) ?></p>
          <div class="flex items-center gap-3 flex-wrap">
            <span id="bid-count" class="text-sm text-slate-500 dark:text-slate-400"><?= $bidCount ?> bid<?= $bidCount !== 1 ? 's' : '' ?></span>
          </div>
        </div>

        <!-- Countdown (active items only) -->
        <?php if ($isActive && !empty($item['ends_at'])): ?>
        <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800/30">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500 tick flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Ending in</p>
          </div>
          <p id="panel-countdown" data-ends="<?= e($item['ends_at']) ?>" class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1 tracking-tight">--</p>
        </div>
        <?php elseif ($isEnded): ?>
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-100 dark:border-slate-700/50">
          <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">This lot has ended</p>
        </div>
        <?php endif; ?>

        <!-- Bid form / sign-in prompt -->
        <div class="px-6 py-5 space-y-4">

          <?php if ($isActive): ?>
            <?php if ($user): ?>
            <!-- Authenticated bid form -->
            <form method="POST" action="<?= e($basePath) ?>/bids">
              <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
              <input type="hidden" name="item_slug" value="<?= e($item['slug'] ?? '') ?>" />

              <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Your bid</label>
              <div class="flex gap-2 mb-1.5">
                <div class="relative flex-1">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
                  <input
                    type="number"
                    name="amount"
                    id="bid-amount"
                    value="<?= number_format($minBid, 2, '.', '') ?>"
                    min="<?= number_format($minBid, 2, '.', '') ?>"
                    step="0.01"
                    class="w-full pl-8 pr-3 py-3 text-base font-bold bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors text-slate-900 dark:text-white"
                  />
                </div>
              </div>
              <p class="text-xs text-slate-400 mb-3">Minimum bid: <?= formatCurrency($minBid) ?></p>

              <button type="submit" class="w-full py-4 rounded-xl font-bold text-base text-white bg-primary hover:bg-primary-hover transition-colors shadow-sm">
                Place Bid
              </button>

              <!-- Gift Aid checkbox -->
              <div class="flex items-start gap-3 mt-3">
                <input type="checkbox" id="gift-aid" name="gift_aid" value="1" class="toggle-input sr-only" />
                <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full flex-shrink-0 mt-0.5 cursor-pointer">
                  <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></span>
                </div>
                <label for="gift-aid" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed cursor-pointer">
                  Add a Gift Aid declaration to this bid. I am a UK taxpayer and confirm this bid includes a Gift Aid donation.
                </label>
              </div>
            </form>

            <?php if ($hasBuyNow): ?>
            <div class="border-t border-slate-100 dark:border-slate-700/50 pt-4">
              <form method="POST" action="<?= e($basePath) ?>/bids/buy-now">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
                <input type="hidden" name="item_slug" value="<?= e($item['slug'] ?? '') ?>" />
                <button type="submit" class="w-full py-3 rounded-xl font-semibold text-sm text-primary border-2 border-primary hover:bg-primary hover:text-white transition-colors">
                  Buy Now — <?= formatCurrency((float)$item['buy_now_price']) ?>
                </button>
              </form>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Sign-in prompt for unauthenticated users -->
            <div class="text-center p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700">
              <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-0.5">Sign in to place a bid</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">You need an account to bid on this lot.</p>
              <div class="flex gap-2">
                <a href="<?= e($basePath) ?>/login" class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-center">Sign in</a>
                <a href="<?= e($basePath) ?>/register" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-sm transition-colors text-center">Register</a>
              </div>
            </div>

            <!-- Greyed-out bid input -->
            <div class="opacity-40 pointer-events-none">
              <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Your bid</label>
              <div class="flex gap-2">
                <div class="relative flex-1">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
                  <input type="number" value="<?= number_format($minBid, 2, '.', '') ?>" class="w-full pl-8 pr-3 py-3 text-base font-bold bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none" readonly />
                </div>
              </div>
              <p class="text-xs text-slate-400 mt-1.5">Minimum bid: <?= formatCurrency($minBid) ?></p>
            </div>
            <button disabled class="w-full py-4 rounded-xl font-bold text-base text-white bg-primary/40 cursor-not-allowed">Place Bid</button>
            <?php endif; ?>

          <?php elseif ($isEnded): ?>
          <p class="text-sm text-center text-slate-500 dark:text-slate-400 py-4">This lot has ended. Bidding is closed.</p>
          <?php endif; ?>

          <!-- Share row -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
            <span class="text-xs text-slate-400 dark:text-slate-500">Share this lot</span>
            <button onclick="copyLink()" class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-primary transition-colors px-2.5 py-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              Copy link
            </button>
          </div>
        </div>
      </div>

      <?php if (!empty($item['market_value']) && (float)$item['market_value'] > 0): ?>
      <p class="text-xs text-center text-slate-400 dark:text-slate-500 mt-3 leading-relaxed px-2">
        Market value <strong class="text-slate-500 dark:text-slate-400"><?= formatCurrency((float)$item['market_value']) ?></strong> &middot; Bids above this qualify for Gift Aid
      </p>
      <?php endif; ?>
    </div>
  </div><!-- /right column -->

</div><!-- /grid -->

<!-- ══════════════════════════════════════ MOBILE BID BAR ══ -->
<?php if ($isActive): ?>
<div class="lg:hidden mobile-bid-bar" id="mobile-bid-bar">
  <div class="flex items-center gap-3 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 px-4 py-3">
    <div class="flex-1 min-w-0">
      <p class="text-xs text-slate-400 dark:text-slate-500 font-medium"><?= $bidCount > 0 ? 'Current bid' : 'Starting bid' ?></p>
      <p id="mobile-current-bid" class="text-xl font-black text-slate-900 dark:text-white tracking-tight"><?= formatCurrency($currentBid) ?></p>
    </div>
    <?php if (!empty($item['ends_at'])): ?>
    <div class="flex-shrink-0 text-right mr-3 hidden sm:block">
      <p class="text-xs text-slate-400 dark:text-slate-500">Ends in</p>
      <p id="mobile-countdown" class="text-sm font-bold text-amber-600 dark:text-amber-400">--</p>
    </div>
    <?php endif; ?>
    <?php if ($user): ?>
    <button
      popovertarget="bid-popover"
      class="flex-shrink-0 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-sm transition-colors"
    >
      Bid Now
    </button>
    <?php else: ?>
    <a
      href="<?= e($basePath) ?>/login"
      class="flex-shrink-0 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-sm transition-colors"
    >
      Sign In
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════ BID POPOVER (mobile) ══ -->
<?php if ($user): ?>
<div
  id="bid-popover"
  popover
  class="fixed inset-x-4 bottom-4 top-auto m-0 w-auto max-w-none rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xl p-6 z-50"
>
  <div class="flex items-center justify-between mb-5">
    <h3 class="text-base font-bold text-slate-900 dark:text-white">Place Your Bid</h3>
    <button popovertarget="bid-popover" popovertargetaction="hide" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div class="mb-4 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl flex items-center justify-between">
    <div>
      <p class="text-xs text-slate-400 dark:text-slate-500"><?= $bidCount > 0 ? 'Current bid' : 'Starting bid' ?></p>
      <p class="text-2xl font-black text-slate-900 dark:text-white"><?= formatCurrency($currentBid) ?></p>
    </div>
    <?php if (!empty($item['ends_at'])): ?>
    <div class="text-right">
      <p class="text-xs text-slate-400 dark:text-slate-500">Ends in</p>
      <p id="popover-countdown" class="text-sm font-bold text-amber-600 dark:text-amber-400">--</p>
    </div>
    <?php endif; ?>
  </div>

  <form method="POST" action="<?= e($basePath) ?>/bids" class="space-y-3">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
    <input type="hidden" name="item_slug" value="<?= e($item['slug'] ?? '') ?>" />
    <div>
      <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Your bid</label>
      <div class="relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
        <input
          type="number"
          name="amount"
          value="<?= number_format($minBid, 2, '.', '') ?>"
          min="<?= number_format($minBid, 2, '.', '') ?>"
          step="0.01"
          class="w-full pl-8 pr-3 py-3 text-base font-bold bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-900 dark:text-white"
        />
      </div>
      <p class="text-xs text-slate-400 mt-1.5">Minimum bid: <?= formatCurrency($minBid) ?></p>
    </div>
    <button type="submit" class="w-full py-4 rounded-xl font-bold text-base text-white bg-primary hover:bg-primary-hover transition-colors">
      Place Bid
    </button>
  </form>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
// Page-specific scripts for countdown and AJAX polling
$itemSlug  = e($item['slug'] ?? '');
$endsAt    = e($item['ends_at'] ?? '');
$isActiveJs = $isActive ? 'true' : 'false';
$pageScripts = <<<JS

// ── Countdown ──
(function() {
  const endsAt = {$isActiveJs} ? new Date('{$endsAt}').getTime() : 0;
  if (!endsAt) return;

  function fmt(ms) {
    if (ms <= 0) return 'Ended';
    const h = Math.floor(ms / 3600000);
    const m = String(Math.floor((ms % 3600000) / 60000)).padStart(2, '0');
    const s = String(Math.floor((ms % 60000) / 1000)).padStart(2, '0');
    return h + 'h ' + m + 'm ' + s + 's';
  }

  function tick() {
    const diff = endsAt - Date.now();
    const str = fmt(diff);
    ['panel-countdown', 'mobile-countdown', 'popover-countdown'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = str;
    });
    if (diff <= 0) clearInterval(timer);
  }

  const timer = setInterval(tick, 1000);
  tick();
})();

// ── AJAX bid polling (every 10s on active items) ──
(function() {
  if (!{$isActiveJs}) return;
  const basePath = '{$basePath}';
  setInterval(function() {
    fetch(basePath + '/api/current-bid/{$itemSlug}')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        const cbEl = document.getElementById('current-bid');
        if (cbEl) cbEl.textContent = d.current_bid;
        const mbEl = document.getElementById('mobile-current-bid');
        if (mbEl) mbEl.textContent = d.current_bid;
        const bcEl = document.getElementById('bid-count');
        if (bcEl) bcEl.textContent = d.bid_count + ' bid' + (d.bid_count !== 1 ? 's' : '');
      })
      .catch(function() {});
  }, 10000);
})();

// ── Copy link ──
function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(function() {
    showToast('Link copied to clipboard', 'success');
  }).catch(function() {});
}
JS;
?>
