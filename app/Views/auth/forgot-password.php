<?php
/**
 * Forgot Password page content fragment — rendered inside layouts/public.php
 *
 * Variables available:
 *   $basePath   (global)
 *   $csrfToken  (global)
 *   $sent       bool — true when ?sent=1 is present (show "check inbox" state)
 */
global $basePath, $csrfToken;
$sent = $sent ?? false;
?>

<style>
  .panel-grain::after {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.05'/%3E%3C/svg%3E");
    opacity: .4; pointer-events: none; border-radius: inherit;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
  .delay-3  { animation-delay: 0.18s; }

  .field { transition: border-color .15s, box-shadow .15s; }
  .field:focus {
    outline: none;
    border-color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.18);
  }
</style>

<div class="flex items-center justify-center py-4">
  <div class="w-full">

    <!-- Split card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl ring-1 ring-slate-900/5 dark:ring-white/5 overflow-hidden flex flex-col md:flex-row min-h-[420px]">

      <!-- ── LEFT PANEL (desktop only) ── -->
      <div class="relative hidden md:flex md:w-[42%] bg-slate-900 panel-grain flex-col p-10 overflow-hidden">
        <div class="absolute top-0 left-0 w-80 h-80 bg-primary/20 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2 pointer-events-none"></div>
        <div class="absolute inset-0 opacity-[0.03]" aria-hidden="true">
          <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid)"/></svg>
        </div>

        <!-- Quote -->
        <div class="relative z-10 flex-1 flex flex-col justify-end">
          <blockquote class="mt-2">
            <p class="text-2xl font-semibold text-white leading-snug tracking-tight">
              "Don't worry &mdash; it happens to&nbsp;the best of us. We'll get you back in&nbsp;moments."
            </p>
            <footer class="mt-5 flex items-center gap-3">
              <div class="w-8 h-px bg-primary"></div>
              <span class="text-sm text-slate-400 font-medium">The Well Foundation</span>
            </footer>
          </blockquote>
        </div>

        <!-- Trust items -->
        <div class="relative z-10 flex-1 flex flex-col justify-end">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Your account is safe</p>
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <div>
                <p class="text-xs font-semibold text-white">Secure reset link</p>
                <p class="text-xs text-slate-500">Expires after 60 minutes</p>
              </div>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <div>
                <p class="text-xs font-semibold text-white">Sent to your inbox</p>
                <p class="text-xs text-slate-500">Check spam if it doesn't arrive</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── RIGHT PANEL ── -->
      <div class="flex-1 flex flex-col justify-center px-6 py-10 sm:px-10 md:px-12">

        <!-- Mobile logo -->
        <div class="flex justify-center mb-8 md:hidden">
          <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-14 w-auto dark:hidden" />
          <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-14 w-auto hidden dark:block" />
        </div>

        <?php if ($sent): ?>
          <!-- ─── SUCCESS / SENT STATE ─── -->
          <div class="text-center fade-up">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Check your inbox</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">If that email is registered with us, we've sent a password reset link.</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mb-8 mt-4">The link expires in 60 minutes. If you don't see the email, check your spam folder.</p>
            <a href="<?= e($basePath) ?>/login" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
              Back to Sign in
            </a>
          </div>

        <?php else: ?>
          <!-- ─── FORM STATE ─── -->
          <div class="mb-8 fade-up">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Forgot your password?</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Enter your email and we'll send you a reset link.</p>
          </div>

          <form method="POST" action="<?= e($basePath) ?>/forgot-password" class="space-y-5" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

            <!-- Email -->
            <div class="fade-up delay-1">
              <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email address</label>
              <input
                type="email" id="email" name="email" autocomplete="email"
                placeholder="you@example.com" required
                class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
              />
            </div>

            <!-- Submit -->
            <div class="fade-up delay-2">
              <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors"
              >
                Send reset link
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </button>
            </div>

            <!-- Back to sign in -->
            <div class="fade-up delay-3 text-center">
              <a href="<?= e($basePath) ?>/login" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                Back to Sign in
              </a>
            </div>

          </form>

        <?php endif; ?>

      </div>
    </div>

    <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-6">
      By using this service you agree to our
      <a href="<?= e($basePath) ?>/terms" class="underline hover:text-primary transition-colors">Terms &amp; Conditions</a>
      and
      <a href="<?= e($basePath) ?>/privacy" class="underline hover:text-primary transition-colors">Privacy Policy</a>.
    </p>
  </div>
</div>
