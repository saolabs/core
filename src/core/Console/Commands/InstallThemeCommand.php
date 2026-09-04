<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Saola\Core\Services\BundleManifest;
use Saola\Core\Services\ThemeService;

/**
 * Cài một gói theme đã build vào ứng dụng.
 *
 * Gói nằm ở `themes/{slug}/` với `dist/` là toàn bộ đầu ra build. Cài = đưa hai
 * nửa của nó về đúng địa hạt:
 *
 *   dist/views/**     →  resources/views/themes/{slug}/   (copy)
 *   dist/public/*     →  public/static/saola/themes/{slug}/ (symlink)
 *
 * Blade phải COPY vào cây view của Laravel chứ không phục vụ tại chỗ: tên view
 * `themes.{slug}.*` được finder resolve theo đường tìm mặc định, nên đặt đúng
 * chỗ là không phải dạy finder thêm gì cả.
 *
 * `dist/public` thì SYMLINK chứ không copy: nó là thứ trình duyệt tải, và
 * symlink cho phép `?v={revision}` đổi theo bản build của theme mà không phải
 * đồng bộ lại thư mục.
 *
 * ⚠️ Đích của cả hai đều NGOÀI outDir của Vite: `emptyOutDir` xoá sạch
 * `public/static/saola/{ctx}/` mỗi lần build và sẽ cuốn theo theme của khách
 * hàng nếu đặt nhầm chỗ.
 */
class InstallThemeCommand extends Command
{
    protected $signature = 'saola:theme:install {slug : Slug của gói theme trong thư mục themes/}
                            {--force : Ghi đè Blade đã cài}
                            {--activate= : Kích hoạt luôn cho context này sau khi cài}';

    protected $description = 'Cài gói theme đã build: copy Blade + link asset';

    public function handle(ThemeService $themes, BundleManifest $bundles): int
    {
        $slug = (string) $this->argument('slug');

        $package = $themes->packagePath($slug);
        if (!is_dir($package)) {
            $this->error("Không thấy gói theme: {$package}");

            return self::FAILURE;
        }

        // Kiểm hợp đồng TRƯỚC khi động vào bất cứ file nào: contract/idMode lệch
        // là marker id lệch, hydrate nhân đôi DOM mà không có lỗi nào phát ra.
        $check = $themes->checkCompatible($slug, $bundles->buildInfo());
        if (!$check['ok']) {
            $this->error($check['reason']);

            return self::FAILURE;
        }

        $source = $themes->packageViewsPath($slug);
        $target = $themes->installedViewsPath($slug);

        if (!is_dir($source)) {
            $this->error("Gói thiếu thư mục Blade đã build: {$source}");

            return self::FAILURE;
        }
        if (is_dir($target) && !$this->option('force')) {
            $this->error("Theme [{$slug}] đã cài ở {$target}. Dùng --force để ghi đè.");

            return self::FAILURE;
        }

        $copied = $this->copyTree($source, $target);
        $this->info("✓ Blade: {$copied} file → {$target}");

        $linked = $this->linkPublic($themes, $slug);
        $this->info($linked ? "✓ Asset: đã link → {$linked}" : '⚠ Gói không có dist/public, bỏ qua asset');

        $manifest = $themes->manifest($slug);
        $this->line("  contract={$manifest['contract']} idMode={$manifest['idMode']} revision={$manifest['revision']}");

        if ($context = $this->option('activate')) {
            $themes->activate($slug, (string) $context);
            $this->info("✓ Đã kích hoạt [{$slug}] cho context [{$context}]");
        }

        return self::SUCCESS;
    }

    /**
     * Copy đệ quy bằng copyFileSync tương đương.
     *
     * KHÔNG dùng `File::copyDirectory()` với `rmSync` cây đích trước: trên
     * bind-mount của Docker Desktop, xoá/copy đệ quy ném EACCES kể cả khi chạy
     * root. mkdir + copy từng file thì không.
     */
    protected function copyTree(string $from, string $to): int
    {
        $count = 0;
        if (!is_dir($to) && !@mkdir($to, 0775, true) && !is_dir($to)) {
            throw new \RuntimeException("Không tạo được thư mục: {$to}");
        }

        foreach (scandir($from) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $src = $from . DIRECTORY_SEPARATOR . $entry;
            $dst = $to . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($src)) {
                $count += $this->copyTree($src, $dst);
            } elseif (@copy($src, $dst)) {
                $count++;
            } else {
                $this->warn("  bỏ qua (không copy được): {$src}");
            }
        }

        return $count;
    }

    /** @return string|null đường đích nếu link được */
    protected function linkPublic(ThemeService $themes, string $slug): ?string
    {
        $source = $themes->packagePath($slug) . DIRECTORY_SEPARATOR . 'dist'
            . DIRECTORY_SEPARATOR . 'public';
        if (!is_dir($source)) {
            return null;
        }

        $root = (string) config('sao.themes.public_path', public_path('static/saola/themes'));
        if (!is_dir($root) && !@mkdir($root, 0775, true) && !is_dir($root)) {
            throw new \RuntimeException("Không tạo được thư mục: {$root}");
        }

        $target = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug;
        if (is_link($target)) {
            @unlink($target);
        }
        if (is_dir($target)) {
            $this->warn("  {$target} là thư mục thật, không ghi đè bằng symlink");

            return null;
        }

        // Symlink TƯƠNG ĐỐI, không tuyệt đối. Đường tuyệt đối của máy build vô
        // nghĩa ở mọi nơi khác: trong container thì gốc dự án là /workspace,
        // trên máy chủ thật lại là /var/www — link trỏ ra ngoài hư không và
        // `is_file()` trả false, nên server im lặng KHÔNG phát URL bundle của
        // theme. Hậu quả: SSR ra HTML của theme còn client rơi về view base →
        // hydrate lệch, không lỗi nào.
        @symlink($this->relativePath(dirname($target), $source), $target);

        return $target;
    }

    /** Đường từ $from tới $to, dạng tương đối. */
    protected function relativePath(string $from, string $to): string
    {
        $fromParts = explode(DIRECTORY_SEPARATOR, trim($from, DIRECTORY_SEPARATOR));
        $toParts = explode(DIRECTORY_SEPARATOR, trim($to, DIRECTORY_SEPARATOR));

        while ($fromParts && $toParts && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        return implode(DIRECTORY_SEPARATOR, array_merge(
            array_fill(0, count($fromParts), '..'),
            $toParts
        ));
    }
}
