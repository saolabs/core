<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Saola\Core\Language\ClientCatalogs;

/** A running app is not sent again the dictionaries it already holds; anything it does not declare travels as before. */
class ClientCatalogsHeldTest extends TestCase
{
    private function config(): array
    {
        return ['locale' => 'vi', 'fallbackLocale' => 'en', 'scope' => 'admin:abc', 'catalogs' => [
            'vi' => ['revision' => 7, 'scope' => 'admin:abc', 'messages' => ['save' => 'Lưu']],
            'en' => ['revision' => 3, 'scope' => 'admin:abc', 'messages' => ['save' => 'Save']],
        ]];
    }

    public function test_held_catalogs_at_the_same_revision_are_left_out(): void
    {
        $out = ClientCatalogs::withoutHeld($this->config(), 'admin:abc;vi=7;en=3');
        $this->assertSame([], $out['catalogs']);
        $this->assertSame(['vi', 'en', 'admin:abc'], [$out['locale'], $out['fallbackLocale'], $out['scope']]); // still tells the client where it stands
    }

    public function test_a_newer_revision_or_an_undeclared_locale_still_travels(): void
    {
        $this->assertSame(['en'], array_keys(ClientCatalogs::withoutHeld($this->config(), 'admin:abc;vi=7;en=2')['catalogs']));
        $this->assertSame(['en'], array_keys(ClientCatalogs::withoutHeld($this->config(), 'admin:abc;vi=7')['catalogs']));
    }

    public function test_no_header_another_scope_or_garbage_sends_everything(): void
    {
        foreach ([null, '', 'admin:old;vi=7;en=3', 'admin:abc;vi=x;en', 'nonsense'] as $held) {
            $this->assertSame(['vi', 'en'], array_keys(ClientCatalogs::withoutHeld($this->config(), $held)['catalogs']), (string) $held);
        }
    }
}
