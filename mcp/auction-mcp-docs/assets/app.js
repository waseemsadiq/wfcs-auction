// WFCS Auction MCP Docs — App JS
const API_BASE = '/auction';

// ---- Auth state ----
const AUTH_KEY = 'wfcs_mcp_auth';

function getAuth() {
  try { return JSON.parse(localStorage.getItem(AUTH_KEY) || 'null'); } catch { return null; }
}

function setAuth(data) { localStorage.setItem(AUTH_KEY, JSON.stringify(data)); }
function clearAuth()   { localStorage.removeItem(AUTH_KEY); }

// ---- Login ----
async function doLogin(email, password) {
  const res = await fetch(`${API_BASE}/api/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || `Login failed (${res.status})`);
  }

  const data = await res.json();
  return data.data; // { token, user, expires_in }
}

// ---- Role → redirect ----
function redirectByRole(role) {
  const map = { bidder: 'bidder/index.html', donor: 'donor/index.html', admin: 'admin/index.html' };
  const target = map[role] || 'bidder/index.html';
  window.location.href = target;
}

// ---- Login page handler ----
function initLoginPage() {
  // Already logged in?
  const auth = getAuth();
  if (auth && auth.user) {
    redirectByRole(auth.user.role);
    return;
  }

  const form  = document.getElementById('login-form');
  const error = document.getElementById('login-error');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email    = form.querySelector('[name="email"]').value.trim();
    const password = form.querySelector('[name="password"]').value;
    const btn      = form.querySelector('[type="submit"]');

    btn.disabled    = true;
    btn.textContent = 'Signing in…';
    error.style.display = 'none';

    try {
      const data = await doLogin(email, password);
      setAuth(data);
      redirectByRole(data.user.role);
    } catch (err) {
      error.textContent    = err.message;
      error.style.display  = 'block';
      btn.disabled         = false;
      btn.textContent      = 'Sign in';
    }
  });
}

// ---- Guard — require auth for role pages ----
function requireAuth(expectedRole) {
  const auth = getAuth();
  if (!auth || !auth.user) {
    window.location.href = '../index.html';
    return null;
  }
  // Admin can access all role pages
  if (auth.user.role !== expectedRole && auth.user.role !== 'admin') {
    clearAuth();
    window.location.href = '../index.html';
    return null;
  }
  return auth;
}

// ---- Render user pill in header ----
function renderUserPill(user) {
  const el = document.getElementById('user-pill');
  if (!el || !user) return;
  el.textContent = user.name;
  el.title       = user.email;
}

// ---- Logout ----
function initLogout() {
  document.querySelectorAll('[data-logout]').forEach((btn) => {
    btn.addEventListener('click', () => { clearAuth(); window.location.href = '../index.html'; });
  });
}

// ---- Toast ----
function showToast(msg) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id        = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2200);
}

// ---- Copy prompt to clipboard ----
function copyPrompt(text) {
  navigator.clipboard.writeText(text).then(
    () => showToast('Prompt copied to clipboard'),
    () => showToast('Copy failed — try selecting manually')
  );
}

// ---- Render prompt cards ----
function renderPromptCards(container, prompts) {
  container.replaceChildren();
  prompts.forEach((p) => {
    const card = document.createElement('div');
    card.className = 'prompt-card';
    card.setAttribute('data-category', p.category);
    card.setAttribute('data-difficulty', p.difficulty);

    const textEl = document.createElement('p');
    textEl.className = 'prompt-text';
    textEl.textContent = p.text;

    const meta = document.createElement('div');
    meta.className = 'prompt-meta';

    const cat = document.createElement('span');
    cat.className   = 'category-tag';
    cat.textContent = p.category;

    const diff = document.createElement('span');
    diff.className   = `difficulty ${p.difficulty}`;
    diff.textContent = p.difficulty;

    const hint = document.createElement('span');
    hint.className   = 'copy-hint';
    hint.textContent = 'Click to copy';

    meta.appendChild(cat);
    meta.appendChild(diff);
    card.appendChild(textEl);
    card.appendChild(meta);
    card.appendChild(hint);

    card.addEventListener('click', () => copyPrompt(p.text));
    container.appendChild(card);
  });

  if (prompts.length === 0) {
    const empty = document.createElement('p');
    empty.className   = 'text-muted';
    empty.textContent = 'No prompts match your filter.';
    container.appendChild(empty);
  }
}

// ---- Filter prompts ----
function initPromptFilters(role) {
  const grid       = document.getElementById('prompt-grid');
  const search     = document.getElementById('prompt-search');
  const filterBtns = document.querySelectorAll('.filter-btn');
  if (!grid || !window.PROMPTS) return;

  let activeCategory = 'all';
  let searchQuery    = '';

  function updateGrid() {
    const all = window.PROMPTS[role] || [];
    const filtered = all.filter((p) => {
      const matchCat  = activeCategory === 'all' || p.category === activeCategory;
      const matchText = searchQuery === '' || p.text.toLowerCase().includes(searchQuery.toLowerCase());
      return matchCat && matchText;
    });
    renderPromptCards(grid, filtered);
  }

  filterBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      filterBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      activeCategory = btn.getAttribute('data-category') || 'all';
      updateGrid();
    });
  });

  if (search) {
    search.addEventListener('input', () => {
      searchQuery = search.value;
      updateGrid();
    });
  }

  updateGrid();
}

// ---- Quick start cards (static, no filter) ----
function renderQuickCards(containerId, prompts) {
  const container = document.getElementById(containerId);
  if (!container) return;
  renderPromptCards(container, prompts);
}

// ---- MCP config code blocks ----
function renderMcpConfig(user) {
  const el = document.getElementById('mcp-config');
  if (!el || !user) return;
  const config = {
    mcpServers: {
      'wfcs-auction': {
        command: 'node',
        args: ['/path/to/auction/mcp/packages/server/dist/index.js'],
        env: {
          WFCS_API_URL: window.location.origin + '/auction',
          WFCS_EMAIL:   user.email,
          WFCS_PASSWORD: '(your password)',
        },
      },
    },
  };
  el.textContent = JSON.stringify(config, null, 2);
}
