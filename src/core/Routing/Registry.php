<?php

namespace Saola\Core\Routing;

/**
 * Kho cây context → module → route, cùng phần đọc cây.
 *
 * Cây này trước đây chỉ có đúng MỘT khách hàng: `Context::pushLaravelRoute()`.
 * Mọi metadata phi-routing khai báo trên node — `title`, `display_name`,
 * `description`, `permission` — bị thu thập rồi vứt, vì không ai gọi
 * `getContexts()` lẫn `Router::toArray()`. `modules()`, `menu()` và
 * `permissions()` là chỗ đọc lại đúng số metadata đó, cho menu, breadcrumb,
 * sidebar và seed quyền.
 *
 * Điểm vào cấp ứng dụng là {@see \Saola\Core\System} — lớp đó không giữ logic
 * nào, chỉ uỷ quyền xuống đây.
 *
 * Trạng thái tĩnh sống theo worker, KHÔNG theo request: cây dựng một lần lúc
 * provider boot, y như route của Laravel. `reset()` chỉ dành cho test.
 */
class Registry
{
    const WEB = 'web';
    const ADMIN = 'admin';
    const API = 'api';

    /** Node được coi là module (khớp Router::$moduleTypes). */
    private const MODULE_TYPES = ['module', 'submodule', 'sub', 'group'];

    /** @var array<string,Context> */
    protected static $contexts = [];

    /**
     * Menu item do module tự thêm — thứ KHÔNG suy ra được từ cây route
     * (link ngoài, mục trỏ tới trang của package khác).
     *
     * @var array<string,list<array<string,mixed>>>
     */
    protected static $menus = [];

    /**
     * Permission không gắn với route nào (quyền theo dữ liệu, quyền của job).
     *
     * @var array<string,list<string>>
     */
    protected static $permissions = [];

    // ──────────────────────────── context / module ────────────────────────────

    /**
     * thêm Context
     *
     * @param string $slug
     * @param array $data
     * @return Context
     */
    public static function addContext($slug, $data = [])
    {
        if (!array_key_exists($slug, static::$contexts)) {
            if (!array_key_exists('slug', $data) || $data['slug'] == null || $data['slug'] == '') {
                $data['slug'] = $slug;
            }
            $data['type'] = 'context';
            $data['context'] = $slug;
            if (!array_key_exists('as', $data) || $data['as'] == null || $data['as'] == '') {
                $data['as'] = $slug;
            }
            static::$contexts[$slug] = new Context($data);
        }
        return static::$contexts[$slug];
    }

    /**
     * lấy context
     *
     * @param string $slug
     * @return Context|null
     */
    public static function getContext($slug)
    {
        return static::$contexts[$slug] ?? null;
    }

    /**
     * lấy tất cả Context
     *
     * @return array<string, Context>
     */
    public static function getContexts()
    {
        return static::$contexts;
    }

    /**
     * lấy Context hoặc tạo mới
     *
     * @param string $slug
     * @param array $defaultData
     * @return Context
     */
    public static function context($slug, $defaultData = [])
    {
        return static::getContext($slug) ?: static::addContext($slug, $defaultData);
    }

    /** @return Context */
    public static function admin($defaultData = [])
    {
        return static::context(static::ADMIN, $defaultData);
    }

    /** @return Context */
    public static function web($defaultData = [])
    {
        return static::context(static::WEB, $defaultData);
    }

    /** @return Context */
    public static function api($defaultData = [])
    {
        return static::context(static::API, $defaultData);
    }

    // ─────────────────────────────── đọc cây ──────────────────────────────────

    /**
     * Cây module đã làm phẳng, đọc được ở bất kỳ đâu — menu, breadcrumb,
     * sidebar admin, seed permission, trang liệt kê module.
     *
     * @param string|null $context null = mọi context
     * @return list<array{context:string,slug:?string,path:string,parent:?string,type:string,title:?string,display_name:?string,description:?string,prefix:?string,route:?string,permission:list<string>,priority:int,depth:int}>
     */
    public static function modules(?string $context = null): array
    {
        $modules = [];
        static::walk($context, static function (array $node, string $ctx, array $trail) use (&$modules): void {
            if (!in_array($node['type'] ?? 'router', self::MODULE_TYPES, true)) {
                return;
            }
            $modules[] = [
                'context' => $ctx,
                'slug' => $node['slug'] ?? null,
                'path' => implode('.', array_filter($trail)),
                'parent' => ($p = implode('.', array_filter(array_slice($trail, 0, -1)))) === '' ? null : $p,
                'type' => $node['type'] ?? 'module',
                'title' => $node['title'] ?? null,
                'display_name' => $node['display_name'] ?? null,
                'description' => $node['description'] ?? null,
                'prefix' => $node['prefix'] ?? null,
                'route' => $node['as'] ?? null,
                'permission' => array_values(array_filter((array) ($node['permission'] ?? []))),
                'priority' => (int) ($node['priority'] ?? 0),
                'depth' => max(count($trail) - 1, 0),
            ];
        });

        usort($modules, static fn (array $a, array $b): int => [$a['context'], $a['depth'], $a['priority']]
            <=> [$b['context'], $b['depth'], $b['priority']]);

        return $modules;
    }

