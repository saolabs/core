<?php

namespace Saola\Core;

use Saola\Core\Routing\Context;
use Saola\Core\Routing\Registry;

/**
 * Cửa vào cấp ứng dụng cho {@see Registry}.
 *
 * KHÔNG có logic ở đây, cũng không có trạng thái: cây context/module là chuyện
 * của routing nên nó ở `Saola\Core\Routing\Registry`, lớp này chỉ uỷ quyền để
 * chỗ khai báo đọc thuận — `System::context('web')->module(...)`.
 *
 * Thêm hành vi thì thêm ở Registry rồi nối một dòng qua đây; đừng để nhánh nào
 * mọc trong lớp này, nếu không lại thành lớp thứ hai chứ không còn là cửa vào.
 */
class System
{
    const WEB = Registry::WEB;
    const ADMIN = Registry::ADMIN;
    const API = Registry::API;

    /** @return Context */
    public static function context(string $slug, array $defaultData = [])
    {
        return Registry::context($slug, $defaultData);
    }

    /** @return Context */
    public static function addContext(string $slug, array $data = [])
    {
        return Registry::addContext($slug, $data);
    }

    /** @return Context|null */
    public static function getContext(string $slug)
    {
        return Registry::getContext($slug);
    }

    /** @return array<string,Context> */
    public static function getContexts(): array
    {
        return Registry::getContexts();
    }

    /** @return Context */
    public static function admin(array $defaultData = [])
    {
        return Registry::admin($defaultData);
    }

    /** @return Context */
    public static function web(array $defaultData = [])
    {
        return Registry::web($defaultData);
    }

    /** @return Context */
    public static function api(array $defaultData = [])
    {
        return Registry::api($defaultData);
    }

    /** @return list<array<string,mixed>> */
    public static function modules(?string $context = null): array
    {
        return Registry::modules($context);
    }

    /** @return list<array<string,mixed>> */
    public static function menu(?string $context = null): array
    {
        return Registry::menu($context);
    }

    /** @return list<string> */
    public static function permissions(?string $context = null): array
    {
        return Registry::permissions($context);
    }

    public static function addMenuItem(string $context, array $item): void
    {
        Registry::addMenuItem($context, $item);
    }

    /** @param string|list<string> $permission */
    public static function addPermission(string $context, $permission): void
    {
        Registry::addPermission($context, $permission);
    }

    public static function reset(): void
    {
        Registry::reset();
    }
}
