<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use App\Repositories\UserRepository;

class AdminEmailChangeTest extends TestCase
{
    private function makeService(UserRepository $repo): AuthService
    {
        return new AuthService($repo);
    }

    public function testThrowsOnInvalidEmailFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid email/i');

        $repo = $this->createMock(UserRepository::class);
        $this->makeService($repo)->changeUserEmail(1, 'not-an-email', 'old@example.com');
    }

    public function testThrowsWhenNewEmailSameAsCurrent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already their email/i');

        $repo = $this->createMock(UserRepository::class);
        $this->makeService($repo)->changeUserEmail(1, 'same@example.com', 'same@example.com');
    }

    public function testThrowsWhenEmailTakenByAnotherUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already in use/i');

        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn(['id' => 99, 'email' => 'taken@example.com']);

        $this->makeService($repo)->changeUserEmail(1, 'taken@example.com', 'old@example.com');
    }

    public function testCallsUpdateEmailOnHappyPath(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        $repo->expects($this->once())
            ->method('updateEmail')
            ->with(1, 'new@example.com')
            ->willReturn('abc123token');
        $repo->method('findById')->willReturn([
            'id'    => 1,
            'name'  => 'Test User',
            'email' => 'new@example.com',
        ]);

        // Should not throw
        $this->makeService($repo)->changeUserEmail(1, 'new@example.com', 'old@example.com');
    }
}
