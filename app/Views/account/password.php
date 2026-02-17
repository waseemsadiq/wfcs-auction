<?php
/**
 * Account Settings — Change Password page content fragment
 * Rendered inside layouts/public.php
 *
 * Variables:
 *   $user      — full user row from DB
 *   $basePath  (global)
 *   $csrfToken (global)
 */
global $basePath, $csrfToken;

$initials    = strtoupper(substr((string)($user['name'] ?? '?'), 0, 1));
$memberSince = !empty($user['created_at'])
    ? date('M Y', strtotime((string)$user['created_at']))
    : '';
?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1 { animation-delay: 0.08s; }
  .delay-2 { animation-delay: 0.16s; }

  .account-tab { position: relative; padding-bottom: 2px; }
  .account-tab::after {
    content: ''; position: absolute; bottom: -2px; left: 0; right: 0;
    height: 2px; background: #45a2da;
    transform: scaleX(0); transform-origin: left; transition: transform .2s ease;
  }
  .account-tab.active::after { transform: scaleX(1); }

  /* Password strength bar */
  #pw-strength-bar { transition: width 0.3s ease, background-color 0.3s ease; }
</style>

<!-- Page header -->
<div class="fade-up mb-6">
  <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Account Settings</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
    Profile and security for <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($user['email'] ?? '') ?></span>
  </p>
</div>

<!-- Sub-nav tabs -->
<div class="fade-up mb-6 flex gap-6 border-b border-slate-200 dark:border-slate-700/40">
  <a href="<?= e($basePath) ?>/account/profile"
     class="account-tab pb-3 text-sm font-semibold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
    Profile
  </a>
  <a href="<?= e($basePath) ?>/account/password"
     class="account-tab active pb-3 text-sm font-semibold text-slate-900 dark:text-white">
    Password
  </a>
</div>

<!-- Password card -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 shadow-sm overflow-hidden mb-5">
  <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center gap-3">
    <div class="w-8 h-8 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
      <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-widest">Password &amp; Security</h2>
  </div>

  <form method="POST" action="<?= e($basePath) ?>/account/password" class="px-6 py-5 space-y-4" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

    <!-- Current password -->
    <div>
      <label for="current_password" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Current Password</label>
      <div class="relative">
        <input
          id="current_password"
          type="password"
          name="current_password"
          placeholder="Enter current password"
          autocomplete="current-password"
          required
          class="w-full px-3 py-2.5 pr-10 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <button type="button" onclick="togglePw('current_password')" class="absolute inset-y-0 right-3 flex items-center text-slate-300 hover:text-slate-500 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <!-- New password -->
    <div>
      <label for="new_password" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">New Password</label>
      <div class="relative">
        <input
          id="new_password"
          type="password"
          name="new_password"
          placeholder="At least 8 characters"
          autocomplete="new-password"
          oninput="updateStrength(this.value)"
          required
          class="w-full px-3 py-2.5 pr-10 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <button type="button" onclick="togglePw('new_password')" class="absolute inset-y-0 right-3 flex items-center text-slate-300 hover:text-slate-500 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <!-- Strength bar -->
      <div class="mt-2 h-1 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
        <div id="pw-strength-bar" class="h-full rounded-full w-0 bg-slate-300 dark:bg-slate-500"></div>
      </div>
      <p id="pw-strength-label" class="text-xs text-slate-400 mt-1 h-4"></p>
    </div>

    <!-- Confirm new password -->
    <div>
      <label for="confirm_password" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
      <input
        id="confirm_password"
        type="password"
        name="confirm_password"
        placeholder="Re-enter new password"
        autocomplete="new-password"
        oninput="checkMatch()"
        required
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
      />
      <p id="pw-match-error" class="hidden text-xs text-red-500 mt-1.5">Passwords do not match.</p>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-4 pt-1">
      <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
        Update Password
      </button>
      <a href="<?= e($basePath) ?>/forgot-password" class="text-sm text-primary hover:underline">
        Forgot your password?
      </a>
    </div>
  </form>
</div>

<script>
function togglePw(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

function updateStrength(val) {
  const bar = document.getElementById('pw-strength-bar');
  const lbl = document.getElementById('pw-strength-label');
  if (!val) { bar.style.width = '0'; lbl.textContent = ''; return; }
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/\d/.test(val))   score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const states = [
    { w: '20%',  color: '#f87171', text: 'Weak' },
    { w: '40%',  color: '#fb923c', text: 'Fair' },
    { w: '65%',  color: '#facc15', text: 'Good' },
    { w: '85%',  color: '#4ade80', text: 'Strong' },
    { w: '100%', color: '#22c55e', text: 'Very strong' },
  ];
  const s = states[Math.min(score - 1, 4)];
  bar.style.width = s.w;
  bar.style.backgroundColor = s.color;
  lbl.textContent = s.text;
  lbl.style.color = s.color;
  checkMatch();
}

function checkMatch() {
  const np  = document.getElementById('new_password').value;
  const cp  = document.getElementById('confirm_password').value;
  const err = document.getElementById('pw-match-error');
  if (cp.length > 0) {
    err.classList.toggle('hidden', np === cp);
  } else {
    err.classList.add('hidden');
  }
}
</script>
