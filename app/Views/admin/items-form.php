<?php
/**
 * Admin Edit Item view.
 *
 * Variables:
 *   $item        — existing item array (for edit) or null (unused - always passed from editItem)
 *   $categories  — all categories
 *   $events      — all events
 *   $errors      — validation errors array
 *   $old         — repopulation values
 *   $user        — admin
 *   $basePath    — global
 *   $csrfToken   — global
 */
global $basePath, $csrfToken;
$isEdit = isset($item) && !empty($item['id']);
$formAction = $isEdit
    ? e($basePath) . '/admin/items/' . e($item['slug']) . '/edit'
    : e($basePath) . '/admin/items';

$v = function(string $key, string $default = '') use ($item, $old): string {
    if (isset($old[$key])) return e((string)$old[$key]);
    if (isset($item[$key])) return e((string)$item[$key]);
    return e($default);
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
</style>

<div class="max-w-2xl mx-auto">
  <div class="fade-up mb-6 flex items-center gap-4">
    <a href="<?= e($basePath) ?>/admin/items" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= $isEdit ? 'Edit Item' : 'Add Item' ?></h1>
      <?php if ($isEdit): ?>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5"><?= e($item['title'] ?? '') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="fade-up mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-xl px-4 py-3">
    <ul class="text-sm text-red-700 dark:text-red-400 space-y-1">
      <?php foreach ($errors as $err): ?>
      <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <!-- Basic info -->
    <div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-6 space-y-4">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Item Details</h2>
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="<?= $v('title') ?>" required
          class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
        <textarea name="description" rows="4"
          class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors resize-none"><?= $v('description') ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-red-500">*</span></label>
          <select name="category_id" required
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
            <option value="">Select category…</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= (isset($old['category_id']) ? (int)$old['category_id'] : (int)($item['category_id'] ?? 0)) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Auction Event <span class="text-red-500">*</span></label>
          <select name="event_id" required
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
            <option value="">Select event…</option>
            <?php foreach ($events as $ev): ?>
            <option value="<?= (int)$ev['id'] ?>" <?= (isset($old['event_id']) ? (int)$old['event_id'] : (int)($item['event_id'] ?? 0)) === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Pricing -->
    <div class="fade-up delay-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-6 space-y-4">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Pricing</h2>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Starting Bid (£)</label>
          <input type="number" name="starting_bid" value="<?= $v('starting_bid', '0') ?>" min="0" step="0.01"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Min Increment (£)</label>
          <input type="number" name="min_increment" value="<?= $v('min_increment', '1') ?>" min="0.01" step="0.01"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Buy Now Price (£) <span class="text-xs text-slate-400 font-normal">optional</span></label>
          <input type="number" name="buy_now_price" value="<?= $v('buy_now_price') ?>" min="0" step="0.01"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Market Value (£) <span class="text-xs text-slate-400 font-normal">optional</span></label>
          <input type="number" name="market_value" value="<?= $v('market_value') ?>" min="0" step="0.01"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lot Number <span class="text-xs text-slate-400 font-normal">optional</span></label>
          <input type="number" name="lot_number" value="<?= $v('lot_number') ?>" min="1"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
          <?php
          $currStatus = $old['status'] ?? $item['status'] ?? 'draft';
          ?>
          <select name="status"
            class="w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
            <option value="draft"   <?= $currStatus === 'draft'   ? 'selected' : '' ?>>Draft</option>
            <option value="active"  <?= $currStatus === 'active'  ? 'selected' : '' ?>>Active</option>
            <option value="ended"   <?= $currStatus === 'ended'   ? 'selected' : '' ?>>Ended</option>
            <option value="sold"    <?= $currStatus === 'sold'    ? 'selected' : '' ?>>Sold</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Image -->
    <div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 p-6">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Image</h2>
      <?php if (!empty($item['image'])): ?>
      <div class="mb-4">
        <img src="<?= e('/uploads/' . $item['image']) ?>" alt="Current image" class="w-24 h-24 rounded-xl object-cover border border-slate-200 dark:border-slate-600" />
        <p class="text-xs text-slate-400 mt-1">Current image — upload below to replace.</p>
      </div>
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
        class="w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/15 transition-colors" />
      <p class="text-xs text-slate-400 mt-2">JPEG, PNG, or WebP. Max 5 MB.</p>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-3">
      <a href="<?= e($basePath) ?>/admin/items" class="px-5 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">Cancel</a>
      <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-sm transition-colors">
        <?= $isEdit ? 'Save Changes' : 'Create Item' ?>
      </button>
    </div>
  </form>
</div>
