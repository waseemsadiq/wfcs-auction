<?php
/**
 * Standalone Gala Projector — no auctioneer required.
 *
 * Variables:
 *   $event   — event array (id, title, slug, venue, …)
 *   $items   — all items for this event (all statuses)
 *   $basePath — global
 */
global $basePath;

$eventTitle = (string)($event['title'] ?? 'WFCS Auction');
$eventSlug  = (string)($event['slug']  ?? '');

$itemsJson = json_encode(array_map(fn($i) => [
    'id'           => (int)$i['id'],
    'slug'         => (string)$i['slug'],
    'title'        => (string)$i['title'],
    'status'       => (string)$i['status'],
    'lot_number'   => (int)($i['lot_number'] ?? 0),
    'current_bid'  => (float)($i['current_bid'] ?? 0),
    'starting_bid' => (float)($i['starting_bid'] ?? 0),
    'bid_count'    => (int)($i['bid_count'] ?? 0),
    'ends_at'      => (string)($i['ends_at'] ?? ''),
    'image'        => (string)($i['image'] ?? ''),
    'market_value' => (float)($i['market_value'] ?? 0),
    'category'     => (string)($i['category_name'] ?? ''),
], $items), JSON_HEX_TAG | JSON_HEX_AMP);
?>
<script>
/* Default the gala projector to dark; only go light if user explicitly saved light */
(function() {
  var v = '; ' + document.cookie, parts = v.split('; theme=');
  var cookie = parts.length === 2 ? parts.pop().split(';').shift() : '';
  var local  = '';
  try { local = localStorage.getItem('darkMode') || ''; } catch(e) {}
  if (cookie !== 'light' && local !== 'false') {
    document.documentElement.classList.add('dark');
  }
})();
</script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { width: 100vw; height: 100vh; overflow: hidden; transition: background 0.4s, color 0.4s; }

  /* ── Light theme (default / :root) ── */
  :root {
    --gold:            #8a6000;
    --gold-lt:         #b07d0a;
    --gold-dim:        rgba(138,96,0,0.10);
    --gold-ring:       rgba(138,96,0,0.18);
    --gold-label:      rgba(138,96,0,0.65);
    --bg:              #f5f2ea;
    --text:            #1a1611;
    --text-quiet:      rgba(26,22,17,0.35);
    --text-dim:        rgba(26,22,17,0.45);
    --text-muted:      rgba(26,22,17,0.35);
    --text-faint:      rgba(26,22,17,0.3);
    --text-ultra-muted: rgba(26,22,17,0.22);
    --bid-url-text:    rgba(26,22,17,0.4);
    --surface:         rgba(0,0,0,0.02);
    --border:          rgba(0,0,0,0.08);
    --border-g:        rgba(160,100,0,0.22);
    --lb-bg:           rgba(0,0,0,0.03);
    --ticker-bg:       rgba(0,0,0,0.04);
    --topbar-bg:       linear-gradient(180deg, rgba(212,168,67,0.06) 0%, transparent 100%);
    --glow-color:      rgba(212,168,67,0.06);
    --track-stroke:    rgba(0,0,0,0.08);
    --dot-inactive:    rgba(0,0,0,0.12);
    --badge-ended-bg:  rgba(0,0,0,0.04);
    --badge-ended-bdr: rgba(0,0,0,0.12);
    --btn-ctrl:        rgba(26,22,17,0.35);
    --shimmer-0:       #8a6000;
    --shimmer-50:      #c8860a;
    --bid-flash-start: #8a6000;
  }

  /* ── Dark theme ── */
  html.dark {
    --gold:            #d4a843;
    --gold-lt:         #f0c862;
    --gold-dim:        rgba(212,168,67,0.12);
    --gold-ring:       rgba(212,168,67,0.22);
    --gold-label:      rgba(212,168,67,0.6);
    --bg:              #08090f;
    --text:            #f0ede8;
    --text-quiet:      rgba(240,237,232,0.28);
    --text-dim:        rgba(240,237,232,0.35);
    --text-muted:      rgba(240,237,232,0.28);
    --text-faint:      rgba(240,237,232,0.25);
    --text-ultra-muted: rgba(240,237,232,0.22);
    --bid-url-text:    rgba(240,237,232,0.4);
    --surface:         rgba(255,255,255,0.03);
    --border:          rgba(255,255,255,0.07);
    --border-g:        rgba(212,168,67,0.18);
    --lb-bg:           rgba(0,0,0,0.2);
    --ticker-bg:       rgba(0,0,0,0.3);
    --topbar-bg:       linear-gradient(180deg, rgba(212,168,67,0.04) 0%, transparent 100%);
    --glow-color:      rgba(212,168,67,0.05);
    --track-stroke:    rgba(255,255,255,0.06);
    --dot-inactive:    rgba(255,255,255,0.12);
    --badge-ended-bg:  rgba(255,255,255,0.03);
    --badge-ended-bdr: rgba(255,255,255,0.1);
    --btn-ctrl:        rgba(240,237,232,0.28);
    --shimmer-0:       #d4a843;
    --shimmer-50:      #fff8e8;
    --bid-flash-start: #fff8d0;
  }

  html, body { background: var(--bg); color: var(--text); }

  @keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.7); }
    50%       { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
  }
  @keyframes goldPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(212,168,67,0.6); }
    50%       { box-shadow: 0 0 0 10px rgba(212,168,67,0); }
  }
  @keyframes bidFlash {
    0%   { color: var(--bid-flash-start); text-shadow: 0 0 60px rgba(212,168,67,0.9); }
    100% { color: var(--gold); text-shadow: none; }
  }
  @keyframes shimmer {
    0%   { background-position: -200% center; }
    100% { background-position:  200% center; }
  }
  @keyframes marquee {
    0%   { transform: translateX(100vw); }
    100% { transform: translateX(-100%); }
  }

  .live-dot  { animation: livePulse 1.6s ease-in-out infinite; }
  .gold-dot  { animation: goldPulse 2s ease-in-out infinite; }
  .bid-flash { animation: bidFlash 1.2s ease-out forwards; }

  .shimmer-text {
    background: linear-gradient(90deg, var(--shimmer-0) 0%, var(--shimmer-50) 45%, var(--shimmer-50) 50%, var(--shimmer-0) 55%, var(--shimmer-0) 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 3.5s linear infinite;
  }

  .marquee-track { animation: marquee 40s linear infinite; white-space: nowrap; }

  #root {
    display: grid;
    grid-template-rows: auto 1fr auto;
    height: 100vh;
    width: 100vw;
    font-family: 'Outfit', system-ui, sans-serif;
  }

  /* Top bar */
  #topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 2.5rem 1rem;
    border-bottom: 1px solid var(--border);
    background: var(--topbar-bg);
    flex-shrink: 0;
  }
  #topbarLeft { display: flex; align-items: center; gap: 1.1rem; }
  #topbarDivider { width: 1px; height: 32px; background: var(--border); }
  #topbarSubtitle { font-size: 0.55rem; font-weight: 700; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-quiet); margin-bottom: 2px; }
  #topbarTitle { font-size: 1.05rem; font-weight: 800; color: var(--text); }
  #topbarRight { display: flex; align-items: center; gap: 0.85rem; }
  #liveClock { font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--text-quiet); letter-spacing: 0.04em; }
  #liveBadge { display: flex; align-items: center; gap: 7px; padding: 7px 16px; background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,0.22); border-radius: 99px; }
  #liveBadgeLabel { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.2em; text-transform: uppercase; color: #ef4444; }
  .btn-ctrl { padding: 7px; border-radius: 9px; background: transparent; border: none; cursor: pointer; color: var(--btn-ctrl); }
  .btn-ctrl:hover { color: var(--text); }

  /* Stage */
  #stage {
    display: grid;
    grid-template-columns: 1fr 300px;
    min-height: 0;
    overflow: hidden;
  }

  /* Spotlight */
  #spotlight {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem 3rem;
    border-right: 1px solid var(--border);
    position: relative;
    overflow: hidden;
  }
  #spotlight::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 30%, var(--glow-color) 0%, transparent 65%);
    pointer-events: none;
  }

  #itemImage {
    width: 140px;
    height: 105px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--border-g);
    margin-bottom: 1rem;
    flex-shrink: 0;
  }
  #itemImagePlaceholder {
    width: 140px;
    height: 105px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    flex-shrink: 0;
    color: var(--gold-dim);
  }

  #lotLabel {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 0.4rem;
  }

  #statusBadge {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 14px;
    border-radius: 99px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    border: 1px solid rgba(52,211,153,0.3);
    background: rgba(52,211,153,0.07);
    color: #34d399;
    margin-bottom: 0.65rem;
  }
  #statusBadge.ended {
    border-color: var(--badge-ended-bdr);
    background: var(--badge-ended-bg);
    color: var(--text-dim);
  }

  #itemTitle {
    font-size: clamp(1.9rem, 4vw, 3rem);
    font-weight: 900;
    line-height: 1.1;
    text-align: center;
    max-width: 580px;
    margin-bottom: 0.35rem;
  }

  #itemMeta {
    font-size: 0.8rem;
    color: var(--text-dim);
    letter-spacing: 0.05em;
    margin-bottom: 1.25rem;
    text-align: center;
  }

  #bidBox {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    margin-bottom: 1rem;
    background: var(--gold-dim);
    border: 1px solid var(--border-g);
    border-radius: 18px;
    padding: 0.85rem 2.25rem;
  }
  #bidLabel {
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold-label);
  }
  #bidAmount {
    font-size: clamp(3rem, 7vw, 5rem);
    font-weight: 900;
    line-height: 1;
    color: var(--gold);
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
  }
  #bidCount {
    font-size: 0.7rem;
    color: var(--text-muted);
    letter-spacing: 0.08em;
  }

  #countdown {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    margin-bottom: 0.75rem;
  }
  #countdownRing { transform: rotate(-90deg); }
  #countdownTrack { stroke: var(--track-stroke); }
  #countdownArc   { stroke: var(--gold); stroke-linecap: round; transition: stroke-dashoffset 1s linear; }
  #countdownText  {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-dim);
    text-align: center;
  }

  #dots { display: flex; gap: 5px; }
  .dot  { width: 5px; height: 5px; border-radius: 50%; background: var(--dot-inactive); transition: all 0.3s; }
  .dot.active { width: 16px; border-radius: 3px; background: var(--gold); }

  /* Leaderboard */
  #leaderboard {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--lb-bg);
  }
  #lbHeader {
    padding: 0.85rem 1.1rem 0.65rem;
    font-size: 0.55rem;
    font-weight: 800;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--text-faint);
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
  }
  #lbList { overflow-y: auto; flex: 1; scrollbar-width: none; }
  #lbList::-webkit-scrollbar { display: none; }

  .lb-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 1.1rem;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
  }
  .lb-item.lb-active { background: var(--gold-dim); border-left: 2px solid var(--gold); }
  .lb-item.lb-sold   { opacity: 0.35; }
  .lb-dot  { width: 6px; height: 6px; border-radius: 50%; background: var(--dot-inactive); flex-shrink: 0; }
  .lb-dot.active { background: #34d399; }
  .lb-dot.sold   { background: var(--dot-inactive); }
  .lb-lot  { font-size: 0.58rem; font-weight: 700; color: var(--text-faint); width: 20px; flex-shrink: 0; text-align: right; }
  .lb-name { font-size: 0.75rem; font-weight: 600; color: var(--text); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .lb-bid  { font-size: 0.75rem; font-weight: 800; color: var(--gold); flex-shrink: 0; font-variant-numeric: tabular-nums; }
  .lb-bid.none { color: var(--text-ultra-muted); font-weight: 500; }

  #bidUrl { padding: 0.65rem 1.1rem 0.85rem; border-top: 1px solid var(--border); flex-shrink: 0; }
  #bidUrlLabel { font-size: 0.52rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold-label); margin-bottom: 3px; }
  #bidUrlText  { font-size: 0.65rem; font-weight: 600; color: var(--bid-url-text); word-break: break-all; }

  /* Ticker */
  #ticker {
    height: 32px;
    display: flex;
    align-items: center;
    overflow: hidden;
    border-top: 1px solid var(--border);
    background: var(--ticker-bg);
    flex-shrink: 0;
  }
  .marquee-track {
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-ultra-muted);
  }

  /* Logo adapts to theme */
  #topbarLogo { height: 42px; width: auto; opacity: 0.8; }
  html:not(.dark) #topbarLogo { filter: invert(1) sepia(1) saturate(0) brightness(0.3); }

  .hidden { display: none !important; }
