<?php
// Layout variables:
// $pageTitle   — browser tab title (string)
// $user        — authenticated admin user array
// $activeNav   — 'dashboard'|'auctions'|'items'|'users'|'payments'|'gift-aid'|'live-events'|'settings'
// $content     — rendered page HTML (string)
// $pageScripts — optional inline JS for this page
global $basePath, $csrfToken;
$flash = getFlash();
require __DIR__ . '/../partials/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col transition-colors duration-300 <?= e($bodyClass ?? '') ?>">
<?php require __DIR__ . '/../partials/header-admin.php'; ?>
<main class="flex-1 max-w-7xl mx-auto w-full px-6 py-8">
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
