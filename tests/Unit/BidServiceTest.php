<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BidServiceTest extends TestCase
{
    private function makeItem(array $overrides = []): array
    {
        return array_merge([
            'id'             => 1,
            'status'         => 'active',
            'current_bid'    => '100.00',
            'min_increment'  => '1.00',
            'starting_bid'   => '50.00',
            'buy_now_price'  => null,
            'donor_id'       => 2,
            'auction_end'    => null,
            'event_ends_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
        ], $overrides);
    }

    private function makeUser(array $overrides = []): array
    {
        return array_merge([
            'id'       => 3,
            'verified' => true,
        ], $overrides);
    }

    private function makeService(): \App\Services\BidService
    {
        $bidRepo  = $this->createMock(\App\Repositories\BidRepository::class);
        $itemRepo = $this->createMock(\App\Repositories\ItemRepository::class);
        return new \App\Services\BidService($bidRepo, $itemRepo);
    }

    public function testBidMustExceedCurrentBid(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        $item    = $this->makeItem(['current_bid' => '100.00']);
        $user    = $this->makeUser();

        // Bid equals current_bid — must exceed, so this should throw
        $service->place($item, $user, 100.00, false, []);
    }

    public function testBidMustMeetMinIncrement(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        // current_bid=100.00, min_increment=1.00 — valid minimum is 101.00
        $item    = $this->makeItem(['current_bid' => '100.00', 'min_increment' => '1.00']);
        $user    = $this->makeUser();

        // 100.50 is less than 100 + 1.00 — should throw
        $service->place($item, $user, 100.50, false, []);
    }

    public function testBidMustMeetStartingBid(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        // No bids yet (current_bid=0), starting_bid=50.00
        $item    = $this->makeItem(['current_bid' => '0.00', 'starting_bid' => '50.00']);
        $user    = $this->makeUser();

        // Bid below starting_bid — should throw
        $service->place($item, $user, 30.00, false, []);
    }

    public function testDonorCannotBidOwnItem(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        // donor_id === bidder id (user id 3)
        $item    = $this->makeItem(['donor_id' => 3]);
        $user    = $this->makeUser(['id' => 3]);

        $service->place($item, $user, 120.00, false, []);
    }

    public function testItemMustBeActiveToAcceptBid(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        $item    = $this->makeItem(['status' => 'ended']);
        $user    = $this->makeUser();

        $service->place($item, $user, 120.00, false, []);
    }

    public function testEmailMustBeVerifiedToBid(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();
        $item    = $this->makeItem();
        $user    = $this->makeUser(['verified' => false]);

        $service->place($item, $user, 120.00, false, []);
    }

    public function testValidBidUpdatesCurrentBid(): void
    {
        $bidRepo  = $this->createMock(\App\Repositories\BidRepository::class);
        $itemRepo = $this->createMock(\App\Repositories\ItemRepository::class);

        $bidRepo->expects($this->once())
            ->method('insert')
            ->willReturn(42);

        $itemRepo->expects($this->once())
            ->method('updateBidStats');

        $service = new \App\Services\BidService($bidRepo, $itemRepo);
        $item    = $this->makeItem(['current_bid' => '100.00']);
        $user    = $this->makeUser();

        $result = $service->place($item, $user, 101.00, false, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('bid_id', $result);
    }

    public function testBuyNowEndsBidding(): void
    {
        $bidRepo  = $this->createMock(\App\Repositories\BidRepository::class);
        $itemRepo = $this->createMock(\App\Repositories\ItemRepository::class);

        $bidRepo->expects($this->once())
            ->method('insert')
            ->willReturn(99);

        $itemRepo->expects($this->once())
            ->method('updateBidStats');

        $itemRepo->expects($this->once())
            ->method('setStatus')
            ->with(1, 'ended');

        $service = new \App\Services\BidService($bidRepo, $itemRepo);
        $item    = $this->makeItem(['current_bid' => '100.00', 'buy_now_price' => '500.00']);
        $user    = $this->makeUser();

        // isBuyNow = true
        $result = $service->place($item, $user, 500.00, true, []);

        $this->assertIsArray($result);
        $this->assertTrue($result['buy_now'] ?? false);
    }
}
