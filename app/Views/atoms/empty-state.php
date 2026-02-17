<?php
// Atom: empty-state
// Props: $icon (SVG string), $title, $description, $action (optional: HTML string for CTA)
$icon        = $icon ?? '';
$title       = $title ?? 'Nothing here yet';
$description = $description ?? '';
$action      = $action ?? '';
?>
<div class="flex flex-col items-center justify-center py-20 text-center">
  <?php if ($icon): ?>
  <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-4 text-slate-400 dark:text-slate-500">
    <?= $icon ?>
  </div>
  <?php endif; ?>
  <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1"><?= e($title) ?></h3>
  <?php if ($description): ?>
  <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm leading-relaxed"><?= e($description) ?></p>
  <?php endif; ?>
  <?php if ($action): ?>
  <div class="mt-5"><?= $action ?></div>
  <?php endif; ?>
</div>
