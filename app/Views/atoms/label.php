<?php
// Atom: label
// Props: $for, $text, $required
?>
<label for="<?= e($for ?? '') ?>" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
  <?= e($text ?? '') ?><?php if (!empty($required)): ?> <span class="text-red-500">*</span><?php endif; ?>
</label>
