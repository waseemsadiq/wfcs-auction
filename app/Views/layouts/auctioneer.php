<?php
// Auctioneer layout — no footer, full-height
// Variables: $pageTitle, $content, $pageScripts
global $basePath, $csrfToken;
$flash = getFlash();
require __DIR__ . '/../partials/head.php';
?>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col select-none transition-colors duration-300">
<?= $content ?>
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
