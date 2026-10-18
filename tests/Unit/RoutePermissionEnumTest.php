<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Saola\Core\Routing\Permission;
use Saola\Core\Routing\Router;
use Saola\Core\System;
use Tests\TestCase;
use TypeError;

class RoutePermissionEnumTest extends TestCase
{
    protected function tearDown(): void
    {
        System::reset();
        parent::tearDown();
    }

    public function test_typed_declaration_exports_serializable_values_without_permission_package(): void
    {
        $route = (new Router)->permission(Permission::READ, Permission::UPDATE, Permission::READ);
        $this->assertSame(['read', 'update'], $route->toArray()['permission']);
        $this->assertSame(['access', 'read', 'create', 'update', 'delete', 'restore', 'force_delete', 'extra'], array_column(Permission::cases(), 'value'));
        $this->assertSame(['read'], json_decode(json_encode((new Router)->permission(Permission::READ)->toArray()), true)['permission']);
    }

    public function test_string_setter_is_rejected(): void
    {
        $this->expectException(TypeError::class);
        (new Router)->permission('read');
    }

    public function test_constructor_accepts_enum_cases(): void
    {
        $route = new Router(['permission' => [Permission::ACCESS, Permission::READ]]);
        $this->assertSame(['access', 'read'], $route->toArray()['permission']);
        $route->permission = Permission::UPDATE;
        $this->assertSame(['update'], $route->toArray()['permission']);
    }

    public function test_constructor_rejects_strings_in_arrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Router(['permission' => [Permission::READ, 'update']]);
    }

    public function test_property_assignment_rejects_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $route = new Router;
        $route->permission = 'read';
    }

    public function test_scoped_catalog_distinguishes_contexts_paths_and_custom_prefixes(): void
    {
        System::admin(['permission' => Permission::ACCESS])->module('users', ['prefix' => 'people'])
            ->get('/', fn () => 'ok')->permission(Permission::READ);
        System::api()->module('users')->get('/', fn () => 'ok')->permission(Permission::READ);
        System::addPermission('admin', ['users', 'profile'], Permission::UPDATE);
        $rows = System::permissionDeclarations();
        $this->assertContains(['context' => 'admin', 'module_path' => [], 'operation' => 'access'], $rows);
        $this->assertContains(['context' => 'admin', 'module_path' => ['users'], 'operation' => 'read'], $rows);
        $this->assertContains(['context' => 'api', 'module_path' => ['users'], 'operation' => 'read'], $rows);
        $this->assertContains(['context' => 'admin', 'module_path' => ['users', 'profile'], 'operation' => 'update'], $rows);
        $this->assertCount(3, System::permissionDeclarations('admin'));
    }

    public function test_extra_declaration_rejects_string_operation(): void
    {
        $this->expectException(TypeError::class);
        System::addPermission('admin', ['users'], 'read');
    }
}
