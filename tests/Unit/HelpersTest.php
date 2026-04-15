<?php
namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_format_currency()
    {
        $result = format_currency(1000000, 'VND');
        $this->assertStringContainsString('1.000.000', $result);
        $this->assertStringContainsString("\u{20AB}", $result);
    }
}