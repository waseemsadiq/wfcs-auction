<?php
/**
 * Payment pending view — shown when Stripe is not yet configured.
 *
 * Variables available:
 *   $payment  — payment record (id, amount, status, ...)
 *   $item     — item record (title, slug, ...)
 *   $user     — authenticated user (name, email, ...)
 *   $basePath — base URL path (global)
 */
global $basePath;
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.08s; }
  .delay-2  { animation-delay: 0.16s; }
</style>

<!-- Pending heading -->
<div class="fade-up text-center mb-8">
  <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mx-auto mb-4">
    <svg class="w-8 h-8 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Payment Coming Soon</h1>
  <p class="text-slate-500 dark:text-slate-400 mt-1.5 text-sm leading-relaxed">
    Congratulations on winning your item!<br>
    Online payment is being set up — our team will send you payment instructions shortly.
  </p>
</div>

<!-- Item summary -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-6 mb-5">
  <div class="flex items-start gap-4 pb-5 border-b border-slate-100 dark:border-slate-700/40">
    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
      <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-xs font-bold text-amber-500 uppercase tracking-widest mb-0.5">Winning Lot</p>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-snug"><?= e($item['title']) ?></h2>
    </div>
  </div>
  <div class="flex items-center justify-between pt-4">
    <div>
      <p class="text-xs text-slate-500 dark:text-slate-400">Winner</p>
      <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mt-0.5"><?= e($user['name']) ?></p>
    </div>
    <div class="text-right">
      <p class="text-xs text-slate-500 dark:text-slate-400">Amount Due</p>
      <p class="text-2xl font-black text-slate-900 dark:text-white mt-0.5"><?= e(formatCurrency((float)$payment['amount'])) ?></p>
    </div>
  </div>
</div>

<!-- What to expect -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-6 mb-5">
  <h2 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">What to expect</h2>
  <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      We will email <strong class="text-slate-800 dark:text-slate-200"><?= e($user['email']) ?></strong> with payment instructions within 2 business days.
    </li>
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Your item is reserved for you and will not be re-auctioned while payment is pending.
    </li>
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.49 12 19.79 19.79 0 0 1 1.43 3.37 2 2 0 0 1 3.41 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8a16 16 0 0 0 6 6l.62-.62a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      Questions? Call or email us at <a href="mailto:info@wellfoundation.org.uk" class="text-primary hover:underline">info@wellfoundation.org.uk</a>.
    </li>
  </ul>
</div>

<!-- Back to My Bids -->
<div class="fade-up delay-2 text-center">
  <a href="<?= e($basePath) ?>/my-bids"
     class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-primary/25">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to My Bids
  </a>
</div>
