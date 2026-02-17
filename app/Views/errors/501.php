<?php
http_response_code(501);
global $basePath;
$bp = isset($basePath) ? $basePath : '';
?><!DOCTYPE html>
<html lang="en" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>501 Not Implemented — WFCS Auction</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($bp, ENT_QUOTES) ?>/images/favicon.svg" />
  <link rel="stylesheet" href="<?= htmlspecialchars($bp, ENT_QUOTES) ?>/css/output.css" />
  <style>
    ::view-transition-old(root), ::view-transition-new(root) { animation: none; mix-blend-mode: normal; }
    ::view-transition-old(root) { z-index: 1; }
    ::view-transition-new(root) { z-index: 9999; }
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    .dark ::-webkit-scrollbar-thumb { background: #334155; }
  </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col items-center justify-center px-6 transition-colors duration-300">
  <div class="text-center max-w-md">
    <p class="text-8xl font-black text-amber-500 mb-4 tabular-nums">501</p>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Not Implemented</h1>
    <p class="text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">This feature is not yet available. We're working on it and it will be ready soon.</p>
    <a href="<?= htmlspecialchars($bp, ENT_QUOTES) ?>/" class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary-hover text-white font-semibold rounded-xl shadow transition-colors">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      Back to home
    </a>
  </div>
</body>
</html>
