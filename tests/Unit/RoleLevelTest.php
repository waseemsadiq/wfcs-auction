<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RoleLevelTest extends TestCase
{
    public function testSuperAdminLevel(): void
    {
        $this->assertSame(3, roleLevel('super_admin'));
    }

    public function testAdminLevel(): void
    {
        $this->assertSame(2, roleLevel('admin'));
    }

    public function testDonorLevel(): void
    {
        $this->assertSame(1, roleLevel('donor'));
    }

    public function testBidderLevel(): void
    {
        $this->assertSame(0, roleLevel('bidder'));
    }

    public function testUnknownRoleDefaultsToZero(): void
    {
        $this->assertSame(0, roleLevel(''));
        $this->assertSame(0, roleLevel('wizard'));
    }
}
