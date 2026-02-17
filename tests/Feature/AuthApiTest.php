<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class AuthApiTest extends TestCase
{
    public function testTokenEndpointReturnsJwt(): void
    {
        $this->markTestIncomplete('Feature tests require full app bootstrap — implement in Phase 13');
    }

    public function testTokenEndpointRejects401OnBadPassword(): void
    {
        $this->markTestIncomplete('Feature tests require full app bootstrap — implement in Phase 13');
    }

    public function testTokenEndpointRateLimited(): void
    {
        $this->markTestIncomplete('Feature tests require full app bootstrap — implement in Phase 13');
    }
}