</style>

<div id="root">

  <!-- TOP BAR -->
  <div id="topbar">
    <div id="topbarLeft">
      <img id="topbarLogo" src="<?= e($basePath) ?>/images/logo-white.svg" alt="The Well Foundation" />
      <div id="topbarDivider"></div>
      <div>
        <p id="topbarSubtitle">Charity Auction</p>
        <p id="topbarTitle"><?= e($eventTitle) ?></p>
      </div>
    </div>

    <div id="topbarRight">
      <span id="liveClock"></span>
      <div id="liveBadge">
        <span class="live-dot" style="width:7px;height:7px;border-radius:50%;background:#ef4444;display:inline-block;flex-shrink:0;"></span>
        <span id="liveBadgeLabel">Live</span>
      </div>
      <!-- Theme toggle -->
      <button class="btn-ctrl" onclick="toggleDarkMode(event)" title="Toggle theme (T)">
        <!-- Sun shown in dark mode; updateDarkIcon() manages visibility via 'hidden' class -->
        <svg id="iconSun" style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <!-- Moon shown in light mode -->
        <svg id="iconMoon" style="width:18px;height:18px;" class="hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <!-- Fullscreen -->
      <button class="btn-ctrl" onclick="toggleFullscreen()" title="Fullscreen (F)">
        <svg id="fsExpand" style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
        <svg id="fsCompress" style="width:18px;height:18px;display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M21 8h-3a2 2 0 0 1-2-2V3"/><path d="M3 16h3a2 2 0 0 1 2 2v3"/><path d="M16 21v-3a2 2 0 0 1 2-2h3"/></svg>
      </button>
    </div>
  </div>

  <!-- STAGE -->
  <div id="stage">

    <!-- SPOTLIGHT -->
    <div id="spotlight">
      <img id="itemImage" src="" alt="" class="hidden" />
      <div id="itemImagePlaceholder">
        <svg style="width:32px;height:32px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </div>

      <p id="lotLabel">—</p>

      <div id="statusBadge">
        <span class="gold-dot" style="width:6px;height:6px;border-radius:50%;background:#34d399;display:inline-block;flex-shrink:0;"></span>
        <span id="statusText">Bidding Open</span>
      </div>

      <h1 id="itemTitle" class="shimmer-text">Loading…</h1>
      <p id="itemMeta"></p>

      <div id="bidBox">
        <p id="bidLabel">Current Highest Bid</p>
        <p id="bidAmount">£0</p>
        <p id="bidCount"></p>
      </div>

      <div id="countdown">
        <svg id="countdownRing" width="72" height="72" viewBox="0 0 120 120">
          <circle id="countdownTrack" cx="60" cy="60" r="54" fill="none" stroke-width="5" />
          <circle id="countdownArc"   cx="60" cy="60" r="54" fill="none" stroke-width="5"
                  stroke-dasharray="339.29" stroke-dashoffset="339.29" />
        </svg>
        <p id="countdownText"></p>
      </div>

      <div id="dots"></div>
    </div>

    <!-- LEADERBOARD -->
    <div id="leaderboard">
      <div id="lbHeader">All Lots</div>
      <div id="lbList"></div>
      <div id="bidUrl">
        <p id="bidUrlLabel">Bid now from your phone</p>
        <p id="bidUrlText"></p>
      </div>
    </div>

  </div>

  <!-- TICKER -->
  <div id="ticker">
    <div class="marquee-track" id="tickerInner">
      <?= e($eventTitle) ?> &nbsp;·&nbsp; All proceeds support The Well Foundation (Charity No. SC040105) &nbsp;·&nbsp; Bid now from your phone &nbsp;·&nbsp; Thank you for your generosity &nbsp;·&nbsp; <?= e($eventTitle) ?> &nbsp;·&nbsp; All proceeds support The Well Foundation &nbsp;·&nbsp;
    </div>
  </div>

