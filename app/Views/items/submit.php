<?php
/**
 * Donate an Item / submit-item form
 *
 * Variables from controller:
 *   $basePath   (global), $csrfToken (global)
 *   $user       — authenticated user (required)
 *   $categories — all categories for dropdown
 *   $events     — public events for dropdown
 *   $errors     — validation error messages
 *   $old        — old POST data for re-fill
 */
global $basePath, $csrfToken;

$errors = $errors ?? [];
$old    = $old ?? [];
?>

<style>
  /* ─── View transition ─── */
  ::view-transition-old(root),
  ::view-transition-new(root) { animation: none; mix-blend-mode: normal; }
  ::view-transition-old(root) { z-index: 1; }
  ::view-transition-new(root) { z-index: 9999; }

  /* ─── Fade-up ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1 { animation-delay: 0.06s; }
  .delay-2 { animation-delay: 0.12s; }
  .delay-3 { animation-delay: 0.18s; }
  .delay-4 { animation-delay: 0.24s; }
  .delay-5 { animation-delay: 0.30s; }

  /* ─── Hero grain ─── */
  .hero-grain::after {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.05'/%3E%3C/svg%3E");
    opacity: .45; pointer-events: none;
  }

  /* ─── Form section card ─── */
  .form-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.75rem;
  }
  .dark .form-section {
    background: #1e293b;
    border-color: rgba(51,65,85,.6);
  }
  .section-title {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 1.25rem;
  }

  /* ─── Upload area ─── */
  .upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 1rem;
    transition: border-color .2s, background .2s;
    cursor: pointer;
  }
  .dark .upload-area { border-color: #475569; }
  .upload-area:hover, .upload-area.drag-over {
    border-color: #45a2da;
    background: rgba(69,162,218,.04);
  }

  /* ─── Field focus ─── */
  .field { transition: border-color .15s, box-shadow .15s; }
  .field:focus {
    outline: none;
    border-color: #45a2da;
    box-shadow: 0 0 0 3px rgba(69,162,218,.18);
  }

  /* ─── Number input: remove spinners ─── */
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  input[type="number"] { -moz-appearance: textfield; }
</style>

<!-- Hero banner (full-bleed) -->
<div class="-mx-6 -mt-10 mb-10">
  <section class="relative overflow-hidden bg-slate-900 hero-grain">
    <div class="absolute top-0 left-1/3 w-[600px] h-[300px] bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
    <div class="relative max-w-6xl mx-auto px-6 py-14">
      <div class="flex flex-col md:flex-row md:items-center gap-6 md:gap-10">
        <div class="flex-1">
          <div class="inline-flex items-center gap-2 mb-4 px-3.5 py-1.5 bg-primary/10 border border-primary/20 rounded-full">
            <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="text-xs font-bold text-primary uppercase tracking-widest">Donor Submission</span>
          </div>
          <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight leading-tight mb-3">
            Offer an Item for Auction
          </h1>
          <p class="text-slate-400 text-base leading-relaxed max-w-xl">
            Got something you'd like to donate to the auction? Tell us about it and our team will be in touch. We handle all the listing, pricing, and logistics.
          </p>
        </div>
        <div class="flex md:flex-col gap-3">
          <div class="flex items-center gap-2 px-3.5 py-2.5 bg-white/5 border border-white/10 rounded-xl">
            <svg class="w-4 h-4 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span class="text-xs font-semibold text-slate-300 whitespace-nowrap">Admin reviewed</span>
          </div>
          <div class="flex items-center gap-2 px-3.5 py-2.5 bg-white/5 border border-white/10 rounded-xl">
            <svg class="w-4 h-4 text-primary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="text-xs font-semibold text-slate-300 whitespace-nowrap">Gift Aid eligible</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Form -->
<div class="max-w-2xl mx-auto space-y-5">

  <!-- General error -->
  <?php if (!empty($errors['general'])): ?>
  <?= atom('alert', ['type' => 'error', 'message' => $errors['general']]) ?>
  <?php endif; ?>

  <form
    method="POST"
    action="<?= e($basePath) ?>/donate"
    enctype="multipart/form-data"
    novalidate
    class="space-y-5"
  >
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>" />

    <!-- ── SECTION 1: ITEM PHOTO ── -->
    <div class="form-section fade-up">
      <p class="section-title">Item photo</p>

      <?php if (!empty($errors['photo'])): ?>
      <p class="text-xs text-red-600 dark:text-red-400 mb-3"><?= e($errors['photo']) ?></p>
      <?php endif; ?>

      <div class="relative w-full">
        <div class="upload-area w-full h-[220px] overflow-hidden flex items-center justify-center relative" id="upload-zone">
          <img
            src=""
            alt="Item preview"
            class="w-full h-full object-cover rounded-[calc(1rem-2px)] hidden"
            id="photo-preview"
          />
          <!-- Empty state (shown when no image) -->
          <div id="upload-placeholder" class="flex flex-col items-center justify-center text-slate-400 gap-3 p-6">
            <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <div class="text-center">
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Click or drag to upload photo</p>
              <p class="text-xs text-slate-400 mt-0.5">JPG, PNG or WEBP up to 5 MB</p>
            </div>
          </div>
          <!-- Hover overlay (shown when image present) -->
          <div class="absolute inset-0 bg-slate-900/50 opacity-0 hover:opacity-100 transition-opacity hidden flex-col items-center justify-center rounded-[calc(1rem-2px)] cursor-pointer" id="upload-hover-overlay">
            <svg class="w-7 h-7 text-white mb-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span class="text-xs font-semibold text-white">Change photo</span>
          </div>
          <input type="file" name="photo" id="photo-input" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewPhoto(this)" />
        </div>
        <!-- Remove button (hidden until image selected) -->
        <button
          type="button"
          id="remove-photo-btn"
          onclick="removePhoto()"
          class="hidden absolute -top-2.5 -right-2.5 w-7 h-7 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-full shadow-md flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors"
          aria-label="Remove photo"
        >
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <p class="text-xs text-slate-400 dark:text-slate-500 mt-4 flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        JPG, PNG or WEBP. Max 5 MB. High-resolution photos attract more bids.
      </p>
    </div>

    <!-- ── SECTION 2: ITEM DETAILS ── -->
    <div class="form-section fade-up delay-1">
      <p class="section-title">Item details</p>
      <div class="space-y-4">

        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Item title <span class="text-red-500">*</span></label>
          <input
            type="text"
            id="title"
            name="title"
            maxlength="120"
            value="<?= e($old['title'] ?? '') ?>"
            placeholder="e.g. 1962 Rolex Submariner"
            class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border <?= !empty($errors['title']) ? 'border-red-400' : 'border-slate-200 dark:border-slate-600' ?> rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
          />
          <p class="text-xs text-slate-400 mt-1.5 flex items-center justify-between">
            <?php if (!empty($errors['title'])): ?>
            <span class="text-red-500"><?= e($errors['title']) ?></span>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <span><span id="title-count"><?= strlen($old['title'] ?? '') ?></span>/120</span>
          </p>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Detailed description</label>
          <textarea
            id="description"
            name="description"
            rows="5"
            placeholder="Describe your item — condition, provenance, what's included, etc."
            class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 resize-none leading-relaxed"
          ><?= e($old['description'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <!-- ── SECTION 3: CATEGORY + EVENT ── -->
    <div class="form-section fade-up delay-2">
      <p class="section-title">Auction details</p>
      <div class="space-y-4">

        <!-- Category -->
        <div>
          <label for="category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Category <span class="text-red-500">*</span></label>
          <select
            id="category_id"
            name="category_id"
            class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border <?= !empty($errors['category_id']) ? 'border-red-400' : 'border-slate-200 dark:border-slate-600' ?> rounded-xl text-slate-900 dark:text-white"
          >
            <option value="">Select a category&hellip;</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['id']) ?>" <?= (int)($old['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['category_id'])): ?>
          <p class="text-xs text-red-500 mt-1"><?= e($errors['category_id']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Event -->
        <div>
          <label for="event_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Auction event <span class="text-red-500">*</span></label>
          <select
            id="event_id"
            name="event_id"
            class="field w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border <?= !empty($errors['event_id']) ? 'border-red-400' : 'border-slate-200 dark:border-slate-600' ?> rounded-xl text-slate-900 dark:text-white"
          >
            <option value="">Select an event&hellip;</option>
            <?php foreach ($events as $evt): ?>
            <option value="<?= e($evt['id']) ?>" <?= (int)($old['event_id'] ?? 0) === (int)$evt['id'] ? 'selected' : '' ?>><?= e($evt['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['event_id'])): ?>
          <p class="text-xs text-red-500 mt-1"><?= e($errors['event_id']) ?></p>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- ── SECTION 4: PRICING (optional) ── -->
    <div class="form-section fade-up delay-3">
      <p class="section-title">Pricing hints <span class="font-normal normal-case">(optional — our team will confirm)</span></p>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <!-- Starting bid -->
        <div>
          <label for="starting_bid" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Starting bid</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
            <input
              type="number"
              id="starting_bid"
              name="starting_bid"
              value="<?= e($old['starting_bid'] ?? '') ?>"
              min="0"
              step="0.01"
              placeholder="0.00"
              class="field w-full pl-8 pr-3 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400"
            />
          </div>
        </div>

        <!-- Min increment -->
        <div>
          <label for="min_increment" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Min increment</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
            <input
              type="number"
              id="min_increment"
              name="min_increment"
              value="<?= e($old['min_increment'] ?? '') ?>"
              min="0"
              step="0.01"
              placeholder="1.00"
              class="field w-full pl-8 pr-3 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400"
            />
          </div>
        </div>

        <!-- Buy now price -->
        <div>
          <label for="buy_now_price" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Buy now price</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
            <input
              type="number"
              id="buy_now_price"
              name="buy_now_price"
              value="<?= e($old['buy_now_price'] ?? '') ?>"
              min="0"
              step="0.01"
              placeholder="Optional"
              class="field w-full pl-8 pr-3 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400"
            />
          </div>
        </div>

      </div>

      <!-- Market value (Gift Aid) -->
      <div class="mt-4">
        <label for="market_value" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
          Market value
          <span class="ml-1.5 font-normal text-slate-400">(optional)</span>
        </label>
        <div class="flex gap-3 items-start">
          <div class="relative w-48 flex-shrink-0">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">£</span>
            <input
              type="number"
              id="market_value"
              name="market_value"
              value="<?= e($old['market_value'] ?? '') ?>"
              min="0"
              step="0.01"
              placeholder="0.00"
              class="field w-full pl-8 pr-3 py-3 text-sm bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400"
            />
          </div>
          <div class="flex items-start gap-2 pt-3">
            <svg class="w-4 h-4 text-primary flex-shrink-0 mt-px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Used to calculate Gift Aid. The charity can reclaim 25p tax for every £1 bid above this value.
              Leave blank if unknown — our team will assess it.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── SUBMIT ── -->
    <div class="fade-up delay-4 space-y-3">
      <button
        type="submit"
        class="w-full flex items-center justify-center gap-2.5 px-6 py-4 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-lg transition-colors"
      >
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Pledge this item
      </button>

      <!-- Thank-you note -->
      <div class="flex items-start gap-2.5 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700/40 rounded-xl">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <p class="text-xs text-green-700 dark:text-green-300 leading-relaxed">
          Thank you so much for your generosity. A member of our team will be in touch shortly to discuss your pledge and arrange collection of your donated item.
        </p>
      </div>
    </div>

  </form>
</div>

<script>
function previewPhoto(input) {
  const preview = document.getElementById('photo-preview');
  const placeholder = document.getElementById('upload-placeholder');
  const hoverOverlay = document.getElementById('upload-hover-overlay');
  const removeBtn = document.getElementById('remove-photo-btn');

  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    preview.src = e.target.result;
    preview.classList.remove('hidden');
    placeholder.classList.add('hidden');
    hoverOverlay.classList.remove('hidden');
    hoverOverlay.classList.add('flex');
    removeBtn.classList.remove('hidden');
    removeBtn.classList.add('flex');
  };
  reader.readAsDataURL(input.files[0]);
}

function removePhoto() {
  const preview = document.getElementById('photo-preview');
  const placeholder = document.getElementById('upload-placeholder');
  const hoverOverlay = document.getElementById('upload-hover-overlay');
  const removeBtn = document.getElementById('remove-photo-btn');
  const input = document.getElementById('photo-input');

  preview.src = '';
  preview.classList.add('hidden');
  placeholder.classList.remove('hidden');
  hoverOverlay.classList.add('hidden');
  hoverOverlay.classList.remove('flex');
  removeBtn.classList.add('hidden');
  removeBtn.classList.remove('flex');
  input.value = '';
}

// Title character counter
const titleField = document.getElementById('title');
const titleCount = document.getElementById('title-count');
if (titleField && titleCount) {
  titleField.addEventListener('input', function() {
    titleCount.textContent = titleField.value.length;
  });
}
</script>
