<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Repositories\ItemRepository;

class DonorController extends Controller
{
    private ItemRepository $items;

    public function __construct()
    {
        $this->items = new ItemRepository();
    }

    public function myDonations(): void
    {
        $user = getAuthUser();

        if ($user === null) {
            global $basePath;
            $this->redirect($basePath . '/login');
            return;
        }

        $donations = $this->items->forDonor((int)$user['id']);

        // Compute stats from the returned array — no extra queries
        $totalDonated = count($donations);
        $totalSold    = 0;
        $totalRaised  = 0.0;

        foreach ($donations as $item) {
            if ($item['status'] === 'sold') {
                $totalSold++;
                $totalRaised += (float)($item['current_bid'] ?? 0);
            }
        }

        $content = $this->renderView('donor/my-donations', [
            'user'         => $user,
            'donations'    => $donations,
            'totalDonated' => $totalDonated,
            'totalSold'    => $totalSold,
            'totalRaised'  => $totalRaised,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'My Donations',
            'activeNav' => 'my-donations',
            'user'      => $user,
            'content'   => $content,
        ]);
    }
}
