<?php
// $user — authenticated user array or null
// $basePath — base URL path (global)
// $activeNav — 'auctions' | 'my-bids' | 'donate'
?>
<header class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/30 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-6 h-20 flex justify-between items-center">

    <!-- Logo -->
    <a href="<?= e($basePath) ?>/" class="flex items-center gap-3 flex-shrink-0">
      <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-14 w-auto dark:hidden" />
      <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-14 w-auto hidden dark:block" />
    </a>

    <!-- Desktop nav -->
    <nav class="hidden md:flex items-center gap-8">
      <a href="<?= e($basePath) ?>/"
         class="nav-link <?= ($activeNav ?? '') === 'auctions' ? 'active text-primary' : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white' ?> text-sm font-semibold uppercase tracking-widest transition-colors pb-0.5">Auctions</a>
      <a href="<?= $user ? e($basePath) . '/my-bids' : e($basePath) . '/login' ?>"
         class="nav-link <?= ($activeNav ?? '') === 'my-bids' ? 'active text-primary' : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white' ?> text-sm font-semibold uppercase tracking-widest transition-colors pb-0.5">My Bids</a>
      <?php if ($user !== null): ?>
      <a href="<?= e($basePath) ?>/my-donations"
         class="nav-link <?= ($activeNav ?? '') === 'my-donations' ? 'active text-primary' : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white' ?> text-sm font-semibold uppercase tracking-widest transition-colors pb-0.5">My Donations</a>
      <?php endif; ?>
      <a href="<?= e($basePath) ?>/donate"
         class="nav-link <?= ($activeNav ?? '') === 'donate' ? 'active text-primary' : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white' ?> text-sm font-semibold uppercase tracking-widest transition-colors pb-0.5">Donate an Item</a>
    </nav>

    <!-- Right actions -->
    <div class="flex items-center gap-3">
      <?php if ($user): ?>
      <!-- User pill -->
      <a href="<?= e($basePath) ?>/account" class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg hover:border-primary/40 hover:bg-primary/5 transition-colors">
        <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
          <span class="text-xs font-bold text-primary"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
        </div>
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= e($user['name'] ?? '') ?></span>
      </a>
      <a href="<?= e($basePath) ?>/logout" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Sign out</a>
      <?php else: ?>
      <a href="<?= e($basePath) ?>/login" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Sign in</a>
      <a href="<?= e($basePath) ?>/register" class="px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary-hover rounded-lg shadow-sm transition-colors">Register</a>
      <?php endif; ?>

      <!-- Theme toggle -->
      <button onclick="toggleDarkMode(event)" class="p-2 rounded-lg text-slate-400 dark:text-slate-500 hover:text-primary dark:hover:text-primary transition-colors" aria-label="Toggle dark mode">
        <svg id="iconMoon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="iconSun" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>

      <!-- Hamburger (mobile only) -->
      <button onclick="toggleMenu()" class="md:hidden p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
</header>
