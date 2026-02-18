<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testMaskNameFullName(): void
    {
        $this->assertSame('Jane D.', maskName('Jane Doe'));
    }

    public function testMaskNameSingleWord(): void
    {
        $this->assertSame('Jane', maskName('Jane'));
    }

    public function testMaskNameMultipleWords(): void
    {
        $this->assertSame('Jane D.', maskName('Jane Mary Doe'));
    }

    public function testMaskNameEmpty(): void
    {
        $this->assertSame('', maskName(''));
    }

    public function testMaskNameWhitespaceOnly(): void
    {
        $this->assertSame('', maskName('   '));
    }
}
