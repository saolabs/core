<?php

namespace Tests\Unit;

use Saola\Core\Providers\ModuleServiceProvider;
use Saola\Core\System;
use Tests\TestCase;

/**
 * `routes()` từng là hook duy nhất của một module. Hai hook phi-routing phải
 * chạy, và phải là tuỳ chọn — module cũ chỉ khai báo routes() không được hỏng.
 */
class ModuleServiceProviderHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        System::reset();
    }

    protected function tearDown(): void
    {
        System::reset();
        parent::tearDown();
    }

    public function test_boot_goi_ca_ba_hook(): void
    {
        $provider = new class ($this->app) extends ModuleServiceProvider {
            public array $called = [];

            public function routes()
            {
                $this->called[] = 'routes';
            }

            public function menus(): void
            {
                $this->called[] = 'menus';
                System::addMenuItem('web', ['title' => 'Ngoài route']);
            }

            public function permissions(): void
            {
                $this->called[] = 'permissions';
                System::addPermission('web', 'jobs.run');
            }
        };

        $provider->boot();

        $this->assertSame(['routes', 'menus', 'permissions'], $provider->called);
        $this->assertSame(['Ngoài route'], array_column(System::menu('web'), 'title'));
        $this->assertSame(['jobs.run'], System::permissions('web'));
    }

    public function test_module_chi_khai_bao_routes_van_boot_duoc(): void
    {
        $provider = new class ($this->app) extends ModuleServiceProvider {
            public bool $ran = false;

            public function routes()
            {
                $this->ran = true;
            }
        };

        $provider->boot();

        $this->assertTrue($provider->ran);
    }
}
