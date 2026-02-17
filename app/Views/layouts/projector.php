<?php
// Projector layout — full-screen, no header/footer
// Variables: $pageTitle, $content, $pageScripts
global $basePath, $csrfToken;
require __DIR__ . '/../partials/head.php';
?>
<body class="bg-slate-900 text-white font-sans min-h-screen flex flex-col overflow-hidden transition-colors duration-300">
<?= $content ?>
<script>
<?php include __DIR__ . '/../partials/scripts-dark-mode.php'; ?>
<?= $pageScripts ?? '' ?>
</script>
</body>
</html>
