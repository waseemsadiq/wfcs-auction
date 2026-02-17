<?php
// Atom: badge
// Props:
//   Status badge: $status (active|leading|paid|pending|sold|ended|outbid|unpaid|admin|bidder|donor)
//   Category badge: $variant='category', $label
//   Custom: $color (primary|green|yellow|red|gray|blue|teal|amber), $label

// Category variant — solid primary
if (($variant ?? '') === 'category') {
    echo '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary text-white backdrop-blur-sm">' . e($label ?? '') . '</span>';
    return;
}

// Status-to-class map
if (!empty($status) && empty($label)) {
    $label = match($status) {
        'active'  => 'Active',
        'leading' => 'Leading',
        'paid'    => 'Paid',
        'pending' => 'Pending',
        'sold'    => 'Sold',
        'ended'   => 'Ended',
        'outbid'  => 'Outbid',
        'unpaid'  => 'Unpaid',
        'admin'   => 'Admin',
        'bidder'  => 'Bidder',
        'donor'   => 'Donor',
        'draft'   => 'Draft',
        'published' => 'Published',
        'closed'  => 'Closed',
        default   => ucfirst($status),
    };
    $color = match($status) {
        'active', 'leading', 'paid'   => 'green',
        'pending', 'draft'            => 'amber',
        'sold', 'ended', 'closed'     => 'gray',
        'outbid', 'unpaid', 'admin'   => 'red',
        'bidder', 'published'         => 'blue',
        'donor'                       => 'teal',
        default                       => 'gray',
    };
}

$label = $label ?? '';
$color = $color ?? 'gray';

// primary gets special treatment
if ($color === 'primary') {
    echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary text-white">' . e($label) . '</span>';
    return;
}

$classes = match($color) {
    'green'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'amber'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'red'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'blue'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'teal'   => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    default  => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
};
?>
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $classes ?>"><?= e($label) ?></span>
