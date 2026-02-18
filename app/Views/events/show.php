<?php
/**
 * Event detail page — title, dates, item grid with category filter
 *
 * Variables from controller:
 *   $basePath   (global)
 *   $user       — authenticated user or null
 *   $event      — event row (id, slug, title, description, status, starts_at, ends_at, venue)
 *   $items      — array of items in this event
 *   $categories — all categories (for filter tabs)
 */
global $basePath;

$status      = $event['status'] ?? 'published';
$activeFilter = trim($_GET['category'] ?? '');
$query        = trim($_GET['q'] ?? '');

// Client-side category filter using slug (no URL-encoding issues with special chars)
$filteredItems = $activeFilter !== ''
    ? array_values(array_filter($items, fn($itm) => ($itm['category_slug'] ?? '') === $activeFilter))
    : array_values($items);

$statusLabel = match($status) {
    'active'    => 'Live',
    'published' => 'Published',
    'ended'     => 'Ended',
    'closed'    => 'Closed',
    default     => ucfirst($status),
};
$statusClasses = match($status) {
    'active', 'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'ended', 'closed'     => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    default               => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
};
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

<!-- Breadcrumb -->
<?= atom('breadcrumb', [
    'items' => [
        ['label' => 'Auctions', 'url' => '/auctions'],
        ['label' => $event['title'] ?? ''],
    ],
]) ?>

<!-- Event header -->
<div class="fade-up mb-8 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm p-6">
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-3 mb-2 flex-wrap">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= e($event['title'] ?? '') ?></h1>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusClasses ?>"><?= e($statusLabel) ?></span>
      </div>

      <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-slate-500 dark:text-slate-400">
        <?php if (!empty($event['venue'])): ?>
        <span class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?= e($event['venue']) ?>
        </span>
        <?php endif; ?>

        <?php if (!empty($event['starts_at'])): ?>
        <span class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Opens <?= e(date('j M Y', strtotime($event['starts_at']))) ?>
        </span>
        <?php endif; ?>

        <?php if (!empty($event['ends_at'])): ?>
        <span class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Closes <?= e(date('j M Y, g:i A', strtotime($event['ends_at']))) ?>
        </span>
        <?php endif; ?>

        <span class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7 7 10 10M7 17 17 7"/></svg>
          <?= count($items) ?> lot<?= count($items) !== 1 ? 's' : '' ?>
        </span>
      </div>

      <?php if (!empty($event['description'])): ?>
      <p class="mt-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($event['description']) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Category filter tabs + search -->
<div class="fade-up delay-1 mb-6">
  <form action="<?= e($basePath) ?>/auctions/<?= e($event['slug']) ?>" method="GET" class="flex flex-wrap gap-3 items-center">
    <!-- Category pills -->
    <div class="flex flex-wrap gap-2">
      <a
        href="<?= e($basePath) ?>/auctions/<?= e($event['slug']) ?>"
        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $activeFilter === '' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary' ?>"
      >
        All (<?= count($items) ?>)
      </a>
      <?php
      // Build category map from $items (all lots, or search-filtered lots).
      // Key by slug (never has special chars) — name kept for display label.
      $catMap = [];
      foreach ($items as $itm) {
          $cs = $itm['category_slug'] ?? '';
          $cn = $itm['category_name'] ?? '';
          if ($cs !== '') {
              if (!isset($catMap[$cs])) {
                  $catMap[$cs] = ['name' => $cn, 'count' => 0];
              }
              $catMap[$cs]['count']++;
          }
      }
      foreach ($catMap as $catSlug => $catInfo):
        $isActive = ($activeFilter === $catSlug);
      ?>
      <a
        href="<?= e($basePath) ?>/auctions/<?= e($event['slug']) ?>?category=<?= e($catSlug) ?>"
        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= $isActive ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary' ?>"
      >
        <?= e($catInfo['name']) ?> (<?= (int)$catInfo['count'] ?>)
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search input -->
    <div class="relative ml-auto">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input
        type="text"
        name="q"
        value="<?= e($query) ?>"
        placeholder="Search lots&hellip;"
        class="w-full sm:w-48 pl-9 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50"
      />
      <?php if ($activeFilter !== ''): ?>
      <input type="hidden" name="category" value="<?= e($activeFilter) ?>" />
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Items grid -->
<div class="fade-up delay-2">
  <?php if (!empty($filteredItems)): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($filteredItems as $itm): ?>
    <?= atom('item-card', ['item' => $itm]) ?>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <?= atom('empty-state', [
      'icon'        => '<svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
      'title'       => 'No lots match your filter',
      'description' => 'Try a different category or clear your search.',
      'action'      => '<a href="' . e($basePath) . '/auctions/' . e($event['slug']) . '" class="text-sm font-semibold text-primary hover:underline">Clear filters</a>',
  ]) ?>
  <?php endif; ?>
</div>
