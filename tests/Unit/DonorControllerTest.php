<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DonorController stats computation logic.
 *
 * The controller computes totalDonated, totalSold, and totalRaised
 * directly from the array returned by ItemRepository::forDonor().
 * We test that logic in isolation via a simple helper that mirrors
 * the controller's foreach loop.
 */
class DonorControllerTest extends TestCase
{
    /**
     * Mirrors the stats computation in DonorController::myDonations().
     * Returns [totalDonated, totalSold, totalRaised].
     */
    private function computeStats(array $donations): array
    {
        $totalDonated = count($donations);
        $totalSold    = 0;
        $totalRaised  = 0.0;

        foreach ($donations as $item) {
            if ($item['status'] === 'sold') {
                $totalSold++;
                $totalRaised += (float)($item['current_bid'] ?? 0);
            }
        }

        return [$totalDonated, $totalSold, $totalRaised];
    }

    public function testEmptyDonationsReturnsZeros(): void
    {
        [$donated, $sold, $raised] = $this->computeStats([]);

        $this->assertSame(0, $donated);
        $this->assertSame(0, $sold);
        $this->assertSame(0.0, $raised);
    }

    public function testCountsAllItemsAsDonated(): void
    {
        $donations = [
            ['status' => 'active',  'current_bid' => '50.00'],
            ['status' => 'draft',   'current_bid' => '0.00'],
            ['status' => 'sold',    'current_bid' => '120.00'],
        ];

        [$donated, $sold, $raised] = $this->computeStats($donations);

        $this->assertSame(3, $donated);
    }

    public function testOnlySoldItemsCountTowardsSoldAndRaised(): void
    {
        $donations = [
            ['status' => 'active',  'current_bid' => '50.00'],
            ['status' => 'sold',    'current_bid' => '100.00'],
            ['status' => 'sold',    'current_bid' => '75.50'],
            ['status' => 'ended',   'current_bid' => '200.00'],
        ];

        [$donated, $sold, $raised] = $this->computeStats($donations);

        $this->assertSame(4, $donated);
        $this->assertSame(2, $sold);
        $this->assertEqualsWithDelta(175.50, $raised, 0.001);
    }

    public function testMissingCurrentBidDefaultsToZero(): void
    {
        $donations = [
            ['status' => 'sold'], // no current_bid key
        ];

        [$donated, $sold, $raised] = $this->computeStats($donations);

        $this->assertSame(1, $donated);
        $this->assertSame(1, $sold);
        $this->assertSame(0.0, $raised);
    }

    public function testNonSoldItemsDoNotInflateRaised(): void
    {
        $donations = [
            ['status' => 'active', 'current_bid' => '999.00'],
            ['status' => 'ended',  'current_bid' => '500.00'],
        ];

        [$donated, $sold, $raised] = $this->computeStats($donations);

        $this->assertSame(2, $donated);
        $this->assertSame(0, $sold);
        $this->assertSame(0.0, $raised);
    }
}
