<?php
// Atom: table-wrapper
// Props: $content (HTML string — the full <table> element)
// $headers and $rows can be used for a simple auto-rendered table
$content  = $content ?? '';
$headers  = $headers ?? [];
$rows     = $rows ?? [];
?>
<div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
  <div class="overflow-x-auto">
    <?php if ($content): ?>
    <?= $content ?>
    <?php else: ?>
    <table class="w-full min-w-[820px]">
      <?php if ($headers): ?>
      <thead>
        <tr>
          <?php foreach ($headers as $h): ?>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3 border-b border-slate-100 dark:border-slate-700/40"><?= e($h) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <?php endif; ?>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border-b border-slate-50 dark:border-slate-700/30">
          <?php foreach ($row as $cell): ?>
          <td class="px-5 py-3.5 text-sm text-slate-700 dark:text-slate-300"><?= e($cell) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
