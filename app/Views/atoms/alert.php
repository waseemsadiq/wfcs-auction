<?php
// Atom: alert
// Props: $type (info|success|warning|error), $message, $icon (optional SVG string)
$type    = $type ?? 'info';
$message = $message ?? '';

[$bgClass, $borderClass, $textClass, $iconClass] = match($type) {
    'success' => ['bg-green-50 dark:bg-green-900/15', 'border-green-200 dark:border-green-700/30', 'text-green-700 dark:text-green-300', 'text-green-500'],
    'warning' => ['bg-amber-50 dark:bg-amber-900/15', 'border-amber-200 dark:border-amber-700/30', 'text-amber-700 dark:text-amber-300', 'text-amber-500'],
    'error'   => ['bg-red-50 dark:bg-red-900/15', 'border-red-200 dark:border-red-700/30', 'text-red-700 dark:text-red-300', 'text-red-500'],
    default   => ['bg-primary/5 dark:bg-primary/10', 'border-primary/20', 'text-slate-700 dark:text-slate-300', 'text-primary'],
};

$defaultIcon = match($type) {
    'success' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'error'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    default   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
};
$iconHtml = $icon ?? $defaultIcon;
?>
<div class="flex items-start gap-3 p-4 rounded-xl border <?= $bgClass ?> <?= $borderClass ?>">
  <div class="w-5 h-5 flex-shrink-0 mt-0.5 <?= $iconClass ?>">
    <?= $iconHtml ?>
  </div>
  <p class="text-sm <?= $textClass ?> leading-relaxed"><?= e($message) ?></p>
</div>
