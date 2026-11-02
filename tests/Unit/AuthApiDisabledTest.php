<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\ClientShare;
use Tests\TestCase;

class AuthApiDisabledTest extends TestCase
{
    protected function getPackageProviders($app): array { return [SaolaServiceProvider::class]; }

    public function test_custom_auth_hosts_keep_public_bootstrap_without_registering_standard_endpoints(): void
    {
        $this->assertFalse($this->app['router']->has('saola.auth.login'));
        $shared = app(ClientShare::class)->export(Request::create('/'));
        $this->assertArrayNotHasKey('authClient', $shared);
        $this->assertSame(1, $shared['auth']['version']);
        $this->assertSame(app('auth')->getDefaultDriver(), $shared['auth']['defaultGuard']);
    }
}
