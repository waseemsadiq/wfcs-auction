<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private function makeService(array $overrides = []): \App\Services\AuthService
    {
        $userRepo = $overrides['userRepo'] ?? $this->createMock(\App\Repositories\UserRepository::class);
        return new \App\Services\AuthService($userRepo);
    }

    public function testPasswordIsHashed(): void
    {
        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);

        $userRepo->method('findByEmail')->willReturn(null);
        $userRepo->method('findBySlug')->willReturn(null);

        $capturedHash = null;
        $userRepo->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (array $data) use (&$capturedHash) {
                $capturedHash = $data['password_hash'];
                return 1;
            });

        $userRepo->method('findById')->willReturn([
            'id'                => 1,
            'slug'              => 'jane-smith',
            'name'              => 'Jane Smith',
            'email'             => 'jane@example.com',
            'password_hash'     => $capturedHash,
            'role'              => 'bidder',
            'email_verified_at' => null,
        ]);

        $service = new \App\Services\AuthService($userRepo);

        $service->register('Jane Smith', 'jane@example.com', 'securepassword123', 'bidder');

        $this->assertNotNull($capturedHash);
        $this->assertTrue(password_verify('securepassword123', $capturedHash));
        $this->assertNotEquals('securepassword123', $capturedHash);
    }

    public function testDuplicateEmailThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);
        $userRepo->method('findByEmail')->willReturn([
            'id'    => 99,
            'email' => 'existing@example.com',
        ]);

        $service = new \App\Services\AuthService($userRepo);
        $service->register('Existing User', 'existing@example.com', 'password123', 'bidder');
    }

    public function testLoginWithWrongPasswordThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);
        $userRepo->method('findByEmail')->willReturn([
            'id'            => 5,
            'email'         => 'user@example.com',
            'password_hash' => password_hash('correctpassword', PASSWORD_DEFAULT),
            'role'          => 'bidder',
        ]);

        $service = new \App\Services\AuthService($userRepo);
        $service->login('user@example.com', 'wrongpassword');
    }

    public function testLoginWithUnknownEmailThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);
        $userRepo->method('findByEmail')->willReturn(null);

        $service = new \App\Services\AuthService($userRepo);
        $service->login('nobody@example.com', 'anypassword');
    }

    public function testSlugGeneratedFromName(): void
    {
        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);
        $userRepo->method('findBySlug')->willReturn(null);

        $service = new \App\Services\AuthService($userRepo);

        $slug = $service->generateSlug('John Doe');

        $this->assertEquals('john-doe', $slug);
    }

    public function testSlugDeduplicatedWithSuffix(): void
    {
        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);

        // First call finds existing slug, second call returns null (suffix available)
        $userRepo->expects($this->exactly(2))
            ->method('findBySlug')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'slug' => 'john-doe'],  // 'john-doe' taken
                null                                  // 'john-doe-2' is free
            );

        $service = new \App\Services\AuthService($userRepo);

        $slug = $service->generateSlug('John Doe');

        $this->assertEquals('john-doe-2', $slug);
    }

    public function testGenerateTokenExpiresInTwoHours(): void
    {
        $userRepo = $this->createMock(\App\Repositories\UserRepository::class);
        $service  = new \App\Services\AuthService($userRepo);

        $user = [
            'id'                => 1,
            'email'             => 'test@example.com',
            'name'              => 'Test User',
            'role'              => 'bidder',
            'slug'              => 'test-user',
            'email_verified_at' => '2026-01-01 00:00:00',
        ];

        $before = time();
        $token  = $service->generateToken($user);
        $after  = time();

        // Decode payload without signature verification
        $parts   = explode('.', $token);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertGreaterThanOrEqual($before, $payload['iat']);
        $this->assertLessThanOrEqual($after, $payload['iat']);

        $lifetime = $payload['exp'] - $payload['iat'];
        $this->assertEquals(7200, $lifetime, 'Session token must expire in exactly 120 minutes (7200 s)');
    }
}
