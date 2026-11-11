<?php

namespace Saola\Core\Services;

use Closure;
use Illuminate\Http\Request;
use Throwable;

/**
 * Dữ liệu theo request mà package chia sẻ cho client: `Saola::share()` →
 * `APP_CONFIGS.shared` → `App.shared(key)` ở JS.
 *
 * Đăng ký lúc boot của ServiceProvider (singleton sống cả worker Octane), giá trị
 * thì tính lại MỖI lần render trang. Không dùng cho bí mật: mọi thứ ở đây nằm
 * nguyên văn trong HTML.
 */
class ClientShare
{
    /** @var array<string, Closure(Request): mixed> */
    protected array $resolvers = [];

    /** @param callable(Request): mixed $resolver trả null thì khoá bị bỏ khỏi trang */
    public function share(string $key, callable $resolver): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/', $key)) {
            throw new \InvalidArgumentException("Khoá Saola::share không hợp lệ: {$key}");
        }
        $this->resolvers[$key] = Closure::fromCallable($resolver);
    }

    /** @return array<string, mixed> */
    public function export(Request $request): array
    {
        $shared = [];
        foreach ($this->resolvers as $key => $resolver) {
            try {
                $value = $resolver($request);
            } catch (Throwable $e) {
                // Một package lỗi không được làm trắng trang của cả app.
                report($e);
                continue;
            }
            if ($value !== null) {
                $shared[$key] = $value;
            }
        }

        return $shared;
    }
}
