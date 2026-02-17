<?php
// Auth layout — login, register, forgot-password, reset-password
// Variables: $pageTitle, $content, $pageScripts
// No nav links, no hamburger — just logo + theme toggle
global $basePath, $csrfToken;
$flash = getFlash();
require __DIR__ . '/../partials/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col transition-colors duration-300">

<!-- Auth header: logo + theme toggle only -->
<header class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/30 sticky top-0 z-40">
  <div class="max-w-4xl mx-auto px-6 h-20 flex justify-between items-center">
    <a href="<?= e($basePath) ?>/" class="flex items-center gap-3 flex-shrink-0">
      <img src="<?= e($basePath) ?>/images/logo-blue.svg" alt="The Well Foundation" class="h-14 w-auto dark:hidden" />
      <img src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" class="h-14 w-auto hidden dark:block" />
    </a>
    <button onclick="toggleDarkMode(event)" class="p-2 rounded-lg text-slate-400 dark:text-slate-500 hover:text-primary dark:hover:text-primary transition-colors" aria-label="Toggle dark mode">
      <svg id="iconMoon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg id="iconSun" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
  </div>
</header>

<main class="flex-1 max-w-4xl mx-auto w-full px-6 py-10">
  <?= $content ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/toast.php'; ?>
<script>
<?php include __DIR__ . '/../partials/scripts-dark-mode.php'; ?>
<?= $pageScripts ?? '' ?>
</script>
<?php
if ($flash):
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  showToast(<?= json_encode($flash['msg'], JSON_HEX_TAG | JSON_HEX_AMP) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
});
</script>
<?php endif; ?>
</body>
</html>
