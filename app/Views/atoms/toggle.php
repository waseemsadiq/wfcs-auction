<?php
// Atom: toggle
// Props: $name, $id, $checked, $label, $color (blue|green)
$id    = $id ?? ($name ?? '');
$color = $color ?? 'blue';
?>
<label class="flex items-center gap-3 cursor-pointer">
  <input
    type="checkbox"
    name="<?= e($name ?? '') ?>"
    id="<?= e($id) ?>"
    class="toggle-input sr-only"
    <?= !empty($checked) ? 'checked' : '' ?>
    value="1"
  />
  <div class="toggle-track relative w-10 h-6 bg-gray-200 dark:bg-slate-600 rounded-full transition-colors">
    <div class="toggle-knob absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
  </div>
  <?php if (!empty($label)): ?>
  <span class="text-sm text-slate-700 dark:text-slate-300"><?= e($label) ?></span>
  <?php endif; ?>
</label>