</div>

<script>
var basePath  = <?= json_encode($basePath,  JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var eventSlug = <?= json_encode($eventSlug, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var allItems  = <?= $itemsJson ?>;

var spotlightIdx     = 0;
var rotateTimer      = null;
var countdownTimer   = null;
var lastSpotlightId  = null;
var ROTATE_MS        = 25000;

// ── Clock ──────────────────────────────────────────────────────────────────
function updateClock() {
  var n = new Date();
  var pad = function(x) { return String(x).padStart(2,'0'); };
  document.getElementById('liveClock').textContent = pad(n.getHours()) + ':' + pad(n.getMinutes()) + ':' + pad(n.getSeconds());
}
updateClock();
setInterval(updateClock, 1000);

// ── Fullscreen ─────────────────────────────────────────────────────────────
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    document.exitFullscreen();
  }
}
document.addEventListener('fullscreenchange', function() {
  var fs = !!document.fullscreenElement;
  document.getElementById('fsExpand').style.display   = fs ? 'none' : '';
  document.getElementById('fsCompress').style.display = fs ? '' : 'none';
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'f' || e.key === 'F') toggleFullscreen();
  if (e.key === 't' || e.key === 'T') { if (typeof toggleDarkMode === 'function') toggleDarkMode(e); }
});

