<?php
// Mobile overlay and sidebar — public pages only
// $user — authenticated user array or null
// $basePath — base URL path (global)
// $activeNav — active nav item
?>
<!-- Mobile overlay -->
<div id="mobile-overlay" onclick="toggleMenu()" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden"></div>

<!-- Mobile sidebar -->
<div id="mobile-menu" class="fixed top-0 right-0 h-full w-72 bg-white dark:bg-slate-800 z-50 shadow-2xl translate-x-full border-l border-slate-200 dark:border-slate-700 transition-transform duration-300 ease-in-out">
  <div class="p-6">
    <div class="flex justify-between items-center mb-8">
      <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Menu</span>
      <button onclick="toggleMenu()" class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <nav class="space-y-1">
      <a href="<?= e($basePath) ?>/"
         class="block px-3 py-2.5 text-sm font-semibold rounded-lg <?= ($activeNav ?? '') === 'auctions' ? 'text-primary bg-primary/5' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors">Auctions</a>
      <a href="<?= $user ? e($basePath) . '/my-bids' : e($basePath) . '/login' ?>"
         class="block px-3 py-2.5 text-sm font-semibold rounded-lg <?= ($activeNav ?? '') === 'my-bids' ? 'text-primary bg-primary/5' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors">My Bids</a>
      <?php if ($user !== null): ?>
      <a href="<?= e($basePath) ?>/my-donations"
         class="block px-3 py-2.5 text-sm font-semibold rounded-lg <?= ($activeNav ?? '') === 'my-donations' ? 'text-primary bg-primary/5' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors">My Donations</a>
      <?php endif; ?>
      <a href="<?= e($basePath) ?>/donate"
         class="block px-3 py-2.5 text-sm font-semibold rounded-lg <?= ($activeNav ?? '') === 'donate' ? 'text-primary bg-primary/5' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?> transition-colors">Donate an Item</a>
    </nav>
    <div class="mt-8 space-y-3">
      <?php if ($user): ?>
      <a href="<?= e($basePath) ?>/account" class="flex items-center gap-2 px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-transparent hover:border-primary/40 hover:bg-primary/5 rounded-lg transition-colors">
        <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
          <span class="text-xs font-bold text-primary"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= e($user['name'] ?? '') ?></span>
      </a>
      <a href="<?= e($basePath) ?>/logout" class="w-full flex justify-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Sign out</a>
      <?php else: ?>
      <a href="<?= e($basePath) ?>/login" class="w-full flex justify-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Sign in</a>
      <a href="<?= e($basePath) ?>/register" class="w-full flex justify-center px-4 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-hover transition-colors">Register to Bid</a>
      <?php endif; ?>
    </div>
  </div>
</div>
