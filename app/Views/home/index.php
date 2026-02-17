<?php
/**
 * Home page — hero, active lots grid, event banner
 *
 * Variables from controller (available after extract in layout):
 *   $basePath   (global)
 *   $user       — authenticated user or null
 *   $events     — array of public events
 *   $items      — array of active items (up to 6)
 *   $categories — array of all categories
 */
global $basePath, $csrfToken;
?>

<style>
  /* ─── Page-load animations ─── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0);   }
  }
  .fade-up  { animation: fadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both; }
  .delay-1  { animation-delay: 0.08s; }
  .delay-2  { animation-delay: 0.16s; }
  .delay-3  { animation-delay: 0.24s; }
  .delay-4  { animation-delay: 0.32s; }
  .delay-5  { animation-delay: 0.40s; }
  .delay-6  { animation-delay: 0.48s; }

  /* ─── Card hover ─── */
  .item-card {
    transition: transform 0.25s cubic-bezier(0.16,1,0.3,1), box-shadow 0.25s ease;
  }
  .item-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px -12px rgba(0,0,0,.18); }
  .dark .item-card:hover { box-shadow: 0 20px 48px -12px rgba(0,0,0,.55); }

  /* ─── Live dot pulse ─── */
  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(69,162,218,.55); }
    50%       { box-shadow: 0 0 0 5px rgba(69,162,218,0); }
  }
  .live-dot { animation: livePulse 1.8s ease-in-out infinite; }

  /* ─── Countdown tick ─── */
  @keyframes tickPulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .55; }
  }
  .tick { animation: tickPulse 1s ease-in-out infinite; }

  /* ─── Full-bleed (breaks out of max-w-6xl container) ─── */
  .hero-full-bleed {
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    margin-top: -2.5rem;
    width: 100vw;
  }
  .filter-full-bleed {
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    margin-top: 0;
    width: 100vw;
  }

  /* ─── Hero grid ─── */
  .hero-grid {
    background-image: linear-gradient(rgba(255,255,255,.3) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,.3) 1px, transparent 1px);
    background-size: 72px 72px;
  }

  /* ─── Hero grain ─── */
  .hero-grain::after {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.05'/%3E%3C/svg%3E");
    opacity: .35; pointer-events: none;
  }
</style>

<?php
// Hero is outside the main content area — we need to "escape" the layout max-w container.
// The layout wraps content in max-w-6xl, so we use negative margin trick to full-bleed.
// We output the hero BEFORE the layout's <main> container, but since this view is injected
// INTO <main>, we use -mx-6 -mt-10 to break out of the padding and pull up.
?>

<!-- ══════════════════════════════════════ HERO ══ -->
<div class="hero-full-bleed mb-0">
  <section class="relative overflow-hidden bg-slate-900 hero-grain">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[360px] bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.04] hero-grid" aria-hidden="true"></div>

    <div class="relative max-w-6xl mx-auto px-6 py-20 md:py-28">
      <div class="max-w-3xl">
        <div class="fade-up inline-flex items-center gap-2.5 mb-6 px-4 py-1.5 bg-primary/10 border border-primary/20 rounded-full">
          <span class="live-dot w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
          <span class="text-xs font-bold text-primary uppercase tracking-widest">Live Auctions Active</span>
        </div>

        <h1 class="fade-up delay-1 text-4xl md:text-6xl font-black text-white leading-[1.05] tracking-tight mb-5">
          Bid for change.<br/>
          <span class="text-primary">Make it count.</span>
        </h1>

        <p class="fade-up delay-2 text-base md:text-lg text-slate-400 font-medium max-w-xl mb-10 leading-relaxed">
          Every bid supports The Well Foundation's work across Scotland.
          Gift Aid eligible — your bid goes 25% further.
        </p>

        <div class="fade-up delay-3 flex flex-col sm:flex-row items-start sm:items-center gap-3">
          <a href="#auctions" class="inline-flex items-center gap-2 px-7 py-3.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-xl shadow-lg transition-colors">
            Browse Live Lots
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </a>
          <?php if (!$user): ?>
          <a href="<?= e($basePath) ?>/login" class="inline-flex items-center gap-2 px-7 py-3.5 bg-white/5 hover:bg-white/10 text-white font-semibold rounded-xl border border-white/10 transition-colors">Sign In to Bid</a>
          <?php endif; ?>
        </div>

        <div class="fade-up delay-4 mt-12 flex flex-wrap gap-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white/5 border border-white/10 rounded-full">
            <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="text-xs font-semibold text-slate-300">Gift Aid Eligible</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white/5 border border-white/10 rounded-full">
            <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <span class="text-xs font-semibold text-slate-300">Secure Stripe Payments</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white/5 border border-white/10 rounded-full">
            <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span class="text-xs font-semibold text-slate-300">Charity No. SC040105</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ══════════════════════════════════════ FILTER BAR ══ -->
