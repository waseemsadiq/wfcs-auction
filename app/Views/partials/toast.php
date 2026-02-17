<div id="toast" class="shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 overflow-hidden">
  <div id="toast-body" class="flex items-start gap-3 px-4 py-3.5 bg-white dark:bg-slate-800 border-l-4 border-l-green-500">
    <svg id="toast-icon" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <p id="toast-message" class="text-sm font-medium text-slate-700 dark:text-slate-200 flex-1 leading-snug"></p>
    <button onclick="hideToast()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors flex-shrink-0">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div id="toast-progress" class="h-0.5 w-full origin-left bg-green-500"></div>
</div>
