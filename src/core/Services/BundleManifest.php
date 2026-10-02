<?php

namespace Saola\Core\Services;

/**
 * URL thật của các bundle JS/CSS mà trang phải nạp.
 *
 * Một chỗ duy nhất chứa nhánh dev/prod. Trước đây logic này nằm inline trong
 * `_system/partials/assets.blade.php`, nên mỗi khi cần URL ở chỗ khác
 * (`APP_CONFIGS.bundles`, modulepreload, import map) là phải chép lại — ba bản
 * chép là ba chỗ để lệch.
 *
 * DEV : `public/hot` chứa URL Vite dev server; mọi thứ load từ đó, KHÔNG có
 *       import map (Vite tự resolve bare specifier, emit thêm sẽ đá nhau với
 *       dep-optimizer).
 * BUILD: đọc `public/static/saola/{ctx}/manifest.json` do Vite ghi.
 */
class BundleManifest
{
    /** @var array<string, array<string, mixed>> Cache manifest theo context, mỗi request đọc một lần. */
    protected array $manifests = [];

    protected ?bool $isDev = null;

    protected ?array $buildInfo = null;

    /** Đang chạy Vite dev server không. */
    public function isDev(): bool
    {
        return $this->isDev ??= is_file(public_path('hot'));
    }

    /** Gốc URL của dev server, null nếu không ở chế độ dev. */
    public function devOrigin(): ?string
    {
        if (!$this->isDev()) {
            return null;
        }
        $hot = @file_get_contents(public_path('hot'));

        return is_string($hot) ? rtrim(trim($hot), '/') : null;
    }

    /** Đường nguồn của entry một context — khoá trong manifest của Vite. */
    public function entrySource(string $context): string
    {
        return "resources/js/saola/app.{$context}.js";
    }

    /** URL entry `app.{ctx}.js`. */
    public function entryUrl(string $context): string
    {
        return $this->url($context, $this->entrySource($context), "js/app.{$context}.js");
    }

    /** URL CSS của app, null nếu build không sinh ra. */
    public function cssUrl(string $context): ?string
    {
        $source = 'resources/css/app.css';
        if ($this->isDev()) {
            return $this->devUrl($source);
        }
        $file = $this->manifest($context)[$source]['file'] ?? null;

        return $file ? asset("static/saola/{$context}/{$file}") : null;
    }

    /**
     * Import map cho trang — cần ở CẢ dev lẫn build.
     *
     * Theme build độc lập để `@saolabs/client` là external, nên output của nó
     * giữ nguyên bare specifier. Import map trỏ specifier đó về chính entry của
     * app ⇒ hai bên dùng CHUNG một instance runtime. Không có nó thì theme kéo
     * theo bản runtime thứ hai: hai Application, hai MarkerRegistry, hydrate vỡ
     * mà không có lỗi nào. Xem docs/EXTENSION_ARCHITECTURE.md §7.
     *
     * ⚠️ Bản trước bỏ import map ở dev với lý do "Vite tự resolve bare
     * specifier". SAI: Vite chỉ viết lại import trong những module NÓ phục vụ.
     * `main.js` của theme là file tĩnh dưới `public/`, không đi qua transform
     * của Vite, nên bare specifier trong đó tới thẳng trình duyệt và chết nếu
     * không có import map. Trỏ về URL dev của entry là đủ: entry đó
     * `export *` runtime, và Vite đã resolve giúp nó rồi.
     *
     * @return array<string, string> specifier → URL
     */
    public function importMap(string $context): array
    {
        return ['@saolabs/client' => $this->entryUrl($context)];
    }

    /**
     * Thông tin build (`public/static/saola/saola.json`) — builder ghi ra.
     *
     * Dùng để đối chiếu `theme.json` của gói theme: lệch `contract` hoặc
     * `idMode` là marker id lệch, hydrate nhân đôi DOM. Xem §8.3.
     */
    public function buildInfo(): array
    {
        if ($this->buildInfo !== null) {
            return $this->buildInfo;
        }
        $path = public_path('static/saola/saola.json');
        $raw = is_file($path) ? @file_get_contents($path) : null;
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return $this->buildInfo = is_array($data) ? $data : [];
    }

    /** Manifest Vite của một context. */
    public function manifest(string $context): array
    {
        if (isset($this->manifests[$context])) {
            return $this->manifests[$context];
        }
        $path = public_path("static/saola/{$context}/manifest.json");
        $raw = is_file($path) ? @file_get_contents($path) : null;
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return $this->manifests[$context] = is_array($data) ? $data : [];
    }

    /** URL tới một file trên dev server. */
    public function devUrl(string $source): string
    {
        return ($this->devOrigin() ?? '') . '/' . ltrim($source, '/');
    }

    /** Reset cache — Octane dùng lại worker giữa các request. */
    public function reset(): void
    {
        $this->manifests = [];
        $this->buildInfo = null;
        $this->isDev = null;
    }

    protected function url(string $context, string $source, string $fallback): string
    {
        if ($this->isDev()) {
            return $this->devUrl($source);
        }
        $file = $this->manifest($context)[$source]['file'] ?? $fallback;

        return asset("static/saola/{$context}/{$file}");
    }
}
