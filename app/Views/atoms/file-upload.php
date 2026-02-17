<?php
// Atom: file-upload
// Props: $name, $id, $label, $accept, $required, $help, $error, $maxSize
$id      = $id ?? ($name ?? '');
$accept  = $accept ?? 'image/*';
$maxSize = $maxSize ?? '5MB';
?>
<div>
  <?php if (!empty($label)): ?>
  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
    <?= e($label) ?><?php if (!empty($required)): ?> <span class="text-red-500">*</span><?php endif; ?>
  </label>
  <?php endif; ?>
  <label for="<?= e($id) ?>" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed <?= !empty($error) ? 'border-red-400' : 'border-slate-200 dark:border-slate-600' ?> rounded-xl cursor-pointer bg-white dark:bg-slate-700/30 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
    <div class="flex flex-col items-center justify-center py-4">
      <svg class="w-8 h-8 text-slate-400 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p class="text-sm text-slate-500 dark:text-slate-400"><span class="font-semibold text-primary">Click to upload</span> or drag &amp; drop</p>
      <p class="text-xs text-slate-400 mt-1">Max <?= e($maxSize) ?></p>
    </div>
    <input type="file" name="<?= e($name ?? '') ?>" id="<?= e($id) ?>" class="hidden" accept="<?= e($accept) ?>" <?= !empty($required) ? 'required' : '' ?> />
  </label>
  <?php if (!empty($error)): ?>
  <p class="mt-1.5 text-sm text-red-500"><?= e($error) ?></p>
  <?php elseif (!empty($help)): ?>
  <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500"><?= e($help) ?></p>
  <?php endif; ?>
</div>
