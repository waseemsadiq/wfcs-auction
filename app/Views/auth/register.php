<?php
/**
 * Register page content fragment — rendered inside layouts/public.php
 *
 * Variables: $basePath (global), $csrfToken (global)
 */
global $basePath, $csrfToken;
?>

<style>
  /* ─── Fade-up ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1 { animation-delay: 0.05s; }
  .delay-2 { animation-delay: 0.10s; }
  .delay-3 { animation-delay: 0.15s; }
  .delay-4 { animation-delay: 0.20s; }
  .delay-5 { animation-delay: 0.25s; }
  .delay-6 { animation-delay: 0.30s; }
  .delay-7 { animation-delay: 0.35s; }

  /* ─── Field focus ─── */
  .field { transition: border-color .15s, box-shadow .15s; }
  .field:focus {
    outline: none;
    border-color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.18);
  }

  /* ─── Account type pill toggle ─── */
  .acct-pill input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
  .acct-pill label {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.75rem;
    font-size: 0.875rem; font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s, box-shadow .15s;
    user-select: none;
  }
  .dark .acct-pill label { border-color: #475569; color: #94a3b8; }
  .acct-pill input[type="radio"]:checked + label {
    border-color: #45a2da;
    background: rgba(69,162,218,.08);
    color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.14);
  }

  /* ─── Gift Aid collapsible ─── */
  #gift-aid-body {
    overflow: hidden;
    transition: max-height .4s cubic-bezier(.16,1,.3,1), opacity .3s ease;
    max-height: 600px;
    opacity: 1;
  }
  #gift-aid-body.collapsed { max-height: 0; opacity: 0; }

  /* ─── Org fields reveal ─── */
  #org-fields {
    overflow: hidden;
    transition: max-height .4s cubic-bezier(.16,1,.3,1), opacity .3s ease;
    max-height: 0;
    opacity: 0;
  }
  #org-fields.visible { max-height: 500px; opacity: 1; }

  /* ─── Address reveal ─── */
  #address-fields {
    overflow: hidden;
    transition: max-height .35s cubic-bezier(.16,1,.3,1), opacity .25s ease;
    max-height: 0;
    opacity: 0;
  }
  #address-fields.visible { max-height: 300px; opacity: 1; }

  /* ─── Section card ─── */
  .form-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.75rem;
  }
  .dark .form-section { background: #1e293b; border-color: rgba(51,65,85,.6); }
  .section-title {
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: #94a3b8; margin-bottom: 1.25rem;
  }

  /* ─── Toggle switch ─── */
  .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
  .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }
</style>

<!-- Page heading -->
<div class="text-center mb-10 fade-up">
  <div class="inline-flex items-center justify-center w-14 h-14 bg-primary/10 rounded-2xl mb-5">
    <svg class="w-7 h-7 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
  </div>
  <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Create your account</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Join The Well Foundation auction platform and start bidding for a good cause.</p>
</div>

