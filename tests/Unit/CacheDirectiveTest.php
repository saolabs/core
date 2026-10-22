<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Saola\Core\Providers\BladeDirectiveServiceProvider;
use Saola\Core\Providers\SaolaServiceProvider;
use Tests\TestCase;

/** @cache(...) là cấu hình PageCache phía client; Blade không được in nó ra trang. */
class CacheDirectiveTest extends TestCase
{
    public function test_cache_directive_khong_in_gi_o_server(): void
    {
        (new SaolaServiceProvider($this->app))->register();
        $provider = new BladeDirectiveServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertSame('<p>x</p>', trim(Blade::compileString("@cache(false)\n@cache({ ttl: 60000 })\n<p>x</p>")));
    }
}
