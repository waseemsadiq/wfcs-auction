<?php
/**
 * Payment checkout view — shown when Stripe is configured and payment is pending.
 *
 * Variables available:
 *   $payment  — payment record (id, amount, gift_aid_claimed, gift_aid_amount, ...)
 *   $item     — item record (title, slug, ...)
 *   $user     — authenticated user (name, email, gift_aid_eligible, ...)
 *   $basePath — base URL path (global)
 *   $csrfToken — CSRF token (global)
 *
 * This page is only shown if Stripe IS configured. It redirects immediately
 * to Stripe Checkout — there is no card form on this page (Stripe hosts that).
 * The layout renders the standard header/footer; only the main content is here.
 */
global $basePath, $csrfToken;

$giftAidEligible  = !empty($user['gift_aid_eligible']);
$giftAidAmount    = $giftAidEligible
    ? round((float)$payment['amount'] * 0.25, 2)
    : 0;
$winnerInitials   = mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))
    . '***'
    . mb_strtoupper(mb_substr(strrchr($user['name'] ?? 'U', ' ') ?: $user['name'], -1));
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.08s; }
  .delay-2  { animation-delay: 0.16s; }
  .delay-3  { animation-delay: 0.24s; }

  /* ─── Gift Aid animated panels ─── */
  .ga-panel {
    display: grid;
    grid-template-rows: 1fr;
    opacity: 1;
    transition: grid-template-rows 0.35s cubic-bezier(0.4,0,0.2,1), opacity 0.28s ease;
  }
  .ga-panel > .ga-inner { overflow: hidden; }
  .ga-panel.ga-collapsed { grid-template-rows: 0fr; opacity: 0; }

  /* ─── Toggle switch ─── */
  .toggle-input ~ .toggle-track .toggle-knob { transform: translateX(0); }
  .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
  .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }
</style>

<!-- Heading -->
<div class="fade-up text-center mb-8">
  <div class="w-14 h-14 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-4">
    <svg class="w-7 h-7 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Congratulations!</h1>
  <p class="text-slate-500 dark:text-slate-400 mt-1.5 text-sm leading-relaxed">
    You won an item at The Well Foundation auction.<br>
    You will be redirected to our secure payment page.
  </p>
</div>

<!-- Item summary card -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-6 mb-5">
  <div class="flex items-start gap-4 pb-5 border-b border-slate-100 dark:border-slate-700/40">
    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-xs font-bold text-primary uppercase tracking-widest mb-0.5">Winning Lot</p>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-snug"><?= e($item['title']) ?></h2>
    </div>
  </div>
  <div class="flex items-center justify-between pt-4">
    <div>
      <p class="text-xs text-slate-500 dark:text-slate-400">Winner</p>
      <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 font-mono mt-0.5"><?= e($winnerInitials) ?></p>
    </div>
    <div class="text-right">
      <p class="text-xs text-slate-500 dark:text-slate-400">Winning Bid</p>
      <p class="text-2xl font-black text-slate-900 dark:text-white mt-0.5"><?= e(formatCurrency((float)$payment['amount'])) ?></p>
    </div>
  </div>
</div>

<?php if ($giftAidEligible): ?>
<!-- Gift Aid section -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-5">
  <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-slate-100 dark:border-slate-700/40">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
      </div>
      <div>
        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Claim Gift Aid</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
          Worth an extra <strong class="text-green-600 dark:text-green-400"><?= e(formatCurrency($giftAidAmount)) ?></strong> to the charity at no cost to you
        </p>
      </div>
    </div>
    <label class="flex items-center cursor-pointer flex-shrink-0">
      <input type="checkbox" id="gift-aid-toggle" class="toggle-input sr-only" checked onchange="toggleGiftAid(this)" />
      <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
        <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
      </div>
    </label>
  </div>

  <!-- Body — shown when Gift Aid is ON -->
  <div id="gift-aid-body" class="ga-panel"><div class="ga-inner"><div class="px-5 py-4">
    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed border-l-2 border-green-400 pl-3">
      I confirm I am a UK taxpayer and I would like The Well Foundation to claim Gift Aid on the charitable portion of my winning bid. I understand I must pay UK Income or Capital Gains Tax equal to or greater than the amount of Gift Aid claimed.
    </p>
  </div></div></div>

  <!-- Body — shown when Gift Aid is OFF -->
  <div id="gift-aid-off" class="ga-panel ga-collapsed"><div class="ga-inner"><div class="px-5 py-4">
    <p class="text-xs text-slate-400 dark:text-slate-500">Gift Aid not claimed. The Well Foundation will not apply for HMRC reclaim on this donation.</p>
  </div></div></div>
</div>
<?php endif; ?>

<!-- Payment breakdown -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-5 mb-5">
  <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Payment Breakdown</h3>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-sm text-slate-600 dark:text-slate-300">Winning Bid</span>
      <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= e(formatCurrency((float)$payment['amount'])) ?></span>
    </div>
    <?php if ($giftAidEligible && $giftAidAmount > 0): ?>
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-1.5">
        <span class="text-sm text-slate-600 dark:text-slate-300">Gift Aid benefit (HMRC claim)</span>
        <span class="text-xs px-1.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded font-semibold">Free</span>
      </div>
      <span class="text-sm font-semibold text-green-600 dark:text-green-400">+<?= e(formatCurrency($giftAidAmount)) ?></span>
    </div>
    <?php endif; ?>
    <div class="border-t border-slate-100 dark:border-slate-700/40 pt-3 flex items-center justify-between">
      <span class="text-sm font-bold text-slate-900 dark:text-white">Total to Pay</span>
      <span class="text-xl font-black text-slate-900 dark:text-white"><?= e(formatCurrency((float)$payment['amount'])) ?></span>
    </div>
  </div>
  <?php if ($giftAidEligible): ?>
  <p class="text-xs text-slate-400 dark:text-slate-500 mt-3 leading-relaxed">Gift Aid is reclaimed directly from HMRC by The Well Foundation and is not deducted from your payment.</p>
  <?php endif; ?>
</div>

<!-- Pay button — redirects to Stripe Checkout -->
<div class="fade-up delay-3 mb-5">
  <p class="text-xs text-slate-500 dark:text-slate-400 text-center mb-3">
    Clicking the button below will take you to our secure Stripe payment page.
  </p>
  <a href="<?= e($basePath . '/payment/' . $item['slug']) ?>"
     class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-primary hover:bg-primary-hover text-white text-base font-bold rounded-xl transition-colors shadow-lg shadow-primary/25">
    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    Pay <?= e(formatCurrency((float)$payment['amount'])) ?> Securely
  </a>
</div>

<!-- Trust signals -->
<div class="fade-up delay-3 flex flex-col items-center gap-2">
  <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
    <svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    <span>Secured by <strong class="text-slate-600 dark:text-slate-300">Stripe</strong> — 256-bit SSL encryption</span>
  </div>
  <p class="text-xs text-slate-400 dark:text-slate-500">The Well Foundation &middot; Charity No. SC040105</p>
</div>
