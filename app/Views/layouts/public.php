<?php
// Layout variables:
// $pageTitle   — browser tab title (string)
// $user        — authenticated user array or null
// $activeNav   — 'auctions' | 'my-bids' | 'donate'
// $content     — rendered page HTML (string)
// $pageScripts — optional inline JS for this page
// $mainWidth   — optional: 'max-w-4xl' | 'max-w-xl' (default 'max-w-6xl')
$mainWidth = $mainWidth ?? 'max-w-6xl';
$user ??= null;
global $basePath, $csrfToken;
$flash = getFlash(); // must be called before any output (setcookie needs headers open)
require __DIR__ . '/../partials/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col transition-colors duration-300 <?= e($bodyClass ?? '') ?>">
<?php require __DIR__ . '/../partials/header-public.php'; ?>
<?php require __DIR__ . '/../partials/mobile-menu.php'; ?>
<main class="flex-1 <?= e($mainWidth) ?> mx-auto w-full px-6 py-10">
  <?= $content ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/toast.php'; ?>
<script>
<?php include __DIR__ . '/../partials/scripts-dark-mode.php'; ?>
<?php include __DIR__ . '/../partials/scripts-mobile-menu.php'; ?>
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
<?php if ($user !== null): ?>
<script>
(function() {
  var logoutUrl = <?= json_encode($basePath . '/logout') ?>;
  var delay = 120 * 60 * 1000;
  var timer;
  function reset() { clearTimeout(timer); timer = setTimeout(function() { window.location.href = logoutUrl; }, delay); }
  ['mousemove','keydown','click','scroll','touchstart'].forEach(function(e) { window.addEventListener(e, reset, {passive:true}); });
  reset();
})();
</script>
<?php endif; ?>
</body>
</html>