<div id="auctions" class="filter-full-bleed mb-8 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700/30 shadow-sm sticky top-20 z-30">
  <div class="max-w-6xl mx-auto px-6 py-3.5 flex flex-wrap gap-3 items-center">

    <!-- Search — GET form targeting auctions list -->
    <form action="<?= e($basePath) ?>/auctions" method="GET" class="relative flex items-center gap-3 flex-wrap">
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input
          type="text"
          name="q"
          placeholder="Search lots&hellip;"
          class="w-full sm:w-56 pl-9 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50"
        />
      </div>

      <?php if (!empty($categories)): ?>
      <select name="category" class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
    </form>

    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium ml-auto hidden lg:block">
      <?= count($items) ?> active lot<?= count($items) !== 1 ? 's' : '' ?>
    </p>
  </div>
</div>

<!-- ══════════════════════════════════════ ACTIVE EVENTS BANNER ══ -->
<?php foreach (array_slice($events, 0, 1) as $evt): ?>
<div class="fade-up mb-8 flex items-center gap-4 px-5 py-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/30 shadow-sm">
  <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
    <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </svg>
  </div>
  <div class="flex-1 min-w-0">
    <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($evt['title']) ?></p>
    <p class="text-xs text-slate-500 dark:text-slate-400">
      <?php if (!empty($evt['venue'])): ?><?= e($evt['venue']) ?> &middot; <?php endif; ?>
      <?php if (!empty($evt['ends_at'])): ?>All lots close <?= e(date('j M Y, g:i A', strtotime($evt['ends_at']))) ?><?php endif; ?>
    </p>
  </div>
  <?php if ($evt['status'] === 'active'): ?>
  <div class="hidden sm:flex items-center gap-2 flex-shrink-0">
    <span class="live-dot w-2 h-2 rounded-full bg-primary"></span>
    <span class="text-xs font-bold text-primary uppercase tracking-widest">Live</span>
  </div>
  <?php endif; ?>
  <a href="<?= e($basePath) ?>/auctions/<?= e($evt['slug']) ?>" class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold text-primary border border-primary/30 rounded-lg hover:bg-primary/5 transition-colors">View Event</a>
</div>
<?php endforeach; ?>

<!-- ══════════════════════════════════════ SECTION HEADING ══ -->
<div class="fade-up delay-1 flex items-end justify-between mb-6">
  <div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Active Lots</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
      <?= $user ? 'Place your bid below.' : 'Sign in to place a bid.' ?>
    </p>
  </div>
  <a href="<?= e($basePath) ?>/auctions" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary-hover transition-colors">
    View all
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
  </a>
</div>

<!-- ══════════════════════════════════════ ITEM GRID ══ -->
<?php if (!empty($items)): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php foreach ($items as $idx => $item): ?>
  <?php
    $delay = min($idx + 1, 6);
    echo atom('item-card', ['item' => $item]);
  ?>
  <?php endforeach; ?>
</div>

<div class="mt-8 text-center">
  <a href="<?= e($basePath) ?>/auctions" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-primary border border-primary/30 rounded-xl hover:bg-primary/5 transition-colors">
    Browse all lots
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
  </a>
</div>

<?php else: ?>
<?= atom('empty-state', [
    'icon'        => '<svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'title'       => 'No active lots right now',
    'description' => 'Check back soon — new lots are added regularly.',
]) ?>
<?php endif; ?>
