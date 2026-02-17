<?php
// Atom: item-card
// Props: $item (array with slug, title, image, current_bid, status, ends_at, category_name, bid_count)
// $basePath is global
global $basePath;
$item = $item ?? [];
?>
<article class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 overflow-hidden hover:shadow-md transition-shadow group item-card">
  <a href="<?= e($basePath) ?>/items/<?= e($item['slug'] ?? '') ?>" class="block">
    <div class="relative h-52 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800">
      <?php if (!empty($item['image'])): ?>
      <img
        src="<?= e($basePath) ?>/uploads/<?= e($item['image']) ?>"
        alt="<?= e($item['title'] ?? '') ?>"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <?php else: ?>
      <div class="w-full h-full flex items-center justify-center text-slate-300 dark:text-slate-600">
        <svg class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </div>
      <?php endif; ?>
      <?php if (!empty($item['category_name'])): ?>
      <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-primary text-white backdrop-blur-sm"><?= e($item['category_name']) ?></span>
      <?php endif; ?>
    </div>
    <div class="p-5">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1 truncate"><?= e($item['title'] ?? '') ?></h3>
      <?php if (!empty($item['description'])): ?>
      <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed line-clamp-2"><?= e($item['description']) ?></p>
      <?php endif; ?>
      <div class="flex items-end justify-between mt-3">
        <div>
          <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Current bid</p>
          <p class="text-2xl font-black text-slate-900 dark:text-white"><?= formatCurrency((float)($item['current_bid'] ?? 0)) ?></p>
          <p class="text-xs text-slate-400 mt-0.5"><?= (int)($item['bid_count'] ?? 0) ?> bids</p>
        </div>
        <?php if (!empty($item['ends_at'])): ?>
        <div class="text-right">
          <p class="text-xs text-slate-400 dark:text-slate-500"><?= e($item['ends_at']) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </a>
</article>
