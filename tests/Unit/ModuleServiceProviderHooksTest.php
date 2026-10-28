<?php

namespace Tests\Unit;

use Saola\Core\Providers\ModuleServiceProvider;
use Saola\Core\System;
use Saola\Core\Routing\Permission;
use Tests\TestCase;

/**
 * define() khai báo module, menus()/permissions() phần không gắn với module;
 * cả ba tuỳ chọn, và module cũ chỉ khai báo routes() (tên cũ) không được hỏng.
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

            public function define(): void
            {
                $this->called[] = 'define';
            }

            public function menus(): void
            {
                $this->called[] = 'menus';
                System::addMenuItem('web', ['title' => 'Ngoài route']);
            }

            public function permissions(): void
            {
                $this->called[] = 'permissions';
                System::addPermission('web', ['jobs'], Permission::CREATE);
            }
        };

        $provider->boot();

        $this->assertSame(['define', 'menus', 'permissions'], $provider->called);
        $this->assertSame(['Ngoài route'], array_column(System::menu('web'), 'title'));
        $this->assertSame(['create'], System::permissions('web'));
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

    public function test_module_khong_khai_bao_hook_nao_van_boot_duoc(): void
    {
        (new class ($this->app) extends ModuleServiceProvider {})->boot();
        $this->assertSame([], System::permissions('web'));
    }
}
