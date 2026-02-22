<?php
// Atom: popover-shell
// Props: $id, $title, $body (HTML), $footer (HTML), $width (default '36rem')
$id     = $id ?? 'popover-' . uniqid();
$width  = $width ?? '36rem';
$title  = $title ?? '';
$body   = $body ?? '';
$footer = $footer ?? '';
?>
<div
  id="<?= e($id) ?>"
  popover="manual"
  style="position:fixed;inset:0;margin:auto;width:min(<?= e($width) ?>,calc(100% - 2rem));max-height:min(90vh,760px);border:none;border-radius:0.75rem;padding:0;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);"
  class="form-popover bg-white dark:bg-slate-800"
>
  <!-- Header -->
  <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-700/40 flex-shrink-0">
    <h2 class="text-base font-bold text-slate-900 dark:text-white"><?= e($title) ?></h2>
    <button
      onclick="document.getElementById('<?= e($id) ?>').hidePopover()"
      class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
      aria-label="Close"
    >
      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <!-- Scrollable body -->
  <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
    <?= $body ?>
  </div>

  <!-- Footer actions -->
  <?php if ($footer): ?>
  <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700/40 flex justify-end gap-3 flex-shrink-0">
    <?= $footer ?>
  </div>
  <?php endif; ?>
</div>