// ── Helpers ────────────────────────────────────────────────────────────────
function fmtGBP(n) {
  return '£' + Number(n).toLocaleString('en-GB', { maximumFractionDigits: 0 });
}
function getActiveItems() {
  return allItems.filter(function(i) { return i.status === 'active'; });
}

// ── Countdown ring ─────────────────────────────────────────────────────────
function startCountdown(endsAt) {
  clearInterval(countdownTimer);
  var arc  = document.getElementById('countdownArc');
  var txt  = document.getElementById('countdownText');
  var circ = 339.29;

  if (!endsAt) {
    arc.style.strokeDashoffset = String(circ);
    txt.textContent = '';
    return;
  }

  var endMs  = new Date(endsAt).getTime();
  var WINDOW = 7 * 24 * 3600 * 1000;

  function tick() {
    var diff = endMs - Date.now();
    if (diff <= 0) {
      txt.textContent = 'Bidding Closed';
      arc.style.strokeDashoffset = String(circ);
      clearInterval(countdownTimer);
      return;
    }
    var d = Math.floor(diff / 86400000);
    var h = Math.floor((diff % 86400000) / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    if (d > 0)       txt.textContent = d + 'd ' + h + 'h remaining';
    else if (h > 0)  txt.textContent = h + 'h ' + m + 'm remaining';
    else if (m > 0)  txt.textContent = m + 'm ' + s + 's remaining';
    else             txt.textContent = s + 's remaining';
    var frac = Math.min(1, Math.max(0, diff / WINDOW));
    arc.style.strokeDashoffset = String(circ * (1 - frac));
  }
  tick();
  countdownTimer = setInterval(tick, 1000);
}

// ── Dots ───────────────────────────────────────────────────────────────────
function renderDots(total, current) {
  var container = document.getElementById('dots');
  var nodes = [];
  for (var i = 0; i < total; i++) {
    var d = document.createElement('div');
    d.className = 'dot' + (i === current ? ' active' : '');
    nodes.push(d);
  }
  container.replaceChildren.apply(container, nodes);
}

// ── Leaderboard ────────────────────────────────────────────────────────────
function renderLeaderboard(spotlightId) {
  var sorted = allItems.slice().sort(function(a, b) {
    return (a.lot_number || 999) - (b.lot_number || 999);
  });

  var nodes = sorted.map(function(item) {
    var row = document.createElement('div');
    row.className = 'lb-item' +
      (item.id === spotlightId ? ' lb-active' : '') +
      (item.status === 'sold'  ? ' lb-sold'   : '');
    row.id = 'lb-' + item.id;

    var dot = document.createElement('span');
    dot.className = 'lb-dot ' + (item.status === 'active' ? 'active' : item.status === 'sold' ? 'sold' : '');

    var lot = document.createElement('span');
    lot.className = 'lb-lot';
    lot.textContent = item.lot_number ? String(item.lot_number) : '–';

    var name = document.createElement('span');
    name.className = 'lb-name';
    name.title = item.title;
    name.textContent = item.title;

    var bid = document.createElement('span');
    bid.className = 'lb-bid' + (item.bid_count > 0 ? '' : ' none');
    bid.id = 'lb-bid-' + item.id;
    bid.textContent = item.bid_count > 0 ? fmtGBP(item.current_bid) : '–';

    row.appendChild(dot);
    row.appendChild(lot);
    row.appendChild(name);
    row.appendChild(bid);
    return row;
  });

  var list = document.getElementById('lbList');
  list.replaceChildren.apply(list, nodes);
}

// ── Spotlight ──────────────────────────────────────────────────────────────
function renderSpotlight(item, activeItems) {
  var idx = activeItems.findIndex(function(i) { return i.id === item.id; });

  // Lot label
  var lot = item.lot_number ? 'Lot ' + item.lot_number : '';
  var ofStr = activeItems.length > 1 ? (lot ? ' · ' : '') + (idx + 1) + ' of ' + activeItems.length + ' active lots' : 'Featured Lot';
  document.getElementById('lotLabel').textContent = lot + ofStr;

  // Title (shimmer applied via class, using textContent — safe)
  document.getElementById('itemTitle').textContent = item.title;

  // Meta
  var parts = [];
  if (item.category) parts.push(item.category);
  if (item.market_value > 0) parts.push('Estimated value: ' + fmtGBP(item.market_value));
  document.getElementById('itemMeta').textContent = parts.join('  ·  ');

  // Image
  var imgEl = document.getElementById('itemImage');
  var phEl  = document.getElementById('itemImagePlaceholder');
  if (item.image) {
    imgEl.src = basePath + '/uploads/' + item.image;
    imgEl.classList.remove('hidden');
    phEl.style.display = 'none';
  } else {
    imgEl.classList.add('hidden');
    phEl.style.display = '';
  }

  // Bid
  updateBidDisplay(item);

  // Status
  var badge = document.getElementById('statusBadge');
  var stTxt = document.getElementById('statusText');
  if (item.status === 'active') {
    badge.classList.remove('ended');
    stTxt.textContent = 'Bidding Open';
  } else {
    badge.classList.add('ended');
    stTxt.textContent = item.status === 'sold' ? 'Sold' : 'Ended';
  }

  // Countdown
  startCountdown(item.ends_at || null);

  // Dots
  renderDots(activeItems.length, idx);

  // Bid URL
  document.getElementById('bidUrlText').textContent =
    window.location.hostname + basePath + '/items/' + item.slug;

  lastSpotlightId = item.id;
}

function updateBidDisplay(item) {
  var hasBids = item.bid_count > 0;
  document.getElementById('bidLabel').textContent  = hasBids ? 'Current Highest Bid' : 'Opening Bid';
  document.getElementById('bidAmount').textContent = fmtGBP(item.current_bid);
  document.getElementById('bidCount').textContent  = hasBids
    ? item.bid_count + (item.bid_count === 1 ? ' bid placed' : ' bids placed')
    : 'Be the first to bid!';
}

function flashBid() {
  var el = document.getElementById('bidAmount');
  el.classList.remove('bid-flash');
  void el.offsetWidth;
  el.classList.add('bid-flash');
}

// ── Rotation ───────────────────────────────────────────────────────────────
function showSlide(idx) {
  var active = getActiveItems();
  if (active.length === 0) return;
  spotlightIdx = ((idx % active.length) + active.length) % active.length;
  renderSpotlight(active[spotlightIdx], active);
  renderLeaderboard(active[spotlightIdx].id);
}

function scheduleRotation() {
  clearTimeout(rotateTimer);
  if (getActiveItems().length > 1) {
    rotateTimer = setTimeout(function() {
      showSlide(spotlightIdx + 1);
      scheduleRotation();
    }, ROTATE_MS);
  }
}

// ── Polling ────────────────────────────────────────────────────────────────
function poll() {
  fetch(basePath + '/api/event-bids?event=' + encodeURIComponent(eventSlug))
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.items) return;

      data.items.forEach(function(fresh) {
        var existing = allItems.find(function(i) { return i.id === fresh.id; });
        if (!existing) return;

        var bidChanged = fresh.current_bid !== existing.current_bid;
        existing.current_bid = fresh.current_bid;
        existing.bid_count   = fresh.bid_count;
        existing.status      = fresh.status;

        // Leaderboard row
        var lbBid = document.getElementById('lb-bid-' + fresh.id);
        if (lbBid) {
          lbBid.className = 'lb-bid' + (fresh.bid_count > 0 ? '' : ' none');
          lbBid.textContent = fresh.bid_count > 0 ? fmtGBP(fresh.current_bid) : '–';
        }
        var lbRow = document.getElementById('lb-' + fresh.id);
        if (lbRow) {
          lbRow.className = 'lb-item' +
            (fresh.id === lastSpotlightId ? ' lb-active' : '') +
            (fresh.status === 'sold' ? ' lb-sold' : '');
        }

        // Spotlight update
        if (fresh.id === lastSpotlightId && bidChanged) {
          updateBidDisplay(existing);
          flashBid();
        }
      });
    })
    .catch(function() { /* silent */ });
}

// ── Boot ───────────────────────────────────────────────────────────────────
(function init() {
  var active = getActiveItems();
  if (active.length > 0) {
    showSlide(0);
    scheduleRotation();
  } else if (allItems.length > 0) {
    renderSpotlight(allItems[0], [allItems[0]]);
    renderLeaderboard(allItems[0].id);
  }
  setInterval(poll, 5000);
})();
</script>
