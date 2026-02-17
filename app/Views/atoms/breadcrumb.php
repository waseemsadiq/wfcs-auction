<?php
// Atom: breadcrumb
// Props: $items — array of ['label' => '...', 'url' => '...'] (last item has no url)
// $basePath is global
global $basePath;
$items = $items ?? [];
?>
<nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-6">
  <?php foreach ($items as $i => $crumb): ?>
    <?php if ($i > 0): ?>
    <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    <?php endif; ?>
    <?php if (!empty($crumb['url'])): ?>
    <a href="<?= e($basePath . $crumb['url']) ?>" class="hover:text-primary transition-colors"><?= e($crumb['label']) ?></a>
    <?php else: ?>
    <span class="text-slate-900 dark:text-white font-medium"><?= e($crumb['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
