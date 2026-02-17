<!DOCTYPE html>
<html lang="en" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'WFCS Auction') ?> — WFCS Auction</title>

  <link rel="icon" type="image/svg+xml" href="<?= e($basePath ?? '') ?>/images/favicon.svg" />
  <link rel="icon" type="image/x-icon" href="<?= e($basePath ?? '') ?>/images/favicon.ico" />

  <!-- Compiled Tailwind v4 + WFCS design tokens -->
  <link rel="stylesheet" href="<?= e($basePath ?? '') ?>/css/output.css" />

  <style>
    /* ─── View Transition dark mode wipe ─── */
    ::view-transition-old(root),
    ::view-transition-new(root) { animation: none; mix-blend-mode: normal; }
    ::view-transition-old(root) { z-index: 1; }
    ::view-transition-new(root) { z-index: 9999; }

    /* ─── Nav underline ─── */
    .nav-link { position: relative; }
    .nav-link::after {
      content: ''; position: absolute; bottom: -2px; left: 0; right: 0;
      height: 2px; background: #45a2da;
      transform: scaleX(0); transform-origin: left; transition: transform .2s ease;
    }
    .nav-link:hover::after,
    .nav-link.active::after { transform: scaleX(1); }

    /* ─── Toggle switch ─── */
    .toggle-input ~ .toggle-track .toggle-knob { transform: translateX(0); }
    .toggle-input:checked ~ .toggle-track { background-color: #45a2da; }
    .toggle-input:checked ~ .toggle-track .toggle-knob { transform: translateX(16px); }
    .toggle-track { transition: background-color 0.2s ease; }
    .toggle-knob { transition: transform 0.2s cubic-bezier(0.16,1,0.3,1); }

    /* ─── Toast ─── */
    #toast {
      position: fixed; bottom: 1.5rem; right: 1.5rem; width: 22rem; z-index: 9999;
      pointer-events: none;
      transform: translateX(calc(100% + 2rem));
      transition: transform .4s cubic-bezier(.34,1.4,.64,1);
    }
    #toast.show { transform: translateX(0); pointer-events: auto; }
    @keyframes shrink { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    #toast-progress.running { animation: shrink 5s linear forwards; }

    /* ─── Popover ─── */
    .form-popover::backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
    .form-popover:popover-open {
      display: flex; flex-direction: column;
      max-height: min(90vh, 760px);
      border: none; border-radius: 0.75rem;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
      padding: 0; overflow: hidden;
    }

    /* ─── Admin sub-nav ─── */
    .subnav-pill { position: relative; transition: color 0.15s ease; }
    .subnav-pill.active { color: #45a2da; }
    .subnav-pill.active::after {
      content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
      height: 2px; background: #45a2da; border-radius: 9999px;
    }
    header nav { scrollbar-width: none; }
    header nav::-webkit-scrollbar { display: none; }

    /* ─── Scrollbar ─── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    .dark ::-webkit-scrollbar-thumb { background: #334155; }
  </style>
</head>
