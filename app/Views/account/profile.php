<?php
/**
 * Account Settings — Profile page content fragment
 * Rendered inside layouts/public.php
 *
 * Variables:
 *   $user      — full user row from DB
 *   $basePath  (global)
 *   $csrfToken (global)
 */
global $basePath, $csrfToken;

$initials       = strtoupper(substr((string)($user['name'] ?? '?'), 0, 1));
$isVerified     = !empty($user['email_verified_at']);
$giftAidOn      = !empty($user['gift_aid_eligible']);
$memberSince    = !empty($user['created_at'])
    ? date('M Y', strtotime((string)$user['created_at']))
    : '';

// Split stored name into first / last for the profile form
$nameParts     = explode(' ', trim((string)($user['name'] ?? '')), 2);
$firstName     = $nameParts[0] ?? '';
$lastName      = $nameParts[1] ?? '';
$suggestedName = trim($firstName . ' ' . $lastName);
$giftAidName   = !empty($user['gift_aid_name']) ? $user['gift_aid_name'] : $suggestedName;
?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1 { animation-delay: 0.08s; }
  .delay-2 { animation-delay: 0.16s; }
  .delay-3 { animation-delay: 0.24s; }

  .account-tab { position: relative; padding-bottom: 2px; }
  .account-tab::after {
    content: ''; position: absolute; bottom: -2px; left: 0; right: 0;
    height: 2px; background: #45a2da;
    transform: scaleX(0); transform-origin: left; transition: transform .2s ease;
  }
  .account-tab.active::after { transform: scaleX(1); }

  /* Toggle switch */
  .toggle-input ~ .toggle-track .toggle-knob { transform: translateX(0); }
  .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
  .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }

  /* Danger zone card */
  .danger-card { border-color: #fecaca; }
  .dark .danger-card { border-color: rgba(153,27,27,0.4); }
</style>

<!-- Page header -->
<div class="fade-up mb-6 flex items-center gap-3">
  <a href="<?= e($basePath) ?>/my-bids" class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
  </a>
  <div>
    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Account Settings</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
      Profile, notifications and security for <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($user['email'] ?? '') ?></span>
    </p>
  </div>
</div>

<!-- Sub-nav tabs -->
<div class="fade-up mb-6 flex gap-6 border-b border-slate-200 dark:border-slate-700/40">
  <a href="<?= e($basePath) ?>/account/profile"
     class="account-tab active pb-3 text-sm font-semibold text-slate-900 dark:text-white">
    Profile
  </a>
  <a href="<?= e($basePath) ?>/account/password"
     class="account-tab pb-3 text-sm font-semibold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
    Password
  </a>
</div>

<!-- Profile card -->
<div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 shadow-sm overflow-hidden mb-5">
  <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center gap-3">
    <div class="w-8 h-8 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
      <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-widest">Profile</h2>
  </div>

  <!-- Avatar row -->
  <div class="px-6 pt-5 pb-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-primary/15 flex items-center justify-center flex-shrink-0 ring-2 ring-primary/20">
      <span class="text-xl font-black text-primary"><?= e($initials) ?></span>
    </div>
    <div>
      <p class="text-base font-bold text-slate-900 dark:text-white"><?= e($user['name'] ?? '') ?></p>
      <p class="text-sm text-slate-400 dark:text-slate-500 capitalize">
        <?= e($user['role'] ?? 'bidder') ?><?= $memberSince ? ' · member since ' . e($memberSince) : '' ?>
      </p>
    </div>
  </div>

  <!-- Profile form -->
  <form method="POST" action="<?= e($basePath) ?>/account/profile" class="px-6 py-5 space-y-5">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

    <!-- First / Last Name -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label for="first_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">First Name</label>
        <input
          type="text" id="first_name" name="first_name"
          value="<?= e($firstName) ?>"
          maxlength="128"
          required
          class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>
      <div>
        <label for="last_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Last Name</label>
        <input
          type="text" id="last_name" name="last_name"
          value="<?= e($lastName) ?>"
          maxlength="128"
          class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>
    </div>

    <!-- Email (read-only) -->
    <div>
      <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Email Address</label>
      <div class="flex items-center gap-3">
        <input
          type="email"
          value="<?= e($user['email'] ?? '') ?>"
          readonly
          class="flex-1 px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-400 dark:text-slate-500 cursor-not-allowed"
        />
        <?php if ($isVerified): ?>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 flex-shrink-0">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Verified
          </span>
        <?php else: ?>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 flex-shrink-0">
            Unverified
          </span>
        <?php endif; ?>
      </div>
      <p class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">
        To change your email contact <a href="mailto:info@wellfoundation.org.uk" class="text-primary hover:underline">info@wellfoundation.org.uk</a>
      </p>
    </div>

    <!-- Phone -->
    <div>
      <label for="phone" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Phone Number</label>
      <input
        type="tel" id="phone" name="phone"
        value="<?= e($user['phone'] ?? '') ?>"
        placeholder="+44 7700 000000"
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
      />
      <p class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">Used for important auction updates only. Never shared.</p>
    </div>

    <!-- Company Name -->
    <div>
      <label for="company_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Your Company <span class="normal-case font-normal">(optional)</span></label>
      <input
        type="text" id="company_name" name="company_name"
        value="<?= e($user['company_name'] ?? '') ?>"
        maxlength="255"
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
      />
    </div>

    <!-- Company Contact Name -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label for="company_contact_first_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Contact First Name</label>
        <input
          type="text" id="company_contact_first_name" name="company_contact_first_name"
          value="<?= e($user['company_contact_first_name'] ?? '') ?>"
          maxlength="100"
          class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>
      <div>
        <label for="company_contact_last_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Contact Last Name</label>
        <input
          type="text" id="company_contact_last_name" name="company_contact_last_name"
          value="<?= e($user['company_contact_last_name'] ?? '') ?>"
          maxlength="100"
          class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>
    </div>

    <!-- Company Contact Email -->
    <div>
      <label for="company_contact_email" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Company Contact Email</label>
      <input
        type="email" id="company_contact_email" name="company_contact_email"
        value="<?= e($user['company_contact_email'] ?? '') ?>"
        maxlength="255"
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
      />
    </div>

    <!-- Website -->
    <div>
      <label for="website" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Website</label>
      <input
        type="url" id="website" name="website"
        value="<?= e($user['website'] ?? '') ?>"
        placeholder="https://example.com"
        maxlength="255"
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
      />
    </div>

    <!-- Save -->
    <div>
      <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
        Save Changes
      </button>
    </div>
  </form>
</div>

<!-- Gift Aid card -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 shadow-sm overflow-hidden mb-5">
  <form method="POST" action="<?= e($basePath) ?>/account/profile" id="gift-aid-form">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
    <!-- We still send the name field even if toggled off — the service handles null -->
    <input type="hidden" name="name" value="<?= e($user['name'] ?? '') ?>" />

    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
        </div>
        <div>
          <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-widest">Gift Aid</h2>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Boost your donation by 25% at no cost to you.</p>
        </div>
      </div>
      <label class="flex items-center cursor-pointer flex-shrink-0">
        <input
          type="checkbox"
          id="gift-aid-toggle"
          name="gift_aid_eligible"
          value="1"
          class="toggle-input sr-only"
          <?= $giftAidOn ? 'checked' : '' ?>
          onchange="toggleGiftAid(this)"
        />
        <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
          <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
        </div>
      </label>
    </div>

    <div id="gift-aid-fields" class="px-6 py-5 space-y-4 <?= $giftAidOn ? '' : 'hidden' ?>">
      <div class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800/40">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p class="text-xs text-green-800 dark:text-green-300 leading-relaxed">
          I confirm I am a UK taxpayer and understand that if I pay less Income Tax or Capital Gains Tax than the Gift Aid claimed on all my donations, it is my responsibility to pay any difference.
          <a href="https://www.gov.uk/donating-to-charity/gift-aid" target="_blank" rel="noopener" class="underline hover:no-underline">Learn more ↗</a>
        </p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label for="gift_aid_name" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Full Name (as on tax records)</label>
          <input
            type="text"
            id="gift_aid_name"
            name="gift_aid_name"
            value="<?= e($giftAidName) ?>"
            data-suggested="<?= e($suggestedName) ?>"
            class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
          />
          <p id="gift-aid-name-hint" class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">
            <?php if ($giftAidName === $suggestedName && $suggestedName !== ''): ?>
              Suggested from your profile name — edit if different on your tax records.
            <?php else: ?>
              Enter your name exactly as it appears on your HMRC tax records.
            <?php endif; ?>
          </p>
        </div>
        <div class="sm:col-span-2">
          <label for="gift_aid_address" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Home Address (first line)</label>
          <input
            type="text"
            id="gift_aid_address"
            name="gift_aid_address"
            value="<?= e($user['gift_aid_address'] ?? '') ?>"
            placeholder="12 Example Street"
            class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
          />
        </div>
        <div>
          <label for="gift_aid_city" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Town / City</label>
          <input
            type="text"
            id="gift_aid_city"
            name="gift_aid_city"
            value="<?= e($user['gift_aid_city'] ?? '') ?>"
            placeholder="Glasgow"
            class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
          />
        </div>
        <div>
          <label for="gift_aid_postcode" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Postcode</label>
          <input
            type="text"
            id="gift_aid_postcode"
            name="gift_aid_postcode"
            value="<?= e($user['gift_aid_postcode'] ?? '') ?>"
            placeholder="G1 1AB"
            class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors"
          />
        </div>
      </div>
      <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
        Save Declaration
      </button>
    </div>

    <div id="gift-aid-off" class="px-6 py-5 <?= $giftAidOn ? 'hidden' : '' ?>">
      <p class="text-sm text-slate-500 dark:text-slate-400">Gift Aid is disabled. Enable it above to boost the value of your donations by 25% at no extra cost.</p>
    </div>
  </form>
</div>

<!-- Email Notifications card -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 shadow-sm overflow-hidden mb-5">
  <form method="POST" action="<?= e($basePath) ?>/account/notifications">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/40 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      </div>
      <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-widest">Email Notifications</h2>
    </div>
    <div class="divide-y divide-slate-100 dark:divide-slate-700/40">
      <!-- Outbid alerts -->
      <div class="flex items-center justify-between px-6 py-4 gap-6">
        <div>
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Outbid alerts</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sent immediately when someone outbids you.</p>
        </div>
        <label class="flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" name="notify_outbid" value="1" class="toggle-input sr-only" <?= !empty($user['notify_outbid']) ? 'checked' : '' ?> />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
        </label>
      </div>
      <!-- Ending soon -->
      <div class="flex items-center justify-between px-6 py-4 gap-6">
        <div>
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Auction ending soon</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">1-hour reminder for lots you're bidding on.</p>
        </div>
        <label class="flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" name="notify_ending_soon" value="1" class="toggle-input sr-only" <?= !empty($user['notify_ending_soon']) ? 'checked' : '' ?> />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
        </label>
      </div>
      <!-- Winning confirmation -->
      <div class="flex items-center justify-between px-6 py-4 gap-6">
        <div>
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Winning bid confirmation</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sent when you win an item at auction close.</p>
        </div>
        <label class="flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" name="notify_win" value="1" class="toggle-input sr-only" <?= !empty($user['notify_win']) ? 'checked' : '' ?> />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
        </label>
      </div>
      <!-- Payment reminders -->
      <div class="flex items-center justify-between px-6 py-4 gap-6">
        <div>
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Payment reminders</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Reminders to complete payment for items you've won.</p>
        </div>
        <label class="flex items-center cursor-pointer flex-shrink-0">
          <input type="checkbox" name="notify_payment" value="1" class="toggle-input sr-only" <?= !empty($user['notify_payment']) ? 'checked' : '' ?> />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
        </label>
      </div>
      <div class="px-6 py-4">
        <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
          Save Preferences
        </button>
      </div>
    </div>
  </form>
</div>

<!-- Danger Zone card -->
<div class="fade-up delay-3 danger-card bg-white dark:bg-slate-800 rounded-xl border shadow-sm overflow-hidden mb-5">
  <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/40 flex items-center gap-3">
    <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
      <svg class="w-4 h-4 text-red-500 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <h2 class="text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-widest">Danger Zone</h2>
  </div>
  <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Delete account</p>
      <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Permanently removes your account, bid history and Gift Aid declarations. Cannot be undone.</p>
    </div>
    <button
      popovertarget="delete-account-popover"
      class="flex-shrink-0 px-4 py-2.5 text-sm font-semibold text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700/60 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors"
    >
      Delete Account
    </button>
  </div>
</div>

<!-- Delete account popover -->
<div
  id="delete-account-popover"
  popover="manual"
  class="fixed inset-0 m-auto w-[min(28rem,calc(100%-2rem))] h-fit max-h-[90vh] rounded-2xl shadow-2xl p-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden"
>
  <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700/40">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
      </div>
      <h3 class="text-base font-semibold text-slate-900 dark:text-white">Delete Account?</h3>
    </div>
    <button type="button" popovertarget="delete-account-popover" popovertargetaction="hide" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="px-6 py-5 overflow-y-auto">
    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
      This will permanently delete your account, bid history, and Gift Aid declarations. Outstanding payments must be resolved first.
      <strong class="text-slate-900 dark:text-white">This cannot be undone.</strong>
    </p>
    <div class="mb-4">
      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
        Type <span class="font-mono text-red-500">DELETE</span> to confirm
      </label>
      <input
        id="delete-confirm-input"
        type="text"
        placeholder="DELETE"
        oninput="toggleDeleteBtn(this.value)"
        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-400/20 transition-colors"
      />
    </div>
    <div class="flex items-center justify-end gap-3">
      <button type="button" popovertarget="delete-account-popover" popovertargetaction="hide" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</button>
      <form method="POST" action="<?= e($basePath) ?>/account/delete">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />
        <button id="delete-btn" type="submit" disabled class="px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl shadow-sm transition-colors">
          Delete Account
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function toggleGiftAid(checkbox) {
  document.getElementById('gift-aid-fields').classList.toggle('hidden', !checkbox.checked);
  document.getElementById('gift-aid-off').classList.toggle('hidden', checkbox.checked);
}

function toggleDeleteBtn(val) {
  document.getElementById('delete-btn').disabled = val !== 'DELETE';
}

// Keep gift aid name in sync with profile first/last name,
// but only when the field still shows the auto-suggestion.
(function () {
  const firstInput  = document.getElementById('first_name');
  const lastInput   = document.getElementById('last_name');
  const nameInput   = document.getElementById('gift_aid_name');
  const hint        = document.getElementById('gift-aid-name-hint');

  if (!firstInput || !lastInput || !nameInput) return;

  function getSuggested() {
    return (firstInput.value.trim() + ' ' + lastInput.value.trim()).trim();
  }

  function syncName() {
    const suggested = getSuggested();
    // Only update if the field still matches whatever was auto-suggested on load
    if (nameInput.value === nameInput.dataset.suggested) {
      nameInput.value = suggested;
      nameInput.dataset.suggested = suggested;
      if (hint) hint.textContent = suggested
        ? 'Suggested from your profile name — edit if different on your tax records.'
        : 'Enter your name exactly as it appears on your HMRC tax records.';
    }
  }

  firstInput.addEventListener('input', syncName);
  lastInput.addEventListener('input', syncName);
})();
</script>
