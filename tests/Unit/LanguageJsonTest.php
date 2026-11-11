<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Saola\Core\Language\LanguageJson;

class LanguageJsonTest extends TestCase
{
    public function test_nested_objects_root_leaves_unicode_and_escaped_strings(): void
    {
        $tree = LanguageJson::decode('{"save":"Lưu","module-name":{"label":"Line\\n\\"quote\\""}}');
        $this->assertSame(['save' => 'Lưu', 'module-name' => ['label' => "Line\n\"quote\""]], $tree);
    }

    public function test_duplicate_keys_including_equivalent_unicode_escapes_are_rejected(): void
    {
        foreach (['{"save":"a","save":"b"}', '{"group":{"save":"a","\\u0073ave":"b"}}'] as $json) {
            try { LanguageJson::decode($json); $this->fail('Duplicate keys must fail'); }
            catch (\InvalidArgumentException $error) { $this->assertStringContainsString('Duplicate', $error->getMessage()); }
        }
    }

    public function test_invalid_types_syntax_utf8_and_resource_limits_are_rejected(): void
    {
        foreach (['[]', '{"x":null}', '{"x":[]}', '{"x":1}', '{"x":true}', '{"x":"y",}', '{"x":"y"} trailing', '{"x":"'."\xff".'"}', '{"__proto__":{"x":"y"}}'] as $json) {
            try { LanguageJson::decode($json); $this->fail('Invalid payload must fail: '.$json); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        foreach ([[2, 100], [1000, 1]] as [$bytes, $keys]) {
            try { LanguageJson::decode('{"a":"a","b":"b"}', $bytes, $keys); $this->fail('Limits must fail'); }
            catch (\InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }
}
