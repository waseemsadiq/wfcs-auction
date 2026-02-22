<?php
/**
 * Login page content fragment — rendered inside layouts/public.php
 *
 * Variables available from controller + layout:
 *   $basePath  (global)
 *   $csrfToken (global)
 */
global $basePath, $csrfToken;
?>

<style>
  /* ─── Left panel grain ─── */
  .panel-grain::after {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.05'/%3E%3C/svg%3E");
    opacity: .4; pointer-events: none; border-radius: inherit;
  }

  /* ─── Fade-up ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
  .delay-3  { animation-delay: 0.18s; }
  .delay-4  { animation-delay: 0.24s; }
  .delay-5  { animation-delay: 0.30s; }

  /* ─── Field focus ring ─── */
  .field { transition: border-color .15s, box-shadow .15s; }
  .field:focus {
    outline: none;
    border-color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.18);
  }

  /* ─── Toggle switch ─── */
  .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
  .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }
</style>

<div class="flex items-center justify-center py-4">
  <div class="w-full">

    <!-- Split card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl ring-1 ring-slate-900/5 dark:ring-white/5 overflow-hidden flex flex-col md:flex-row min-h-[600px]">

      <!-- ── LEFT PANEL (desktop only) ── -->
      <div class="relative hidden md:flex md:w-[42%] bg-slate-900 panel-grain flex-col p-10 overflow-hidden">
        <!-- Decorative gradient orbs -->
        <div class="absolute top-0 left-0 w-80 h-80 bg-primary/20 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2 pointer-events-none"></div>
        <!-- Subtle grid -->
        <div class="absolute inset-0 opacity-[0.03]" aria-hidden="true">
          <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid)"/></svg>
        </div>

        <!-- Quote section -->
        <div class="relative z-10 flex-1 flex flex-col justify-end">
          <blockquote class="mt-2">
            <p class="text-2xl font-semibold text-white leading-snug tracking-tight">
              "Every bid is an act of&nbsp;generosity. Thank you for being part of something&nbsp;bigger."
            </p>
            <footer class="mt-5 flex items-center gap-3">
              <div class="w-8 h-px bg-primary"></div>
              <span class="text-sm text-slate-400 font-medium">The Well Foundation</span>
            </footer>
          </blockquote>
        </div>

        <!-- Trust badges -->
        <div class="relative z-10 flex-1 flex flex-col justify-end">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Verified &amp; trusted</p>
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <div>
                <p class="text-xs font-semibold text-white">Gift Aid Eligible</p>
                <p class="text-xs text-slate-500">Bids go 25% further</p>
              </div>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <div>
                <p class="text-xs font-semibold text-white">Charity No. SC040105</p>
                <p class="text-xs text-slate-500">Registered in Scotland</p>
              </div>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              <div>
                <p class="text-xs font-semibold text-white">Secure Payments</p>
                <p class="text-xs text-slate-500">Stripe-powered checkout</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── RIGHT PANEL: form ── -->
      <div class="flex-1 flex flex-col justify-center px-6 py-10 sm:px-10 md:px-12">

        <!-- Mobile logo (small screens only) -->
        <div class="flex justify-center mb-8 md:hidden">
          <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-14 w-auto dark:hidden" />
          <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-14 w-auto hidden dark:block" />
        </div>

        <!-- Heading -->
        <div class="mb-8 fade-up">
          <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Welcome back</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Sign in to place bids and track your lots.</p>
        </div>

        <!-- Login form -->
        <form method="POST" action="<?= e($basePath) ?>/login" class="space-y-5" novalidate>
          <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

          <!-- Email -->
          <div class="fade-up delay-1">
            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email address</label>
            <input
              type="email" id="email" name="email" autocomplete="email"
              placeholder="you@example.com"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
            />
          </div>

          <!-- Password -->
          <div class="fade-up delay-2">
            <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
            <div class="relative">
              <input
                type="password" id="password" name="password" autocomplete="current-password"
                placeholder="••••••••"
                class="field w-full px-4 py-3 pr-11 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
              />
              <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <!-- Remember me + Forgot -->
          <div class="fade-up delay-3 flex items-center justify-between">
            <label class="flex items-center gap-2.5 cursor-pointer">
              <input type="checkbox" name="remember" class="toggle-input sr-only" />
              <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
                <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
              </div>
              <span class="text-sm text-slate-600 dark:text-slate-400">Remember me</span>
            </label>
            <a href="<?= e($basePath) ?>/forgot-password" class="text-sm font-medium text-primary hover:text-primary-hover transition-colors">Forgot password?</a>
          </div>

          <!-- Submit -->
          <div class="fade-up delay-4">
            <button
              type="submit"
              class="w-full flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors"
            >
              Sign in
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
          </div>

          <!-- Divider -->
          <div class="fade-up delay-5 relative flex items-center gap-4 py-1">
            <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
            <span class="text-xs text-slate-400 dark:text-slate-500 font-medium whitespace-nowrap">New to WFCS Auction?</span>
            <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
          </div>

          <!-- Register link -->
          <div class="fade-up delay-5 text-center">
            <a href="<?= e($basePath) ?>/register" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary-hover transition-colors">
              Create an account
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>

        </form>

        <!-- Below form note -->
        <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-6">
          By signing in you agree to our
          <a href="<?= e($basePath) ?>/terms" class="underline hover:text-primary transition-colors">Terms &amp; Conditions</a>
          and
          <a href="<?= e($basePath) ?>/privacy" class="underline hover:text-primary transition-colors">Privacy Policy</a>.
        </p>

      </div>
    </div>

  </div>
</div>

<script>
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  const isText = field.type === 'text';
  field.type = isText ? 'password' : 'text';
  btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
}
</script>
