<?php
/**
 * Admin Settings view.
 *
 * Variables available:
 *   $settings  — key => value array of all settings (from SettingsRepository::all())
 *   $user      — authenticated admin user
 *   $basePath  — base URL path (global)
 *   $csrfToken — CSRF token (global)
 */
global $basePath, $csrfToken;

$s = function (string $key, string $default = '') use ($settings): string {
    return e($settings[$key] ?? $default);
};
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.4s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.05s; }
  .delay-2  { animation-delay: 0.10s; }
  .delay-3  { animation-delay: 0.15s; }

  .settings-card {
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
  }
  .dark .settings-card {
    background: #1e293b;
    border-color: rgba(71,85,105,0.4);
  }

  .subnav-pill { position: relative; transition: color 0.15s ease; }
  .subnav-pill.active { color: #45a2da; }
  .subnav-pill.active::after {
    content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
    height: 2px; background: #45a2da; border-radius: 9999px;
  }
</style>

<div class="space-y-6 max-w-4xl mx-auto">

  <!-- Page heading -->
  <div class="fade-up">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Settings</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Application preferences and integrations</p>
  </div>

  <!-- ── 1. Stripe Payments ── -->
  <div class="fade-up delay-1 settings-card">
    <form method="POST" action="<?= e($basePath) ?>/admin/settings">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="section" value="stripe">

      <div class="flex items-center gap-3 mb-5">
        <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Stripe Payments</h2>
      </div>

      <?php
        $pubKey     = $settings['stripe_publishable_key'] ?? '';
        $secKey     = $settings['stripe_secret_key'] ?? '';
        $whToken    = $settings['stripe_webhook_url_token'] ?? '';
        $pubHolder  = !empty($pubKey) ? '••••••••••••••••••••' . substr($pubKey, -4)  : 'pk_live_... or pk_test_...';
        $secHolder  = !empty($secKey) ? '••••••••••••••••••••' . substr($secKey, -4)  : 'sk_live_... or sk_test_...';
        $whHolder   = !empty($whToken) ? '••••••••••••••••••••' . substr($whToken, -4) : 'Generate a random token';
      ?>
      <div class="grid grid-cols-1 gap-4 mb-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
            Stripe Publishable Key
          </label>
          <input type="text" name="stripe_publishable_key"
                 placeholder="<?= e($pubHolder) ?>"
                 value=""
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Leave blank to keep existing value.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
            Stripe Secret Key
          </label>
          <input type="text" name="stripe_secret_key"
                 placeholder="<?= e($secHolder) ?>"
                 value=""
                 autocomplete="off"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Leave blank to keep existing value.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
            Webhook URL Token
          </label>
          <input type="text" name="stripe_webhook_url_token"
                 placeholder="<?= e($whHolder) ?>"
                 value=""
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
          <?php if (!empty($whToken)): ?>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Webhook URL: <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded font-mono">/webhook/stripe?webhook_secret=<?= e($whToken) ?></code>
          </p>
          <?php else: ?>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Leave blank to keep existing value.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit"
                class="px-5 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg transition-colors">
          Save Stripe Settings
        </button>
      </div>
    </form>
  </div>

  <!-- ── 2. SMTP / Email ── -->
  <div class="fade-up delay-2 settings-card">
    <form method="POST" action="<?= e($basePath) ?>/admin/settings">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="section" value="email">

      <div class="flex items-center gap-3 mb-5">
        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">SMTP / Email</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">SMTP Host</label>
          <input type="text" name="smtp_host"
                 placeholder="mail.wellfoundation.org.uk"
                 value="<?= $s('smtp_host') ?>"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">SMTP Port</label>
          <input type="text" name="smtp_port"
                 placeholder="587"
                 value="<?= $s('smtp_port', '587') ?>"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">SMTP Username</label>
          <input type="text" name="smtp_username"
                 placeholder="noreply@wellfoundation.org.uk"
                 value="<?= $s('smtp_username') ?>"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <?php $smtpPass = $settings['smtp_password'] ?? ''; ?>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">SMTP Password</label>
          <input type="text" name="smtp_password"
                 placeholder="<?= !empty($smtpPass) ? '••••••••••••••••••••' . substr($smtpPass, -2) : 'SMTP password' ?>"
                 value=""
                 autocomplete="off"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Leave blank to keep existing value.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">From Name</label>
          <input type="text" name="email_from_name"
                 placeholder="The Well Foundation"
                 value="<?= $s('email_from_name', 'The Well Foundation') ?>"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">From Email</label>
          <input type="email" name="email_from"
                 placeholder="noreply@wellfoundation.org.uk"
                 value="<?= $s('email_from', 'noreply@wellfoundation.org.uk') ?>"
                 class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit"
                class="px-5 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg transition-colors">
          Save Email Settings
        </button>
      </div>
    </form>
  </div>

  <!-- ── 3. Notifications ── -->
  <div class="fade-up delay-3 settings-card">
    <form method="POST" action="<?= e($basePath) ?>/admin/settings">
      <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="section" value="notifications">

      <div class="flex items-center gap-3 mb-5">
        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Notifications</h2>
      </div>

      <div class="space-y-3 mb-4">
        <!-- Outbid email -->
        <div class="flex items-center justify-between py-3 px-4 bg-slate-50 dark:bg-slate-700/40 rounded-lg">
          <div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Outbid email notification</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Send email when a bidder is outbid</p>
          </div>
          <label class="flex items-center cursor-pointer flex-shrink-0">
            <input type="checkbox" name="notify_outbid" value="1"
                   <?= !empty($settings['notify_outbid']) ? 'checked' : '' ?>
                   class="toggle-input sr-only" />
            <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200">
              <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
            </div>
          </label>
        </div>

        <!-- Winner email -->
        <div class="flex items-center justify-between py-3 px-4 bg-slate-50 dark:bg-slate-700/40 rounded-lg">
          <div>
            <p class="text-sm font-medium text-slate-900 dark:text-white">Winner email notification</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Send email when auction closes with payment link</p>
          </div>
          <label class="flex items-center cursor-pointer flex-shrink-0">
            <input type="checkbox" name="notify_winner" value="1"
                   <?= !empty($settings['notify_winner']) ? 'checked' : '' ?>
                   class="toggle-input sr-only" />
            <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200">
              <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
            </div>
          </label>
        </div>

        <!-- Payment reminder -->
        <div class="flex items-center justify-between py-3 px-4 bg-slate-50 dark:bg-slate-700/40 rounded-lg">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900 dark:text-white">Payment reminder after</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Days after winning before sending a reminder</p>
          </div>
          <div class="flex items-center gap-2 ml-4 flex-shrink-0">
            <input type="number" name="payment_reminder_days" min="1" max="30"
                   value="<?= e($settings['payment_reminder_days'] ?? '3') ?>"
                   class="w-16 px-3 py-1.5 text-sm bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 rounded-lg text-slate-900 dark:text-white text-center focus:outline-none focus:border-primary" />
            <span class="text-xs text-slate-500 dark:text-slate-400">days</span>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit"
                class="px-5 py-2 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg transition-colors">
          Save Notification Settings
        </button>
      </div>
    </form>
  </div>

</div>
