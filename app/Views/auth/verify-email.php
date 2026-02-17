<?php
/**
 * Verify Email page content fragment — rendered inside layouts/public.php
 *
 * Variables:
 *   $pending     (bool)    — true if user just registered, awaiting verification
 *   $verifyError (string|null) — set when token is invalid/expired
 *
 * $basePath and $csrfToken are globals.
 */
global $basePath, $csrfToken;

$pending     = !empty($pending)     ? $pending     : (!empty($_GET['pending']));
$verifyError = $verifyError ?? null;
?>

<div class="flex items-center justify-center py-8">
  <div class="w-full max-w-md mx-auto">

    <?php if ($verifyError !== null): ?>
      <!-- ── Invalid / expired token ── -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/5 dark:ring-white/5 p-10 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-2xl mb-6">
          <svg class="w-8 h-8 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Verification link expired</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8"><?= e($verifyError) ?></p>
        <div class="space-y-3">
          <a href="<?= e($basePath) ?>/login" class="w-full flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl transition-colors">
            Back to sign in
          </a>
          <?php $authUser = getAuthUser(); if ($authUser): ?>
            <form method="POST" action="<?= e($basePath) ?>/resend-verification">
              <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
              <button type="submit" class="w-full px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors">
                Resend verification email
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($pending): ?>
      <!-- ── Pending — check your inbox ── -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/5 dark:ring-white/5 p-10 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-6">
          <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Check your inbox</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">We've sent a verification link to your email address.</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">Click the link in the email to activate your account. The link expires in 24 hours.</p>

        <div class="bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600/40 rounded-xl px-5 py-4 mb-8 text-left">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Can't find the email?</p>
          <ul class="text-sm text-slate-500 dark:text-slate-400 space-y-1.5 list-disc list-inside">
            <li>Check your spam or junk folder</li>
            <li>Make sure the email address you registered with is correct</li>
          </ul>
        </div>

        <form method="POST" action="<?= e($basePath) ?>/resend-verification">
          <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
          <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.18-3.89"/></svg>
            Resend verification email
          </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-600">
          Wrong account?
          <a href="<?= e($basePath) ?>/logout" class="underline hover:text-primary transition-colors">Sign out</a>
        </p>
      </div>

    <?php else: ?>
      <!-- ── Default state — no token, no pending ── -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/5 dark:ring-white/5 p-10 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-6">
          <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Verify your email</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">Your account requires email verification before you can place bids.</p>
        <a href="<?= e($basePath) ?>/login" class="w-full flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl transition-colors">
          Back to sign in
        </a>
      </div>

    <?php endif; ?>

  </div>
</div>
