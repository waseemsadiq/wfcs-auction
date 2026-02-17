<?php
// Atom: stat-card
// Props: $icon (SVG markup), $color (blue|green|amber|purple|violet), $label, $value, $subtitle
$color    = $color ?? 'blue';
$subtitle = $subtitle ?? '';
$icon     = $icon ?? '';

$iconBg = match($color) {
    'green'            => 'bg-green-100 dark:bg-green-900/30',
    'amber'            => 'bg-amber-100 dark:bg-amber-900/20',
    'purple', 'violet' => 'bg-violet-100 dark:bg-violet-900/20',
    default            => 'bg-primary/10',
};
$iconColor = match($color) {
    'green'            => 'text-green-600 dark:text-green-400',
    'amber'            => 'text-amber-600 dark:text-amber-400',
    'purple', 'violet' => 'text-violet-600 dark:text-violet-400',
    default            => 'text-primary',
};
?>
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 px-6 py-5">
  <div class="w-12 h-12 rounded-xl <?= $iconBg ?> flex items-center justify-center mb-4">
    <?php if ($icon): ?>
    <div class="w-6 h-6 <?= $iconColor ?>"><?= $icon ?></div>
    <?php endif; ?>
  </div>
  <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1"><?= e($label ?? '') ?></p>
  <p class="text-3xl font-black text-slate-900 dark:text-white"><?= e($value ?? '') ?></p>
  <?php if ($subtitle): ?>
  <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5"><?= e($subtitle) ?></p>
  <?php endif; ?>
</div>
