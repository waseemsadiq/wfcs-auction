<?php
// Atom: button
// Props: $variant (primary|secondary|danger|ghost), $label, $type (button|submit|reset), $class, $disabled, $popovertarget, $onclick
$variant = $variant ?? 'primary';
$type    = $type ?? 'button';
$label   = $label ?? '';
$class   = $class ?? '';
$disabled = !empty($disabled) ? 'disabled' : '';

$baseClasses = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1';

$variantClasses = match($variant) {
    'primary'   => 'bg-primary hover:bg-primary-hover text-white shadow-sm focus:ring-primary/50',
    'secondary' => 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 focus:ring-slate-300',
    'danger'    => 'bg-red-500 hover:bg-red-600 text-white shadow-sm focus:ring-red-400',
    'ghost'     => 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 focus:ring-slate-300',
    default     => 'bg-primary hover:bg-primary-hover text-white shadow-sm focus:ring-primary/50',
};

$extra = '';
if (!empty($popovertarget)) {
    $extra .= ' popovertarget="' . e($popovertarget) . '"';
}
if (!empty($onclick)) {
    $extra .= ' onclick="' . e($onclick) . '"';
}
?>
<button type="<?= e($type) ?>" class="<?= $baseClasses ?> <?= $variantClasses ?> <?= e($class) ?>" <?= $disabled ?><?= $extra ?>><?= e($label) ?></button>