<form method="POST" action="<?= e($basePath) ?>/register" novalidate class="space-y-5">
  <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

  <!-- ── PERSONAL DETAILS ── -->
  <div class="form-section fade-up delay-1">
    <p class="section-title">Personal details</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- First name -->
      <div>
        <label for="first_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">First name</label>
        <input type="text" id="first_name" name="first_name" autocomplete="given-name"
          placeholder="Fatima"
          class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
      </div>

      <!-- Last name -->
      <div>
        <label for="last_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Last name</label>
        <input type="text" id="last_name" name="last_name" autocomplete="family-name"
          placeholder="Al-Hassan"
          class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
      </div>

      <!-- Email -->
      <div class="sm:col-span-2">
        <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email address</label>
        <input type="email" id="email" name="email" autocomplete="email"
          placeholder="you@example.com"
          class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" autocomplete="new-password"
            placeholder="Min. 8 characters"
            class="field w-full px-4 py-3 pr-11 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" aria-label="Show password">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- Confirm password -->
      <div>
        <label for="password_confirm" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirm password</label>
        <div class="relative">
          <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password"
            placeholder="Repeat password"
            class="field w-full px-4 py-3 pr-11 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          <button type="button" onclick="togglePassword('password_confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" aria-label="Show confirm password">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- Phone -->
      <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone number <span class="text-slate-400 font-normal">(optional)</span></label>
        <input type="tel" id="phone" name="phone" autocomplete="tel"
          placeholder="+44 7700 000000"
          class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
      </div>

      <!-- Date of birth -->
      <div>
        <label for="dob" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Date of birth</label>
        <input type="date" id="dob" name="dob" autocomplete="bday"
          class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
      </div>

    </div>

    <!-- Account type -->
    <div class="mt-5">
      <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2.5">Account type</p>
      <div class="flex flex-wrap gap-3">
        <div class="acct-pill relative">
          <input type="radio" id="acct_individual" name="account_type" value="individual" checked />
          <label for="acct_individual">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Individual
          </label>
        </div>
        <div class="acct-pill relative">
          <input type="radio" id="acct_org" name="account_type" value="organisation" />
          <label for="acct_org">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            Organisation
          </label>
        </div>
      </div>
    </div>

    <!-- Org fields — revealed when Organisation is selected -->
    <div id="org-fields" class="space-y-4">
      <div class="mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/50">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Organisation details</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <div>
            <label for="org_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Organisation name</label>
            <input type="text" id="org_name" name="org_name"
              placeholder="e.g. Crescent Community Trust"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          </div>

          <div>
            <label for="org_industry" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Industry / sector</label>
            <select id="org_industry" name="org_industry"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white">
              <option value="" disabled selected>Select sector&hellip;</option>
              <option>Charity / Non-profit</option>
              <option>Education</option>
              <option>Healthcare</option>
              <option>Community &amp; Social Services</option>
              <option>Faith Organisation</option>
              <option>Business &amp; Corporate</option>
              <option>Media &amp; Events</option>
              <option>Other</option>
            </select>
          </div>

          <div>
            <label for="org_contact_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Contact person</label>
            <input type="text" id="org_contact_name" name="org_contact_name"
              placeholder="e.g. Ahmed Malik"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          </div>

          <div>
            <label for="org_contact_phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Contact number <span class="text-slate-400 font-normal">(optional)</span>
            </label>
            <input type="tel" id="org_contact_phone" name="org_contact_phone" autocomplete="tel"
              placeholder="+44 141 000 0000"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          </div>

          <div class="sm:col-span-2">
            <label for="org_website" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Website <span class="text-slate-400 font-normal">(optional)</span>
            </label>
            <input type="url" id="org_website" name="org_website"
              placeholder="https://yourorg.org"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- ── CONSENTS ── -->
  <div class="form-section fade-up delay-2">
    <p class="section-title">Consents &amp; agreements</p>
    <div class="space-y-3.5">

      <!-- Required consents -->
      <div class="space-y-3 py-4 bg-slate-50 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600/40 rounded-xl">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2 px-4">Required</p>

        <label class="flex items-center gap-2.5 cursor-pointer px-4">
          <input type="checkbox" name="terms" required class="toggle-input sr-only" />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
          <span class="text-sm text-slate-600 dark:text-slate-400">I accept the <a href="<?= e($basePath) ?>/terms" class="text-primary font-medium hover:underline">Terms &amp; Conditions</a></span>
        </label>

        <label class="flex items-center gap-2.5 cursor-pointer px-4">
          <input type="checkbox" name="privacy" required class="toggle-input sr-only" />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
          <span class="text-sm text-slate-600 dark:text-slate-400">I accept the <a href="<?= e($basePath) ?>/privacy" class="text-primary font-medium hover:underline">Privacy Policy</a></span>
        </label>

        <label class="flex items-center gap-2.5 cursor-pointer px-4">
          <input type="checkbox" name="data_processing" required class="toggle-input sr-only" />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
          <span class="text-sm text-slate-600 dark:text-slate-400">I consent to data processing for auction purposes</span>
        </label>
      </div>

      <!-- Optional -->
      <label class="flex items-center gap-2.5 cursor-pointer pl-4 ml-4">
        <input type="checkbox" name="marketing" class="toggle-input sr-only" />
        <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
          <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
        </div>
        <span class="text-sm text-slate-600 dark:text-slate-400">Send me updates about upcoming auctions <span class="text-slate-400 dark:text-slate-500">(optional)</span></span>
      </label>

    </div>
  </div>

  <!-- ── GIFT AID ── -->
  <div class="form-section fade-up delay-3">

    <!-- Header row — click to collapse -->
    <button type="button" onclick="toggleGiftAid()" class="w-full flex items-start justify-between gap-4 text-left group" aria-expanded="true" aria-controls="gift-aid-body">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <div>
          <p class="text-sm font-bold text-slate-900 dark:text-white">Boost your impact with Gift Aid</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">For UK taxpayers — costs you nothing extra</p>
        </div>
      </div>
      <svg id="gift-aid-chevron" class="w-5 h-5 text-slate-400 flex-shrink-0 mt-1.5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <!-- Collapsible body -->
    <div id="gift-aid-body">
      <div class="mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/50 space-y-4">

        <!-- Explanation -->
        <div class="flex items-start gap-3 px-4 py-3.5 bg-green-50 dark:bg-green-900/15 border border-green-200 dark:border-green-700/30 rounded-xl">
          <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <p class="text-sm text-green-700 dark:text-green-300 leading-relaxed">
            If you're a UK taxpayer, The Well Foundation can claim <strong>25%</strong> on top of your winning bids at no cost to you. Enable Gift Aid to maximise your charitable impact.
          </p>
        </div>

        <!-- Gift Aid checkbox -->
        <label class="flex items-center gap-2.5 cursor-pointer pl-4 ml-4">
          <input type="checkbox" id="gift_aid" name="gift_aid" onchange="toggleAddressFields(this)" class="toggle-input sr-only" />
          <div class="toggle-track relative w-9 h-5 bg-slate-300 dark:bg-slate-600 rounded-full transition-colors duration-200 flex-shrink-0">
            <span class="toggle-knob absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
          </div>
          <span class="text-sm text-slate-600 dark:text-slate-400">I am a UK taxpayer and want to enable Gift Aid on my winning bids</span>
        </label>

        <!-- Address fields (revealed when checkbox checked) -->
        <div id="address-fields" class="space-y-3">
          <div class="h-px bg-slate-100 dark:bg-slate-700/50"></div>
          <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-widest">UK home address (required for Gift Aid)</p>

          <div>
            <label for="addr1" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Address line 1</label>
            <input type="text" id="addr1" name="addr1" autocomplete="address-line1"
              placeholder="12 Parklands Way"
              class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="city" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">City / Town</label>
              <input type="text" id="city" name="city" autocomplete="address-level2"
                placeholder="Glasgow"
                class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
            </div>
            <div>
              <label for="postcode" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Postcode</label>
              <input type="text" id="postcode" name="postcode" autocomplete="postal-code"
                placeholder="ML1 4WR"
                class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" />
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ── SUBMIT ── -->
  <div class="fade-up delay-4 space-y-3">
    <button type="submit"
      class="w-full flex items-center justify-center gap-2 px-6 py-4 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-lg transition-colors">
      Create my account
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
    </button>

    <p class="text-center text-sm text-slate-500 dark:text-slate-400">
      Already have an account?
      <a href="<?= e($basePath) ?>/login" class="font-semibold text-primary hover:text-primary-hover transition-colors">Sign in</a>
    </p>
  </div>

</form>

<script>
function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  const isText = field.type === 'text';
  field.type = isText ? 'password' : 'text';
  btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
}

// Account type — show/hide org fields
document.querySelectorAll('input[name="account_type"]').forEach(radio => {
  radio.addEventListener('change', () => {
    document.getElementById('org-fields').classList.toggle('visible', radio.value === 'organisation');
  });
});

// Gift Aid collapsible
let giftAidOpen = true;
function toggleGiftAid() {
  giftAidOpen = !giftAidOpen;
  const body = document.getElementById('gift-aid-body');
  const chevron = document.getElementById('gift-aid-chevron');
  body.classList.toggle('collapsed', !giftAidOpen);
  chevron.style.transform = giftAidOpen ? '' : 'rotate(180deg)';
  document.querySelector('[aria-controls="gift-aid-body"]').setAttribute('aria-expanded', giftAidOpen);
}

// Address fields reveal
function toggleAddressFields(checkbox) {
  document.getElementById('address-fields').classList.toggle('visible', checkbox.checked);
}
</script>
