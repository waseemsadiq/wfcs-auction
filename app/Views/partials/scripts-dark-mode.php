// Dark mode — View Transitions API circle wipe
const html = document.documentElement;
(function () {
  const theme = getCookie('theme');
  if (theme === 'dark') html.classList.add('dark');
  else if (!theme && localStorage.getItem('darkMode') === 'true') html.classList.add('dark');
  updateDarkIcon();
})();

function getCookie(name) {
  const v = `; ${document.cookie}`, parts = v.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}
function performThemeToggle() {
  html.classList.toggle('dark');
  const isDark = html.classList.contains('dark');
  localStorage.setItem('darkMode', isDark);
  document.cookie = `theme=${isDark ? 'dark' : 'light'}; path=/; max-age=31536000; SameSite=Strict`;
  updateDarkIcon();
}
function updateDarkIcon() {
  const isDark = html.classList.contains('dark');
  const moon = document.getElementById('iconMoon');
  const sun  = document.getElementById('iconSun');
  if (moon) moon.classList.toggle('hidden', isDark);
  if (sun)  sun.classList.toggle('hidden', !isDark);
}
function toggleDarkMode(event) {
  if (!event || !document.startViewTransition || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    performThemeToggle(); return;
  }
  const x = event.clientX, y = event.clientY;
  const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
  const transition = document.startViewTransition(() => performThemeToggle());
  transition.ready.then(() => {
    html.animate(
      { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`] },
      { duration: 500, easing: 'ease-in-out', pseudoElement: '::view-transition-new(root)' }
    );
  });
}

// Toast
let toastTimer;
const toastColors = {
  success: { border: 'border-l-green-500', bar: 'bg-green-500', icon: 'text-green-500' },
  error:   { border: 'border-l-red-500',   bar: 'bg-red-500',   icon: 'text-red-500'   },
  info:    { border: 'border-l-blue-500',  bar: 'bg-blue-500',  icon: 'text-blue-500'  },
};
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  const body = document.getElementById('toast-body');
  const progress = document.getElementById('toast-progress');
  const icon = document.getElementById('toast-icon');
  const c = toastColors[type] || toastColors.success;
  body.className = `flex items-start gap-3 px-4 py-3.5 bg-white dark:bg-slate-800 border-l-4 ${c.border}`;
  progress.className = `h-0.5 w-full origin-left ${c.bar}`;
  icon.className = `w-5 h-5 flex-shrink-0 mt-0.5 ${c.icon}`;
  document.getElementById('toast-message').textContent = msg;
  progress.style.animation = 'none';
  t.classList.add('show');
  clearTimeout(toastTimer);
  requestAnimationFrame(() => { progress.classList.add('running'); progress.style.animation = ''; });
  toastTimer = setTimeout(hideToast, 5000);
}
function hideToast() {
  document.getElementById('toast').classList.remove('show');
  document.getElementById('toast-progress').classList.remove('running');
}
