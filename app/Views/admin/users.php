<?php
/**
 * Admin Users list view.
 *
 * Variables:
 *   $users       — array of users
 *   $total       — int total
 *   $page        — int
 *   $totalPages  — int
 *   $stats       — [total, bidders, donors, admins, unverified]
 *   $search      — string current search query
 *   $roleFilter  — string current role filter
 *   $user        — authenticated admin
 *   $basePath    — global
 *   $csrfToken   — global
 */
global $basePath, $csrfToken;

$roleBadge = function(string $role): string {
    return match($role) {
        'super_admin' => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Super Admin</span>',
        'admin'       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Admin</span>',
        'donor'       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Donor</span>',
        'bidder'      => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Bidder</span>',
        default       => '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">' . e($role) . '</span>',
    };
};
?>
<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fade-up  { animation: fadeUp 0.45s cubic-bezier(0.16,1,0.3,1) both; }
  .delay-1  { animation-delay: 0.06s; }
  .delay-2  { animation-delay: 0.12s; }
  .stat-card { transition: box-shadow 0.2s ease; }
  .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.07); }
  .dark .stat-card:hover { box-shadow: 0 3px 8px -2px rgba(0,0,0,.20); }
</style>

<!-- Page header -->
<div class="fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Registered Users</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All platform accounts across bidders, donors, and admins.</p>
  </div>
  <form method="GET" action="<?= e($basePath) ?>/admin/users" class="flex items-center gap-3 flex-wrap">
    <div class="relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="Search users&hellip;"
        class="pl-9 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-colors w-48" />
    </div>
    <select name="role" onchange="this.form.submit()"
      class="px-3 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors">
      <option value="">All Roles</option>
      <option value="bidder" <?= ($roleFilter ?? '') === 'bidder' ? 'selected' : '' ?>>Bidders</option>
      <option value="donor"  <?= ($roleFilter ?? '') === 'donor'  ? 'selected' : '' ?>>Donors</option>
      <option value="admin"  <?= ($roleFilter ?? '') === 'admin'  ? 'selected' : '' ?>>Admins</option>
      <option value="super_admin" <?= ($roleFilter ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admins</option>
    </select>
    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg transition-colors">Search</button>
  </form>
</div>

<!-- Summary stats -->
<div class="fade-up delay-1 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-4 text-center">
    <p class="text-2xl font-black text-slate-900 dark:text-white"><?= (int)($stats['total'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Total Users</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-4 text-center">
    <p class="text-2xl font-black text-blue-600 dark:text-blue-400"><?= (int)($stats['bidders'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Bidders</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-4 text-center">
    <p class="text-2xl font-black text-teal-600 dark:text-teal-400"><?= (int)($stats['donors'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Donors</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 px-4 py-4 text-center">
    <p class="text-2xl font-black text-red-600 dark:text-red-400"><?= (int)($stats['admins'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Admins</p>
  </div>
  <div class="stat-card bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-700/40 px-4 py-4 text-center">
    <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= (int)($stats['unverified'] ?? 0) ?></p>
    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1">Unverified</p>
  </div>
</div>

<!-- Users table -->
<div class="fade-up delay-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden mb-8">
  <?php if (empty($users)): ?>
  <div class="py-16 flex flex-col items-center justify-center text-slate-400">
    <svg class="w-12 h-12 mb-3 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No users found</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
      <thead>
        <tr class="border-b border-slate-100 dark:border-slate-700/40">
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">User</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Role</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Verified</th>
          <th class="text-center text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Bids</th>
          <th class="text-left text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 py-3">Joined</th>
          <th class="text-right text-xs font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
        <?php foreach ($users as $u): ?>
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-primary"><?= e(strtoupper(substr((string)($u['name'] ?? 'U'), 0, 1))) ?></span>
              </div>
              <div>
                <p class="font-semibold text-slate-900 dark:text-white text-sm"><?= e($u['name']) ?></p>
                <p class="text-xs text-slate-400"><?= e($u['email']) ?></p>
              </div>
            </div>
          </td>
          <td class="px-4 py-3.5"><?= $roleBadge($u['role'] ?? 'bidder') ?></td>
          <td class="px-4 py-3.5">
            <?php if (!empty($u['email_verified_at'])): ?>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400">
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Verified
            </span>
            <?php else: ?>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Unverified
            </span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3.5 text-center">
            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= (int)($u['bid_count'] ?? 0) ?></span>
          </td>
          <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400">
            <?= e(date('j M Y', strtotime((string)($u['created_at'] ?? 'now')))) ?>
          </td>
          <td class="px-5 py-3.5 text-right">
            <div class="inline-flex items-center gap-2">
              <a href="<?= e($basePath) ?>/admin/users/<?= e($u['slug']) ?>"
                 class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">View</a>
              <?php if (roleLevel($u['role'] ?? '') < 3 && (roleLevel($u['role'] ?? '') < 2 || roleLevel($user['role'] ?? '') >= 3)): ?>
              <button
                type="button"
                popovertarget="del-<?= e($u['slug']) ?>"
                class="px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              >Delete</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php foreach ($users as $u): ?>
  <?php if (roleLevel($u['role'] ?? '') < 3 && (roleLevel($u['role'] ?? '') < 2 || roleLevel($user['role'] ?? '') >= 3)): ?>
  <?php echo atom('popover-shell', [
      'id'     => 'del-' . ($u['slug'] ?? ''),
      'title'  => 'Delete ' . ($u['name'] ?? '') . '?',
      'width'  => '28rem',
      'body'   => '<p class="text-sm text-slate-600 dark:text-slate-300">This will permanently remove <strong>' . e($u['name']) . '</strong> (' . e($u['email']) . ') and all their data — bids, donations, and payment records.</p>'
                . '<p class="text-sm font-semibold text-red-600 dark:text-red-400 mt-3">This action cannot be undone.</p>',
      'footer' => '<button type="button" popovertarget="del-' . e($u['slug']) . '" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>'
                . '<form method="POST" action="' . e($basePath) . '/admin/users/' . e($u['slug']) . '/delete?_csrf_token=' . e($csrfToken) . '" class="inline">'
                . '<button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Yes, delete permanently</button>'
                . '</form>',
  ]); ?>
  <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
  <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
    <p class="text-xs text-slate-400">Page <?= (int)$page ?> of <?= (int)$totalPages ?></p>
    <div class="flex gap-2">
      <?php $q = ['q' => $search, 'role' => $roleFilter]; ?>
      <?php if ($page > 1): ?>
      <a href="?<?= http_build_query(array_merge($q, ['page' => $page - 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_merge($q, ['page' => $page + 1])) ?>" class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-slate-600 dark:text-slate-300">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
