<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserServiceDeleteTest extends TestCase
{
    private function makeService(array $repos = []): \App\Services\UserService
    {
        return new \App\Services\UserService(
            $repos['users']    ?? $this->createMock(\App\Repositories\UserRepository::class),
            $repos['bids']     ?? $this->createMock(\App\Repositories\BidRepository::class),
            $repos['items']    ?? $this->createMock(\App\Repositories\ItemRepository::class),
            $repos['payments'] ?? $this->createMock(\App\Repositories\PaymentRepository::class)
        );
    }

    public function testDeleteBlocksSuperAdminUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Super admin accounts cannot be deleted.');

        $service = $this->makeService();
        $service->deleteUser(
            ['id' => 5, 'role' => 'super_admin', 'email' => 'superadmin@example.com'],
            99
        );
    }

    public function testDeleteAllowsAdminUser(): void
    {
        // Admin deletion is allowed at service level; authorization is the controller's job.
        $users = $this->createMock(\App\Repositories\UserRepository::class);
        $items = $this->createMock(\App\Repositories\ItemRepository::class);

        $items->method('donorItemIdsInActiveAuctions')->willReturn([]);
        $items->method('donorItemIdsNotActive')->willReturn([]);
        $users->expects($this->once())->method('delete');

        $service = $this->makeService(['users' => $users, 'items' => $items]);
        $service->deleteUser(
            ['id' => 10, 'role' => 'admin', 'email' => 'admin@example.com'],
            99
        );
    }

    public function testDeleteAnonymisesDonatedItemsInActiveAuctions(): void
    {
        $items = $this->createMock(\App\Repositories\ItemRepository::class);

        $items->method('donorItemIdsInActiveAuctions')->willReturn([3, 4]);
        $items->method('donorItemIdsNotActive')->willReturn([]);

        $items->expects($this->once())->method('anonymiseDonor');
        $items->expects($this->never())->method('deleteByIds');

        $users = $this->createMock(\App\Repositories\UserRepository::class);

        $service = $this->makeService(['items' => $items, 'users' => $users]);
        $service->deleteUser(
            ['id' => 7, 'role' => 'bidder', 'email' => 'user@example.com'],
            1
        );
    }

    public function testDeleteCallsFullCascadeInOrder(): void
    {
        $callOrder = [];

        $users = $this->createMock(\App\Repositories\UserRepository::class);
        $bids  = $this->createMock(\App\Repositories\BidRepository::class);
        $items = $this->createMock(\App\Repositories\ItemRepository::class);
        $pays  = $this->createMock(\App\Repositories\PaymentRepository::class);

        $items->method('donorItemIdsInActiveAuctions')->willReturn([]);
        $items->method('donorItemIdsNotActive')->willReturn([10, 11]);

        $users->method('deletePasswordResets')->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'password_resets'; });
        $users->method('deleteRateLimits')    ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'rate_limits'; });
        $bids ->method('deleteByUser')        ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'bids_by_user'; });
        $pays ->method('deleteByItems')       ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'payments_by_items'; });
        $bids ->method('deleteByItems')       ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'bids_by_items'; });
        $items->method('deleteByIds')         ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'items_delete'; });
        $pays ->method('deleteByUser')        ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'payments_by_user'; });
        $items->method('clearWinner')         ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'clear_winner'; });
        $users->method('transferEvents')      ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'transfer_events'; });
        $users->method('delete')              ->willReturnCallback(function() use (&$callOrder) { $callOrder[] = 'delete_user'; });

        $service = new \App\Services\UserService($users, $bids, $items, $pays);
        $service->deleteUser(
            ['id' => 7, 'role' => 'bidder', 'email' => 'user@example.com'],
            1
        );

        $this->assertLessThan(
            array_search('delete_user', $callOrder),
            array_search('bids_by_user', $callOrder),
            'bids_by_user must come before delete_user'
        );
        $this->assertLessThan(
            array_search('items_delete', $callOrder),
            array_search('bids_by_items', $callOrder),
            'bids_by_items must come before items_delete'
        );
        $this->assertLessThan(
            array_search('items_delete', $callOrder),
            array_search('payments_by_items', $callOrder),
            'payments_by_items must come before items_delete'
        );
        $this->assertLessThan(
            array_search('delete_user', $callOrder),
            array_search('payments_by_user', $callOrder),
            'payments_by_user must come before delete_user'
        );
        $this->assertContains('delete_user', $callOrder);
    }
}
