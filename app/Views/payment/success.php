<?php
/**
 * Payment success view — shown after payment is confirmed.
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
  .delay-3  { animation-delay: 0.24s; }
</style>

<!-- Success heading -->
<div class="fade-up text-center mb-8">
  <div class="w-16 h-16 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-4">
    <svg class="w-8 h-8 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  </div>
  <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Payment Successful!</h1>
  <p class="text-slate-500 dark:text-slate-400 mt-1.5 text-sm leading-relaxed">
    Thank you, <?= e($user['name']) ?>. Your payment has been received.<br>
    We will be in touch to arrange delivery or collection of your item.
  </p>
</div>

<!-- Order details -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-6 mb-5">
  <h2 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Order Details</h2>

  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-sm text-slate-600 dark:text-slate-300">Item</span>
      <span class="text-sm font-semibold text-slate-900 dark:text-white text-right max-w-xs truncate"><?= e($item['title']) ?></span>
    </div>
    <div class="flex items-center justify-between">
      <span class="text-sm text-slate-600 dark:text-slate-300">Amount paid</span>
      <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= e(formatCurrency((float)$payment['amount'])) ?></span>
    </div>
    <?php if (!empty($payment['stripe_session_id'])): ?>
    <div class="flex items-center justify-between">
      <span class="text-sm text-slate-600 dark:text-slate-300">Reference</span>
      <span class="text-xs font-mono text-slate-500 dark:text-slate-400"><?= e(substr($payment['stripe_session_id'], 0, 20)) ?>…</span>
    </div>
    <?php endif; ?>
    <div class="border-t border-slate-100 dark:border-slate-700/40 pt-3 flex items-center justify-between">
      <span class="text-sm text-slate-600 dark:text-slate-300">Status</span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold rounded-full">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Paid
      </span>
    </div>
  </div>
</div>

<!-- Gift Aid confirmation -->
<?php if (!empty($payment['gift_aid_claimed'])): ?>
<div class="fade-up delay-2 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800/30 rounded-2xl p-5 mb-5">
  <div class="flex items-start gap-3">
    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
    <div>
      <p class="text-sm font-semibold text-green-800 dark:text-green-300">Gift Aid Claimed</p>
      <p class="text-xs text-green-700 dark:text-green-400 mt-0.5 leading-relaxed">
        The Well Foundation will reclaim <?= e(formatCurrency((float)($payment['gift_aid_amount'] ?? 0))) ?> from HMRC on your behalf. Thank you for your generosity.
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- What happens next -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/40 p-6 mb-5">
  <h2 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">What happens next?</h2>
  <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      A confirmation email has been sent to <strong class="text-slate-800 dark:text-slate-200"><?= e($user['email']) ?></strong>.
    </li>
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      Our team will contact you within 2 business days to arrange delivery or collection.
    </li>
    <li class="flex items-start gap-2.5">
      <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Questions? Email us at <a href="mailto:info@wellfoundation.org.uk" class="text-primary hover:underline">info@wellfoundation.org.uk</a>.
    </li>
  </ul>
</div>

<!-- Back to My Bids -->
<div class="fade-up delay-3 text-center">
  <a href="<?= e($basePath) ?>/my-bids"
     class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-primary/25">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to My Bids
  </a>
</div>
