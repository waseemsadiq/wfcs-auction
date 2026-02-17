<?php
// Atom: page-header
// Props: $title, $subtitle, $actions (HTML string, optional)
?>
<div class="flex items-start justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-black text-slate-900 dark:text-white"><?= e($title ?? '') ?></h1>
    <?php if (!empty($subtitle)): ?>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5"><?= e($subtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($actions)): ?>
  <div class="flex items-center gap-3 flex-shrink-0">
    <?= $actions ?>
  </div>
  <?php endif; ?>
</div>
