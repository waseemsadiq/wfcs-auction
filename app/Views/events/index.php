<?php
/**
 * Auctions listing page
 *
 * Variables from controller:
 *   $basePath   (global)
 *   $user       — authenticated user or null
 *   $events     — array of public events
 *   $total      — total count
 *   $page       — current page
 *   $totalPages — total pages
 */
global $basePath;
?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1 { animation-delay: 0.06s; }
  .delay-2 { animation-delay: 0.12s; }
</style>

<!-- Page header -->
<div class="fade-up mb-8">
  <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Auctions</h1>
  <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
    <?= (int)$total ?> auction<?= $total !== 1 ? 's' : '' ?> available
  </p>
</div>

<!-- Auctions grid -->
<?php if (!empty($events)): ?>
<div class="fade-up delay-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($events as $evt): ?>
  <?= atom('event-card', ['event' => $evt]) ?>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-10 flex items-center justify-center gap-2" aria-label="Pagination">
  <?php if ($page > 1): ?>
  <a
    href="<?= e($basePath) ?>/auctions?page=<?= $page - 1 ?>"
    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
  >
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Previous
  </a>
  <?php endif; ?>

  <span class="text-sm text-slate-500 dark:text-slate-400 px-3">
    Page <?= (int)$page ?> of <?= (int)$totalPages ?>
  </span>

  <?php if ($page < $totalPages): ?>
  <a
    href="<?= e($basePath) ?>/auctions?page=<?= $page + 1 ?>"
    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
  >
    Next
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
  </a>
  <?php endif; ?>
</nav>
<?php endif; ?>

<?php else: ?>
<?= atom('empty-state', [
    'icon'        => '<svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'title'       => 'No auctions available',
    'description' => 'There are no active or upcoming auctions right now. Check back soon.',
]) ?>
<?php endif; ?>
