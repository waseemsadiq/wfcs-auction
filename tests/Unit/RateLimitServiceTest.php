<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RateLimitServiceTest extends TestCase
{
    private function makeService(array $overrides = []): \App\Services\RateLimitService
    {
        $repo = $overrides['repo'] ?? $this->createMock(\App\Repositories\RateLimitRepository::class);
        return new \App\Services\RateLimitService($repo);
    }

    public function testAllowsRequestsBelowThreshold(): void
    {
        $repo = $this->createMock(\App\Repositories\RateLimitRepository::class);

        // Simulate 4 attempts (threshold for login is 5)
        $repo->method('find')->willReturn([
            'id'            => 1,
            'identifier'    => '127.0.0.1',
            'action'        => 'login',
            'attempts'      => 4,
            'window_start'  => date('Y-m-d H:i:s', time() - 60),  // 1 minute ago
            'blocked_until' => null,
        ]);

        $repo->expects($this->once())->method('increment');

        $service = new \App\Services\RateLimitService($repo);

        // Should not throw — attempt 4 is within threshold (max=5)
        // For this test we verify it increments without throwing
        $threw = false;
        try {
            $service->check('127.0.0.1', 'login');
        } catch (\RuntimeException $e) {
            $threw = true;
        }
        $this->assertFalse($threw, 'Expected no exception for attempt below threshold');
    }

    public function testBlocksAfterThresholdExceeded(): void
    {
        $this->expectException(\RuntimeException::class);

        $repo = $this->createMock(\App\Repositories\RateLimitRepository::class);

        // Simulate already-blocked record
        $repo->method('find')->willReturn([
            'id'            => 2,
            'identifier'    => '127.0.0.1',
            'action'        => 'login',
            'attempts'      => 5,
            'window_start'  => date('Y-m-d H:i:s', time() - 60),
            'blocked_until' => date('Y-m-d H:i:s', time() + 1800),  // Blocked for 30 minutes
        ]);

        $service = new \App\Services\RateLimitService($repo);
        $service->check('127.0.0.1', 'login');
    }

    public function testResetsWindowAfterExpiry(): void
    {
        $repo = $this->createMock(\App\Repositories\RateLimitRepository::class);

        // Simulate record with window expired (window started 20 minutes ago, window is 15 min)
        $repo->method('find')->willReturn([
            'id'            => 3,
            'identifier'    => '192.168.1.1',
            'action'        => 'login',
            'attempts'      => 4,
            'window_start'  => date('Y-m-d H:i:s', time() - 1200),  // 20 minutes ago
            'blocked_until' => null,
        ]);

        // Should call upsert to reset the window, NOT increment
        $repo->expects($this->once())->method('upsert');
        $repo->expects($this->never())->method('increment');

        $service = new \App\Services\RateLimitService($repo);

        try {
            $service->check('192.168.1.1', 'login');
        } catch (\RuntimeException $e) {
            $this->fail('Expected no exception after window reset but got: ' . $e->getMessage());
        }
    }

    public function testDifferentActionsTrackedSeparately(): void
    {
        $repo = $this->createMock(\App\Repositories\RateLimitRepository::class);

        // Simulate 'login' is blocked but 'bid' is not
        $repo->method('find')->willReturnCallback(function (string $identifier, string $action) {
            if ($action === 'login') {
                return [
                    'id'            => 4,
                    'identifier'    => $identifier,
                    'action'        => 'login',
                    'attempts'      => 5,
                    'window_start'  => date('Y-m-d H:i:s', time() - 60),
                    'blocked_until' => date('Y-m-d H:i:s', time() + 1800),
                ];
            }
            // 'bid' action has no record
            return null;
        });

        $service = new \App\Services\RateLimitService($repo);

        // 'login' should throw
        $loginBlocked = false;
        try {
            $service->check('127.0.0.1', 'login');
        } catch (\RuntimeException $e) {
            $loginBlocked = true;
        }

        // 'bid' should not throw
        $bidBlocked = false;
        try {
            $service->check('127.0.0.1', 'bid');
        } catch (\RuntimeException $e) {
            $bidBlocked = true;
        }

        $this->assertTrue($loginBlocked, 'login action should be blocked');
        $this->assertFalse($bidBlocked, 'bid action should not be blocked');
    }
}
