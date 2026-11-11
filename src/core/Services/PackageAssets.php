<?php

namespace Saola\Core\Services;

/**
 * Bundle JS do package Composer cung cấp cho client.
 *
 * Package khai báo trong composer.json của nó:
 *
 *     "extra": { "saola": { "client": "dist/client.js" } }
 *
 * hoặc theo context: `"client": { "admin": "dist/admin.js", "web": "dist/web.js" }`.
 * File là ES module build sẵn với `@saolabs/client` để external (import map trỏ
 * nó về runtime của app) và `export default defineBundle({...})`.
 *
 * `saola:assets` chép file vào `public/static/saola/packages/{vendor}/{name}/`;
 * trang nạp chúng qua `APP_CONFIGS.bundles`, TRƯỚC bundle của theme.
 */
class PackageAssets
{
    public const PUBLIC_DIR = 'static/saola/packages';

    /** @var array<string, array<string, list<string>>>|null tên package => context ('*' = mọi context) => file nguồn */
    protected ?array $declared = null;

    /** @return array<string, array<string, list<string>>> */
    public function declared(): array
    {
        if ($this->declared !== null) {
            return $this->declared;
        }

        $this->declared = [];
        $installed = base_path('vendor/composer/installed.json');
        if (!is_file($installed)) {
            return $this->declared;
        }
        $data = json_decode((string) file_get_contents($installed), true);
        $packages = is_array($data) ? ($data['packages'] ?? $data) : [];
        usort($packages, static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        foreach ($packages as $package) {
            $client = $package['extra']['saola']['client'] ?? null;
            $name = (string) ($package['name'] ?? '');
            if ($client === null || !preg_match('#^[a-z0-9_.-]+/[a-z0-9_.-]+$#', $name)) {
                continue;
            }
            $dir = base_path('vendor/composer/' . ($package['install-path'] ?? '../' . $name));
            $byContext = is_array($client) && !array_is_list($client) ? $client : ['*' => $client];
            foreach ($byContext as $context => $files) {
                foreach ((array) $files as $file) {
                    $this->declared[$name][$context][] = rtrim($dir, '/') . '/' . ltrim((string) $file, '/');
                }
            }
        }

        return $this->declared;
    }

    /**
     * Chép mọi bundle khai báo ra public. Trả về [nguồn => đích].
     *
     * @return array<string, string>
     */
    public function publish(): array
    {
        $copied = [];
        foreach ($this->declared() as $name => $contexts) {
            foreach ($contexts as $files) {
                foreach ($files as $source) {
                    if (!is_file($source)) {
                        throw new \RuntimeException("Package [{$name}] khai báo extra.saola.client nhưng thiếu file: {$source}");
                    }
                    $target = $this->publicPath($name, $source);
                    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
                        throw new \RuntimeException('Không tạo được thư mục ' . dirname($target));
                    }
                    copy($source, $target);
                    $copied[$source] = $target;
                }
            }
        }

        return $copied;
    }

    /**
     * URL bundle của package cho một context, theo tên package.
     *
     * @return list<string>
     */
    public function urls(string $context): array
    {
        $urls = [];
        foreach ($this->declared() as $name => $contexts) {
            foreach ([...($contexts['*'] ?? []), ...($contexts[$context] ?? [])] as $source) {
                $public = $this->publicPath($name, $source);
                if (!is_file($public)) {
                    // Im lặng là kịch bản tệ nhất: SSR có, client thiếu helper → nổ lúc chạy.
                    report(new \RuntimeException(
                        "Package [{$name}] có bundle client chưa publish ({$public}) — chạy `php artisan saola:assets`."
                    ));
                    continue;
                }
                $relative = substr($public, strlen(public_path()) + 1);
                $urls[] = asset(str_replace(DIRECTORY_SEPARATOR, '/', $relative)) . '?v=' . filemtime($public);
            }
        }

        return $urls;
    }

    protected function publicPath(string $package, string $source): string
    {
        return public_path(self::PUBLIC_DIR . '/' . $package . '/' . basename($source));
    }
}