    /**
     * Mọi permission khai báo trên cây (context, module, route) hợp với các
     * permission module tự thêm. Dùng để seed bảng quyền mà không phải chép
     * tay danh sách lần thứ hai.
     *
     * @return list<string>
     */
    public static function permissions(?string $context = null): array
    {
        $permissions = [];
        static::walk($context, static function (array $node) use (&$permissions): void {
            foreach ((array) ($node['permission'] ?? []) as $permission) {
                $permissions[] = $permission;
            }
        });
        foreach (static::$permissions as $ctx => $list) {
            if ($context === null || $ctx === $context) {
                $permissions = array_merge($permissions, $list);
            }
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    /**
     * Menu suy ra từ cây module, trộn với item module tự thêm.
     *
     * Chỉ module có nhãn hiển thị (`display_name`, hoặc `title` nếu không có)
     * mới lên menu — module hạ tầng không đặt nhãn thì tự động vắng mặt.
     * Khoá của item khớp `Html\MenuItem`: title / href / icon / submenu.
     *
     * @return list<array<string,mixed>>
     */
    public static function menu(?string $context = null): array
    {
        $byPath = [];
        $roots = [];

        foreach (static::modules($context) as $module) {
            $label = $module['display_name'] ?? $module['title'];
            if (!$label) {
                continue;
            }
            $byPath[$module['path']] = [
                'title' => $label,
                'href' => static::hrefFor($module['route']),
                'route' => $module['route'],
                'permission' => $module['permission'],
                'priority' => $module['priority'],
                'submenu' => [],
            ];

            // Cha bị bỏ qua vì không có nhãn ⇒ con leo lên gốc, không biến mất.
            if ($module['parent'] !== null && isset($byPath[$module['parent']])) {
                $byPath[$module['parent']]['submenu'][] = &$byPath[$module['path']];
            } else {
                $roots[] = &$byPath[$module['path']];
            }
        }

        foreach (static::$menus as $ctx => $items) {
            if ($context === null || $ctx === $context) {
                foreach ($items as $item) {
                    $roots[] = $item + ['submenu' => [], 'priority' => 0];
                }
            }
        }

        return static::sortByPriority($roots);
    }

    // ──────────────────────────── mặt phi-routing ─────────────────────────────

    /**
     * Thêm một mục menu không suy ra được từ route (link ngoài, trang tĩnh).
     * Khoá dùng được: title, href, icon, active_key, priority, permission.
     */
    public static function addMenuItem(string $context, array $item): void
    {
        static::$menus[$context][] = $item;
    }

    /**
     * Đăng ký permission không gắn với route nào.
     *
     * @param string|list<string> $permission
     */
    public static function addPermission(string $context, $permission): void
    {
        foreach ((array) $permission as $name) {
            static::$permissions[$context][] = $name;
        }
    }

    /** Xoá sạch registry — dùng cho test, và cho worker cần dựng lại cây. */
    public static function reset(): void
    {
        static::$contexts = [];
        static::$menus = [];
        static::$permissions = [];
    }

    // ─────────────────────────────── nội bộ ───────────────────────────────────

    /**
     * Duyệt cây đã `toArray()`.
     *
     * `$trail` là chuỗi slug của các node MODULE tính từ context xuống —
     * context không góp slug vì nó đã là khoá của cây.
     *
     * @param callable(array $node, string $context, list<string> $trail): void $visit
     */
    protected static function walk(?string $context, callable $visit): void
    {
        foreach (static::contextsFor($context) as $slug => $ctx) {
            static::walkNode($ctx->toArray(), $slug, [], $visit);
        }
    }

    /** @return array<string,Context> */
    protected static function contextsFor(?string $context): array
    {
        if ($context === null) {
            return static::$contexts;
        }
        $one = static::getContext($context);

        return $one ? [$context => $one] : [];
    }

    /** @param callable(array,string,list<string>):void $visit */
    protected static function walkNode(array $node, string $context, array $trail, callable $visit): void
    {
        if (in_array($node['type'] ?? 'router', self::MODULE_TYPES, true)) {
            $trail[] = (string) ($node['slug'] ?? '');
        }
        $visit($node, $context, $trail);
        foreach ($node['children'] ?? [] as $child) {
            if (is_array($child)) {
                static::walkNode($child, $context, $trail, $visit);
            }
        }
    }

    /**
     * Tên route → URL.
     *
     * Tên của một module là tên NHÓM (`admin.users`), bản thân nó thường không
     * có URL — URL thật nằm ở route con. Không đoán theo quy ước `.index`: quy
     * ước đó hụt ngay trong demo (`web.users` chỉ có `.profile`). Lấy route con
     * đầu tiên không đòi tham số, theo đúng thứ tự đã đăng ký — tức thứ tự
     * priority mà cây module đã sắp.
     */
    protected static function hrefFor(?string $routeName): ?string
    {
        if (!$routeName || !function_exists('route') || !function_exists('app')) {
            return null;
        }

        try {
            $named = app('router')->getRoutes()->getRoutesByName();
        } catch (\Throwable) {
            // Router chưa sẵn sàng (menu dựng lúc boot) — để chỗ render tự lo.
            return null;
        }

        $prefix = $routeName . '.';
        foreach ([$routeName => true] + $named as $name => $_) {
            if ($name !== $routeName && !str_starts_with((string) $name, $prefix)) {
                continue;
            }
            $route = $named[$name] ?? null;
            if ($route === null || $route->parameterNames() !== []) {
                continue;
            }
            try {
                return route($name);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /** @param list<array<string,mixed>> $items */
    protected static function sortByPriority(array $items): array
    {
        usort($items, static fn (array $a, array $b): int => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));
        foreach ($items as $i => $item) {
            if (!empty($item['submenu'])) {
                $items[$i]['submenu'] = static::sortByPriority($item['submenu']);
            }
        }

        return $items;
    }
}
