<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuctionServiceTest extends TestCase
{
    private function makeService(array $repoDeps = []): \App\Services\AuctionService
    {
        $itemRepo    = $repoDeps['itemRepo']    ?? $this->createMock(\App\Repositories\ItemRepository::class);
        $bidRepo     = $repoDeps['bidRepo']     ?? $this->createMock(\App\Repositories\BidRepository::class);
        $paymentRepo = $repoDeps['paymentRepo'] ?? $this->createMock(\App\Repositories\PaymentRepository::class);
        $settingsRepo = $repoDeps['settingsRepo'] ?? $this->createMock(\App\Repositories\SettingsRepository::class);
        $paymentSvc  = $repoDeps['paymentSvc']  ?? $this->createMock(\App\Services\PaymentService::class);

        return new \App\Services\AuctionService(
            $itemRepo,
            $bidRepo,
            $paymentRepo,
            $settingsRepo,
            $paymentSvc
        );
    }

    public function testEndedItemGetsWinnerIdentified(): void
    {
        $itemRepo    = $this->createMock(\App\Repositories\ItemRepository::class);
        $bidRepo     = $this->createMock(\App\Repositories\BidRepository::class);
        $paymentRepo = $this->createMock(\App\Repositories\PaymentRepository::class);
        $settingsRepo = $this->createMock(\App\Repositories\SettingsRepository::class);
        $paymentSvc  = $this->createMock(\App\Services\PaymentService::class);

        $endedItem = [
            'id'             => 1,
            'slug'           => 'rolex-submariner',
            'status'         => 'active',
            'auction_end'    => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'event_ends_at'  => null,
            'current_bid'    => '4200.00',
            'reserve_price'  => '3000.00',
        ];

        $winningBid = ['id' => 10, 'user_id' => 5, 'amount' => '4200.00'];

        $itemRepo->method('findEndedActive')->willReturn([$endedItem]);
        $bidRepo->method('findWinningBid')->with(1)->willReturn($winningBid);
        $paymentRepo->expects($this->once())->method('create');
        $itemRepo->expects($this->once())->method('setStatus')->with(1, 'awaiting_payment');

        $settingsRepo->method('get')->with('auto_payment_requests')->willReturn('0');

        $service = new \App\Services\AuctionService(
            $itemRepo,
            $bidRepo,
            $paymentRepo,
            $settingsRepo,
            $paymentSvc
        );

        $service->processEndedItems();
    }

    public function testItemWithNoBidsStaysEnded(): void
    {
        $itemRepo    = $this->createMock(\App\Repositories\ItemRepository::class);
        $bidRepo     = $this->createMock(\App\Repositories\BidRepository::class);
        $paymentRepo = $this->createMock(\App\Repositories\PaymentRepository::class);
        $settingsRepo = $this->createMock(\App\Repositories\SettingsRepository::class);
        $paymentSvc  = $this->createMock(\App\Services\PaymentService::class);

        $endedItem = [
            'id'            => 2,
            'slug'          => 'no-bids-item',
            'status'        => 'active',
            'auction_end'   => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'event_ends_at' => null,
            'current_bid'   => '0.00',
            'reserve_price' => '100.00',
        ];

        $itemRepo->method('findEndedActive')->willReturn([$endedItem]);
        $itemRepo->expects($this->once())->method('setStatus')->with(2, 'ended');
        $bidRepo->method('findWinningBid')->with(2)->willReturn(null);

        // No payment should be created when there are no bids
        $paymentRepo->expects($this->never())->method('create');

        $service = new \App\Services\AuctionService(
            $itemRepo,
            $bidRepo,
            $paymentRepo,
            $settingsRepo,
            $paymentSvc
        );

        $service->processEndedItems();
    }

    public function testAutoPaymentRequestTriggeredWhenSettingOn(): void
    {
        $itemRepo    = $this->createMock(\App\Repositories\ItemRepository::class);
        $bidRepo     = $this->createMock(\App\Repositories\BidRepository::class);
        $paymentRepo = $this->createMock(\App\Repositories\PaymentRepository::class);
        $settingsRepo = $this->createMock(\App\Repositories\SettingsRepository::class);
        $paymentSvc  = $this->createMock(\App\Services\PaymentService::class);

        $endedItem = [
            'id'            => 3,
            'slug'          => 'auto-pay-item',
            'status'        => 'active',
            'auction_end'   => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'event_ends_at' => null,
            'current_bid'   => '200.00',
            'reserve_price' => '100.00',
        ];

        $winningBid = ['id' => 20, 'user_id' => 7, 'amount' => '200.00'];
        $paymentRecord = ['id' => 5, 'item_id' => 3, 'bid_id' => 20, 'winner_id' => 7];

        $itemRepo->method('findEndedActive')->willReturn([$endedItem]);
        $bidRepo->method('findWinningBid')->willReturn($winningBid);
        $paymentRepo->method('create')->willReturn(5);
        $paymentRepo->method('find')->willReturn($paymentRecord);
        $settingsRepo->method('get')->with('auto_payment_requests')->willReturn('1');

        // Auto payment service should be called
        $paymentSvc->expects($this->once())->method('requestPayment');

        $service = new \App\Services\AuctionService(
            $itemRepo,
            $bidRepo,
            $paymentRepo,
            $settingsRepo,
            $paymentSvc
        );

        $service->processEndedItems();
    }

    public function testAutoPaymentSkippedWhenSettingOff(): void
    {
        $itemRepo    = $this->createMock(\App\Repositories\ItemRepository::class);
        $bidRepo     = $this->createMock(\App\Repositories\BidRepository::class);
        $paymentRepo = $this->createMock(\App\Repositories\PaymentRepository::class);
        $settingsRepo = $this->createMock(\App\Repositories\SettingsRepository::class);
        $paymentSvc  = $this->createMock(\App\Services\PaymentService::class);

        $endedItem = [
            'id'            => 4,
            'slug'          => 'manual-pay-item',
            'status'        => 'active',
            'auction_end'   => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'event_ends_at' => null,
            'current_bid'   => '150.00',
            'reserve_price' => '100.00',
        ];

        $winningBid    = ['id' => 30, 'user_id' => 8, 'amount' => '150.00'];
        $paymentRecord = ['id' => 6, 'item_id' => 4, 'bid_id' => 30, 'winner_id' => 8];

        $itemRepo->method('findEndedActive')->willReturn([$endedItem]);
        $bidRepo->method('findWinningBid')->willReturn($winningBid);
        $paymentRepo->method('create')->willReturn(6);
        $paymentRepo->method('find')->willReturn($paymentRecord);
        $settingsRepo->method('get')->with('auto_payment_requests')->willReturn('0');

        // Auto payment service should NOT be called
        $paymentSvc->expects($this->never())->method('requestPayment');

        $service = new \App\Services\AuctionService(
            $itemRepo,
            $bidRepo,
            $paymentRepo,
            $settingsRepo,
            $paymentSvc
        );

        $service->processEndedItems();
    }

    public function testEffectiveEndTimeUsesItemAuctionEndWhenSet(): void
    {
        $service = $this->makeService();

        $item = [
            'auction_end'   => '2026-03-01 18:00:00',
            'event_ends_at' => '2026-03-01 20:00:00',
        ];

        $result = $service->effectiveEndTime($item);

        $this->assertEquals('2026-03-01 18:00:00', $result);
    }

    public function testEffectiveEndTimeUsesEventEndsAtWhenItemAuctionEndNull(): void
    {
        $service = $this->makeService();

        $item = [
            'auction_end'   => null,
            'event_ends_at' => '2026-03-01 20:00:00',
        ];

        $result = $service->effectiveEndTime($item);

        $this->assertEquals('2026-03-01 20:00:00', $result);
    }
}
