<?php
// Atom: event-card
// Props: $event (array with slug, title, status, starts_at, ends_at, item_count)
// $basePath is global
global $basePath;
$event = $event ?? [];
$status = $event['status'] ?? 'draft';

$statusClasses = match($status) {
    'active', 'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'ended', 'closed'     => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    default               => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
};
$statusLabel = match($status) {
    'active'    => 'Live',
    'published' => 'Published',
    'ended'     => 'Ended',
    'closed'    => 'Closed',
    'draft'     => 'Draft',
    default     => ucfirst($status),
};
?>
<article class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 p-5 hover:shadow-md transition-shadow">
  <div class="flex items-start justify-between gap-3 mb-3">
    <h3 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($event['title'] ?? '') ?></h3>
    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0 <?= $statusClasses ?>"><?= e($statusLabel) ?></span>
  </div>
  <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400 mb-4">
    <?php if (!empty($event['starts_at'])): ?>
    <span class="flex items-center gap-1">
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <?= e(date('j M Y', strtotime($event['starts_at']))) ?>
    </span>
    <?php endif; ?>
    <?php if (isset($event['item_count'])): ?>
    <span><?= (int)$event['item_count'] ?> lots</span>
    <?php endif; ?>
  </div>
  <a href="<?= e($basePath) ?>/auctions/<?= e($event['slug'] ?? '') ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
    View lots
    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
  </a>
</article>
