<?php

namespace Tests\Unit;

use Saola\Core\Routing\Registry;
use Saola\Core\System;
use Tests\TestCase;

/**
 * Registry là cây context/module đọc được ngoài routing.
 *
 * Trước đây cây này chỉ có một khách hàng — pushLaravelRoute() — nên
 * title/display_name/permission khai báo trên node bị thu thập rồi vứt.
 */
class SystemRegistryTest extends TestCase
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

    public function test_system_uy_quyen_xuong_cay_cua_routing(): void
    {
        $context = System::context('web', ['as' => 'web']);

        // Một cây duy nhất: System không giữ bản sao nào của nó.
        $this->assertSame($context, Registry::getContext('web'));
        $this->assertSame(Registry::getContexts(), System::getContexts());

        // Tên cũ đã xoá hẳn — không shim, không class_alias, không kế thừa giả.
        $this->assertFalse(class_exists(\Saola\Core\Routing\System::class));
        $this->assertFalse(class_exists(\Saola\Core\System\System::class));
    }

    public function test_system_khong_giu_trang_thai_rieng(): void
    {
        // Chốt chặn: System là cửa vào, không phải lớp thứ hai. Một static
        // property mọc ở đây là lúc cây bắt đầu có hai bản sao lệch nhau.
        $this->assertSame([], (new \ReflectionClass(System::class))->getStaticProperties());
    }

    public function test_module_doc_duoc_kem_metadata_phi_routing(): void
    {
        System::context('web', ['as' => 'web'])
            ->module('roster', ['prefix' => '/roster', 'priority' => 5])
            ->title('Danh sách nhân sự')
            ->display_name('Nhân sự')
            ->permission('roster.view')
            ->group(function ($module) {
                $module->get('/', 'index')->name('index');
            });

        $modules = System::modules('web');

        $this->assertCount(1, $modules);
        $this->assertSame('roster', $modules[0]['slug']);
        $this->assertSame('Nhân sự', $modules[0]['display_name']);
        $this->assertSame('Danh sách nhân sự', $modules[0]['title']);
        $this->assertSame(['roster.view'], $modules[0]['permission']);
        $this->assertSame(5, $modules[0]['priority']);
        $this->assertSame(0, $modules[0]['depth']);
    }

    public function test_submodule_giu_quan_he_cha_con(): void
    {
        System::context('admin', ['as' => 'admin'])
            ->module('catalog', ['prefix' => 'catalog'])
            ->display_name('Kho')
            ->group(function ($module) {
                $module->sub('brands', ['prefix' => 'brands'])
                    ->display_name('Thương hiệu')
                    ->group(function ($sub) {
                        $sub->get('/', 'index')->name('index');
                    });
            });

        $modules = System::modules('admin');
        $paths = array_column($modules, 'path');

        $this->assertContains('catalog', $paths);
        $this->assertContains('catalog.brands', $paths);

        $child = $modules[array_search('catalog.brands', $paths, true)];
        $this->assertSame('catalog', $child['parent']);
        $this->assertSame(1, $child['depth']);
    }

    public function test_permission_gom_ca_cay_lan_cai_module_tu_them(): void
    {
        System::context('admin', ['as' => 'admin', 'permission' => ['admin']])
            ->module('users')
            ->permission('users.manage')
            ->group(function ($module) {
                $module->get('/', 'index')->name('index')->permission('users.view');
            });
        System::addPermission('admin', 'reports.export');

        $permissions = System::permissions('admin');

        sort($permissions);
        $this->assertSame(['admin', 'reports.export', 'users.manage', 'users.view'], $permissions);
    }

    public function test_menu_chi_lay_module_co_nhan_va_tron_item_tu_them(): void
    {
        $context = System::context('web', ['as' => 'web']);
        $context->module('docs', ['priority' => 2])->display_name('Tài liệu')
            ->group(fn ($m) => $m->get('/', 'index')->name('index'));
        // Không nhãn ⇒ module hạ tầng, không lên menu.
        $context->module('internal', ['priority' => 1])
            ->group(fn ($m) => $m->get('/', 'index')->name('index'));
        System::addMenuItem('web', ['title' => 'Trạng thái', 'href' => 'https://status.example', 'priority' => 9]);

        $menu = System::menu('web');

        $this->assertSame(['Tài liệu', 'Trạng thái'], array_column($menu, 'title'));
    }

    public function test_reset_xoa_sach_ca_ba_kho(): void
    {
        System::context('web', ['as' => 'web'])->module('x')->display_name('X');
        System::addMenuItem('web', ['title' => 'Y']);
        System::addPermission('web', 'z');

        System::reset();

        $this->assertSame([], System::getContexts());
        $this->assertSame([], System::menu());
        $this->assertSame([], System::permissions());
    }
}
