<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GiftAidServiceTest extends TestCase
{
    private function makeService(): \App\Services\GiftAidService
    {
        $giftAidRepo = $this->createMock(\App\Repositories\GiftAidRepository::class);
        return new \App\Services\GiftAidService($giftAidRepo);
    }

    public function testCalculationAboveMarketValue(): void
    {
        $service = $this->makeService();

        // Bid £200, market £100 → Gift Aid applies on £100 surplus → 25% of £100 = £25
        $result = $service->calculate(200.00, 100.00);

        $this->assertEquals(25.00, $result);
    }

    public function testCalculationAtMarketValue(): void
    {
        $service = $this->makeService();

        // Bid exactly at market value — no surplus, no Gift Aid
        $result = $service->calculate(100.00, 100.00);

        $this->assertEquals(0.00, $result);
    }

    public function testCalculationBelowMarketValue(): void
    {
        $service = $this->makeService();

        // Bid below market value — no Gift Aid possible
        $result = $service->calculate(50.00, 100.00);

        $this->assertEquals(0.00, $result);
    }

    public function testDeclarationRequiresConfirmedTaxpayer(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();

        $declaration = [
            'full_name'          => 'John Smith',
            'address'            => '123 High Street, Glasgow',
            'postcode'           => 'G1 1AA',
            'confirmed_taxpayer' => 0,
        ];

        $service->saveDeclaration(1, 1, $declaration, 25.00);
    }

    public function testDeclarationRequiresFullName(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = $this->makeService();

        $declaration = [
            'full_name'          => '',
            'address'            => '123 High Street, Glasgow',
            'postcode'           => 'G1 1AA',
            'confirmed_taxpayer' => 1,
        ];

        $service->saveDeclaration(1, 1, $declaration, 25.00);
    }
}
