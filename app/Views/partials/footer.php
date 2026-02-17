<footer class="border-t border-slate-200 dark:border-slate-700/30 bg-white dark:bg-slate-800">
  <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-start justify-between text-sm text-slate-400 gap-2">
    <div>
      <p class="mb-2">&copy; <?= date('Y') ?> The Well Foundation. Building 2, Unit C, Ground Floor, 4 Parklands Way, Eurocentral, Holytown, ML1 4WR</p>
      <p class="text-xs">Registered office: 211B Main Street, Bellshill, ML4 1AJ, Scotland. Charity Registration No. SC040105</p>
    </div>
    <div class="sm:text-right">
      <p class="flex gap-4 mb-2 sm:justify-end">
        <a href="<?= e($basePath ?? '') ?>/terms" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Terms</a>
        <a href="<?= e($basePath ?? '') ?>/privacy" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Privacy</a>
      </p>
      <p class="text-xs">Made with <svg class="inline-block w-3.5 h-3.5 align-middle text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> by <a href="https://waseemsadiq.com" target="_blank" rel="noopener" class="hover:text-primary transition-colors">Waseem</a></p>
    </div>
  </div>
</footer>
