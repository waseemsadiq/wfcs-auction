<?php
// Atom: textarea
// Props: $label, $name, $value, $placeholder, $required, $help, $error, $id, $rows, $class
$rows        = $rows ?? 4;
$value       = $value ?? '';
$placeholder = $placeholder ?? '';
$id          = $id ?? ($name ?? '');
$class       = $class ?? '';
?>
<div>
  <?php if (!empty($label)): ?>
  <label for="<?= e($id) ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
    <?= e($label) ?><?php if (!empty($required)): ?> <span class="text-red-500">*</span><?php endif; ?>
  </label>
  <?php endif; ?>
  <textarea
    name="<?= e($name ?? '') ?>"
    id="<?= e($id) ?>"
    rows="<?= (int)$rows ?>"
    placeholder="<?= e($placeholder) ?>"
    class="field w-full px-4 py-3 text-sm bg-white dark:bg-slate-700/50 border <?= !empty($error) ? 'border-red-400 dark:border-red-500' : 'border-slate-200 dark:border-slate-600' ?> rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors resize-y <?= e($class) ?>"
    <?= !empty($required) ? 'required' : '' ?>
  ><?= e($value) ?></textarea>
  <?php if (!empty($error)): ?>
  <p class="mt-1.5 text-sm text-red-500"><?= e($error) ?></p>
  <?php elseif (!empty($help)): ?>
  <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500"><?= e($help) ?></p>
  <?php endif; ?>
</div>
