<?php
/**
 * Reset Password page content fragment — rendered inside layouts/public.php
 *
 * Variables available:
 *   $basePath   (global)
 *   $csrfToken  (global)
 *   $token      string — raw token from query string (may be empty string)
 *   $tokenError string|null — set if token is missing or invalid
 */
global $basePath, $csrfToken;
$token      = $token      ?? '';
$tokenError = $tokenError ?? null;
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
  .delay-4  { animation-delay: 0.24s; }

  .field { transition: border-color .15s, box-shadow .15s; }
  .field:focus {
    outline: none;
    border-color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.18);
  }

  #strength-bar { transition: width .3s ease, background-color .3s ease; }
</style>

<div class="flex items-center justify-center py-4">
  <div class="w-full">

    <!-- Split card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl ring-1 ring-slate-900/5 dark:ring-white/5 overflow-hidden flex flex-col md:flex-row min-h-[480px]">

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
              "Almost there. Choose a strong password and you'll be back to bidding in no&nbsp;time."
            </p>
            <footer class="mt-5 flex items-center gap-3">
              <div class="w-8 h-px bg-primary"></div>
              <span class="text-sm text-slate-400 font-medium">The Well Foundation</span>
            </footer>
          </blockquote>
        </div>

        <!-- Password tips -->
        <div class="relative z-10 flex-1 flex flex-col justify-end">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Password tips</p>
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              <p class="text-xs text-slate-400">At least 8 characters long</p>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              <p class="text-xs text-slate-400">Mix of letters, numbers and symbols</p>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl">
              <svg class="w-5 h-5 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              <p class="text-xs text-slate-400">Don't reuse old passwords</p>
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

        <?php if ($tokenError !== null): ?>
          <!-- ─── INVALID TOKEN STATE ─── -->
          <div class="text-center fade-up">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Link expired or invalid</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-8"><?= e($tokenError) ?></p>
            <a href="<?= e($basePath) ?>/forgot-password" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors">
              Request a new link
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <div class="mt-6">
              <a href="<?= e($basePath) ?>/login" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-primary transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                Back to Sign in
              </a>
            </div>
          </div>

        <?php else: ?>
          <!-- ─── RESET FORM ─── -->
          <div class="mb-8 fade-up">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Set a new password</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Choose a strong password for your account.</p>
          </div>

          <form method="POST" action="<?= e($basePath) ?>/reset-password" class="space-y-5" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
            <input type="hidden" name="token" value="<?= e($token) ?>" />

            <!-- New password -->
            <div class="fade-up delay-1">
              <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">New password</label>
              <div class="relative">
                <input
                  type="password" id="password" name="password" autocomplete="new-password"
                  placeholder="••••••••" required oninput="updateStrength(this.value)"
                  class="field w-full px-4 py-3 pr-11 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
                />
                <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <!-- Strength bar -->
              <div class="mt-2 h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div id="strength-bar" class="h-full w-0 rounded-full bg-slate-300"></div>
              </div>
              <p id="strength-label" class="mt-1 text-xs text-slate-400"></p>
            </div>

            <!-- Confirm password -->
            <div class="fade-up delay-2">
              <label for="confirm" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirm new password</label>
              <div class="relative">
                <input
                  type="password" id="confirm" name="confirm" autocomplete="new-password"
                  placeholder="••••••••" required
                  class="field w-full px-4 py-3 pr-11 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
                />
                <button type="button" onclick="togglePassword('confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <p id="match-error" class="hidden mt-1 text-xs text-red-500">Passwords do not match.</p>
            </div>

            <!-- Submit -->
            <div class="fade-up delay-3">
              <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors"
              >
                Reset password
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </button>
            </div>

            <!-- Back to sign in -->
            <div class="fade-up delay-4 text-center">
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

<script>
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  const isText = field.type === 'text';
  field.type = isText ? 'password' : 'text';
  btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
}
function updateStrength(val) {
  const bar   = document.getElementById('strength-bar');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const states = [
    { w: '0%',   color: 'bg-slate-300', text: '' },
    { w: '25%',  color: 'bg-red-400',   text: 'Weak' },
    { w: '50%',  color: 'bg-amber-400', text: 'Fair' },
    { w: '75%',  color: 'bg-yellow-400',text: 'Good' },
    { w: '100%', color: 'bg-green-500', text: 'Strong' },
  ];
  const s = val.length === 0 ? states[0] : (states[score] || states[1]);
  bar.style.width = s.w;
  bar.className = 'h-full rounded-full ' + s.color;
  label.textContent = s.text;
  label.className = 'mt-1 text-xs ' + (score >= 3 ? 'text-green-600 dark:text-green-400' : score >= 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500');
}
// Client-side confirm match check before submit (server also validates)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[action$="/reset-password"]');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    const pw  = document.getElementById('password');
    const cf  = document.getElementById('confirm');
    const err = document.getElementById('match-error');
    if (pw && cf && err && pw.value !== cf.value) {
      e.preventDefault();
      err.classList.remove('hidden');
      cf.focus();
    }
  });
});
</script>
