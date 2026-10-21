<?php

namespace Saola\Core;

use Saola\Core\Services\ClientShare;

/**
 * Cửa cho package cắm vào phía client. Không giữ state: mọi thứ nằm trong
 * service của container (cùng luật với System).
 */
final class Saola
{
    /**
     * Chia sẻ dữ liệu theo request cho client — đọc bằng `App.shared($key)` ở JS.
     *
     *     Saola::share('permission', fn (Request $r) => ['routes' => ...]);
     *
     * @param callable(\Illuminate\Http\Request): mixed $resolver
     */
    public static function share(string $key, callable $resolver): void
    {
        app(ClientShare::class)->share($key, $resolver);
    }
}
