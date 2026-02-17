<?php
// $user — authenticated admin user array
// $basePath — base URL path (global)
// $activeNav — one of: dashboard|auctions|items|users|payments|gift-aid|live-events|settings
$navItems = [
    'dashboard'   => ['label' => 'Dashboard',   'url' => '/admin/dashboard'],
    'auctions'    => ['label' => 'Auctions',     'url' => '/admin/auctions'],
    'items'       => ['label' => 'Items',        'url' => '/admin/items'],
    'users'       => ['label' => 'Users',        'url' => '/admin/users'],
    'payments'    => ['label' => 'Payments',     'url' => '/admin/payments'],
    'gift-aid'    => ['label' => 'Gift Aid',     'url' => '/admin/gift-aid'],
    'live-events' => ['label' => 'Live Events',  'url' => '/admin/live-events'],
    'settings'    => ['label' => 'Settings',     'url' => '/admin/settings'],
];
$currentNav = $activeNav ?? 'dashboard';
?>
<header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700/40 sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between gap-4">

    <!-- Logo + label -->
    <a href="<?= e($basePath) ?>/admin/dashboard" class="flex items-center gap-3 flex-shrink-0">
      <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="WFCS" class="h-9 w-auto dark:hidden" />
      <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="WFCS" class="h-9 w-auto hidden dark:block" />
      <div class="hidden sm:block">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 leading-none">WFCS Auction</p>
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 leading-tight">Admin Panel</p>
      </div>
    </a>

    <!-- Right: user + logout + theme toggle -->
    <div class="flex items-center gap-3">
      <a href="<?= e($basePath) ?>/account/profile" class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg hover:border-primary/40 hover:bg-primary/5 transition-colors">
        <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
          <span class="text-xs font-bold text-primary"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></span>
        </div>
        <span class="hidden sm:block text-sm font-medium text-slate-700 dark:text-slate-300"><?= e($user['name'] ?? 'Admin') ?></span>
      </a>
      <a href="<?= e($basePath) ?>/logout" class="flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="hidden sm:inline">Logout</span>
      </a>
      <button onclick="toggleDarkMode(event)" class="p-2 rounded-lg text-slate-400 hover:text-primary dark:hover:text-primary transition-colors" aria-label="Toggle dark mode">
        <svg id="iconMoon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="iconSun" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
    </div>
  </div>

  <!-- Sub-nav -->
  <div class="bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700/40">
    <div class="max-w-7xl mx-auto px-6">
      <nav class="flex items-center gap-1 overflow-x-auto">
        <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= e($basePath . $item['url']) ?>"
           class="subnav-pill <?= $currentNav === $key ? 'active' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' ?> flex-shrink-0 px-3 py-3 text-xs font-semibold uppercase tracking-wider"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</header>
