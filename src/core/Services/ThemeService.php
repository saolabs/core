<?php

namespace Saola\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Saola\Core\Engines\ViewContextManager;

/**
 * Theme đang active của một context.
 *
 * Bốn việc, một chỗ: tra slug (qua cache), kiểm tra theme có thật, áp vào
 * request hiện tại, và bỏ áp.
 *
 * Hai ràng buộc đã đo, đừng phá:
 *
 * 1. `apply()` gọi `ViewContextManager::setContextViews()` — ghi vào
 *    `$contextViewOverrides` của một binding `scoped`. Octane xoá scoped sau mỗi
 *    request, nên PHẢI gọi lại mỗi request. Không gọi = mất theme. Dùng
 *    `registerContext()` thay thế thì ngược lại: nó ghi vào registry singleton
 *    và theme rò sang request/tenant sau.
 *
 * 2. Slug KHÔNG được memo hoá vào thuộc tính của service. Service là singleton,
 *    nên memo = admin bấm Active ở worker này, các worker còn lại vẫn phục vụ
 *    theme cũ tới lúc restart. Một lần đọc cache mỗi request là giá đúng phải
 *    trả (`setContextViews()` đo được 0.79 µs, phần đắt là I/O tra slug).
 *
 * DB là việc của ứng dụng, không phải của core: `resolveUsing()` nhận một
 * closure đọc slug khi cache trượt, `activate()` phát sự kiện để ứng dụng ghi DB.
 */
class ThemeService
{
    public const CACHE_PREFIX = 'sao.theme.';
    public const INCOMPATIBLE_CACHE_PREFIX = 'sao.theme.incompatible.';
    public const EVENT_ACTIVATED = 'sao.theme.activated';
    public const EVENT_DEACTIVATED = 'sao.theme.deactivated';

    /** @var array<string, callable(string): ?string> */
    protected array $resolvers = [];

    /**
     * Nguồn đọc slug khi cache trượt — thường là một truy vấn DB.
     *
     * @param callable(string $context): ?string $resolver
     */
    public function resolveUsing(callable $resolver, string $context = 'web'): self
    {
        $this->resolvers[$context] = $resolver;

        return $this;
    }

    /** Thư mục chứa theme, tính theo namespace Blade. */
    public function directory(): string
    {
        return trim((string) config('sao.themes.directory', 'themes'), '.');
    }

    /** Slug → base Blade, vd `storefront` → `themes.storefront`. */
    public function base(string $slug): string
    {
        return $this->directory() . '.' . $slug;
    }

    /** Slug của theme đang active, null nếu dùng base gốc của context. */
    public function active(string $context = 'web'): ?string
    {
        $key = self::CACHE_PREFIX . $context;

        if (Cache::has($key)) {
            $selection = Cache::get($key);
            $slug = is_array($selection) ? ($selection['slug'] ?? null) : $selection;

            return is_string($slug) && $slug !== '' ? $slug : null;
        }

        $resolver = $this->resolvers[$context] ?? null;
        if ($resolver === null) {
            return null;
        }

        $slug = $resolver($context);
        // Ghi cả khi rỗng: nếu không, mỗi request lại một truy vấn DB chỉ để
        // biết "chưa đặt theme nào".
        Cache::forever($key, is_string($slug) ? $slug : '');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * Theme có tồn tại không — thư mục đã compile phải nằm trong đường view.
     *
     * Kiểm ở đây là kiểm thư mục, không kiểm tên file: theme không bắt buộc
     * phải có view nào cụ thể.
     */
    public function exists(string $slug): bool
    {
        if (!$this->isValidSlug($slug)) {
            return false;
        }
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $this->base($slug));

        foreach (View::getFinder()->getPaths() as $path) {
            if (is_dir(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative)) {
                return true;
            }
        }

        return false;
    }

    /** Thư mục Blade ĐÃ CÀI của theme — nằm trong đường tìm view của Laravel. */
    public function installedViewsPath(string $slug): string
    {
        return resource_path('views' . DIRECTORY_SEPARATOR
            . str_replace('.', DIRECTORY_SEPARATOR, $this->base($slug)));
    }

    /** Thư mục Blade trong GÓI phát hành (nguồn để cài). */
    public function packageViewsPath(string $slug): string
    {
        return $this->packagePath($slug) . DIRECTORY_SEPARATOR . 'dist'
            . DIRECTORY_SEPARATOR . 'views';
    }

    /** @return list<string> Slug của mọi theme đã compile. */
    public function available(): array
    {
        $themes = [];


        foreach (View::getFinder()->getPaths() as $path) {
            $dir = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->directory();
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $themeDir) {
                $slug = basename($themeDir);
                if ($this->isValidSlug($slug)) {
                    $themes[$slug] = true;
                }
            }
        }
        $slugs = array_keys($themes);
        sort($slugs);

        return $slugs;
    }

    /**
     * Bật một theme.
     *
     * Kiểm tra ở ĐÂY, không phải lúc render: slug sai thì trang admin báo lỗi
     * ngay, thay vì mọi khách nhận 500.
     *
     * @throws \InvalidArgumentException
     */
    public function activate(string $slug, string $context = 'web'): self
    {
        if (!$this->exists($slug)) {
            throw new \InvalidArgumentException("Theme [{$slug}] chưa được compile hoặc không tồn tại.");
        }

        // InstallThemeCommand đã kiểm trước khi copy, nhưng activate() còn được
        // gọi trực tiếp từ admin/service của ứng dụng. Luôn kiểm lại tại điểm
        // đổi trạng thái để một gói cũ/thiếu manifest không thể trở thành theme
        // active rồi mới làm SSR và runtime lệch contract ở request kế tiếp.
        $check = $this->checkCompatible($slug, app(BundleManifest::class)->buildInfo(), $context);
        if (!$check['ok']) {
            throw new \InvalidArgumentException($check['reason'] ?? "Theme [{$slug}] không tương thích.");
        }

        $this->cacheSelection($slug, $context, $this->manifest($slug));
        Cache::forget(self::INCOMPATIBLE_CACHE_PREFIX . $context);
        event(self::EVENT_ACTIVATED, [$slug, $context]);

        return $this;
    }

    /** Trở về base gốc của context. */
    public function deactivate(string $context = 'web'): self
    {
        Cache::forever(self::CACHE_PREFIX . $context, '');
        Cache::forget(self::INCOMPATIBLE_CACHE_PREFIX . $context);
        event(self::EVENT_DEACTIVATED, [$context]);

        return $this;
    }

    /** Quên slug đã cache, buộc lần tra sau đi lại resolver. */
    public function forget(string $context = 'web'): self
    {
        Cache::forget(self::CACHE_PREFIX . $context);

        return $this;
    }

    /**
     * Áp theme cho request hiện tại. Gọi mỗi request — xem ghi chú đầu lớp.
     *
     * `apply()` là CHỦ SỞ HỮU override của context đó: gọi xong thì override
     * luôn phản ánh theme đang active, kể cả khi "đang active" nghĩa là không
     * có theme nào. Không xoá ở nhánh không-có-theme thì trong tiến trình sống
     * lâu (tinker, queue worker, Octane task) theme vừa tắt vẫn còn hiệu lực.
     *
     * @return bool đã áp một theme hay không
     */
    public function apply(string $context = 'web'): bool
    {
        $manager = app(ViewContextManager::class);
        if (!$manager->hasContext($context)) {
            return false;
        }

        $slug = $this->active($context);
        if ($slug === null) {
            $manager->clearContextViews($context);

            return false;
        }

        // Theme biến mất sau khi đã active (bị xoá, deploy hụt) thì rơi về base
        // gốc thay vì để mọi view 500.
        if (!$this->exists($slug)) {
            $manager->clearContextViews($context);

            return false;
        }

        // App có thể đã được deploy sang contract/idMode mới trong khi cache/DB
        // vẫn giữ theme của bản trước. Selection mới mang metadata để đường nóng
        // chỉ so giá trị; selection chuỗi của bản cũ được kiểm đầy đủ rồi nâng cấp.
        $check = $this->checkActiveCompatible($slug, $context);
        if (!$check['ok']) {
            $manager->clearContextViews($context);
            $reportKey = self::INCOMPATIBLE_CACHE_PREFIX . $context;
            if (!Cache::has($reportKey)) {
                Cache::put($reportKey, true, now()->addHour());
                report(new \RuntimeException($check['reason'] ?? "Theme [{$slug}] không tương thích."));
            }

            return false;
        }

        $manager->setContextViews($context, $this->base($slug));

        return true;
    }

    /**
     * Manifest phát hành của một theme (`theme.json` do builder đóng dấu).
     *
     * @return array<string, mixed> rỗng nếu theme không có / không đọc được
     */
    public function manifest(string $slug): array
    {
        if (!$this->isValidSlug($slug)) {
            return [];
        }
        // Builder ghi vào `dist/` cùng chỗ với views/ và public/ — cả ba đều là
        // sản phẩm build, gốc gói chỉ giữ nguồn và sao.config.json.
        $path = $this->packagePath($slug) . DIRECTORY_SEPARATOR . 'dist'
            . DIRECTORY_SEPARATOR . 'theme.json';
        $raw = is_file($path) ? @file_get_contents($path) : null;
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : [];
    }

    /** Thư mục gói theme đã cài. */
    public function packagePath(string $slug): string
    {
        $base = (string) config('sao.themes.path', base_path('themes'));

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug;
    }

    /**
     * URL js/css của theme đang active — thứ server phát cho client nạp.
     *
     * `?v={revision}` để đổi theme là đổi URL: không có nó thì trình duyệt vẫn
     * dùng bản cache của theme cũ sau khi admin bấm Active.
     *
     * @return array{js: ?string, css: ?string}
     */
    public function assets(string $slug, string $context = 'web'): array
    {
        if (!$this->isValidSlug($slug)) {
            return ['js' => null, 'css' => null];
        }

        $manifest = $this->manifest($slug);
        $revision = (string) ($manifest['revision'] ?? '');
        $query = $revision !== '' ? '?v=' . $revision : '';
        $base = "static/saola/themes/{$slug}";

        $public = public_path("static/saola/themes/{$slug}");
        $js = is_file($public . '/main.js') ? asset("{$base}/main.js") . $query : null;
        $css = is_file($public . '/main.css') ? asset("{$base}/main.css") . $query : null;

        // Gói CÓ bundle mà bản publish không đọc được = cài hỏng (symlink tuyệt
        // đối, thiếu bước link, quyền). Im lặng là kịch bản tệ nhất: SSR ra HTML
        // của theme còn client rơi về view base → hydrate lệch, không lỗi nào.
        if ($js === null && is_file($this->packagePath($slug) . '/dist/public/main.js')) {
            report(new \RuntimeException(
                "Theme [{$slug}] có dist/public/main.js nhưng không truy cập được qua {$public}/main.js — "
                . 'chạy lại `php artisan saola:theme:install ' . $slug . ' --force`.'
            ));
        }

        return ['js' => $js, 'css' => $css];
    }

    /**
     * Theme có tương thích với bản app đang chạy không.
     *
     * Kiểm `contract` và `idMode`: lệch là marker id lệch, hydrate không claim
     * được DOM server gửi và **nhân đôi DOM** — không exception, không log. Nên
     * phải chặn ở đây chứ không để lộ ra lúc render.
     *
     * @param array<string, mixed> $buildInfo nội dung `saola.json` của app
     * @return array{ok: bool, reason: ?string}
     */
    public function checkCompatible(string $slug, array $buildInfo, ?string $expectedContext = null): array
    {
        $manifest = $this->manifest($slug);
        if ($manifest === []) {
            return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu theme.json."];
        }

        $appContract = $buildInfo['contract'] ?? null;
        $themeContract = $manifest['contract'] ?? null;
        $appContractValue = $this->contractValue($appContract);
        $themeContractValue = $this->contractValue($themeContract);
        if ($appContractValue === null) {
            return ['ok' => false, 'reason' => 'Ứng dụng thiếu contract trong public/static/saola/saola.json.'];
        }
        if ($themeContractValue === null) {
            return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu contract trong theme.json."];
        }
        if ($themeContractValue !== $appContractValue) {
            return ['ok' => false, 'reason' =>
                "Theme [{$slug}] build cho contract {$themeContract}, app đang chạy contract {$appContract}."];
        }

        $appIdMode = $buildInfo['idMode'] ?? null;
        $themeIdMode = $manifest['idMode'] ?? null;
        // Allowlist, KHÔNG phó thác cho compiler: IdMode::fromString() cố ý rơi
        // về md5 với mọi giá trị lạ, nên "terser" gõ nhầm sẽ compile im lặng ra
        // id md5 và lệch hoàn toàn với app.
        if (!in_array($appIdMode, ['terse', 'compact', 'md5', 'raw'], true)) {
            return ['ok' => false, 'reason' => 'Ứng dụng thiếu hoặc khai idMode không hợp lệ trong public/static/saola/saola.json: '
                . var_export($appIdMode, true)];
        }
        if (!in_array($themeIdMode, ['terse', 'compact', 'md5', 'raw'], true)) {
            return ['ok' => false, 'reason' => "Theme [{$slug}] khai idMode không hợp lệ: " . var_export($themeIdMode, true)];
        }
        if ($themeIdMode !== $appIdMode) {
            return ['ok' => false, 'reason' =>
                "Theme [{$slug}] build với idMode [{$themeIdMode}], app đang chạy [{$appIdMode}]."];
        }

        $revision = $manifest['revision'] ?? null;
        if (!is_string($revision) || trim($revision) === '') {
            return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu revision hợp lệ trong theme.json."];
        }

        $themeContext = $manifest['context'] ?? null;
        if (!is_string($themeContext) || trim($themeContext) === '') {
            return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu context hợp lệ trong theme.json."];
        }
        if ($expectedContext !== null && $themeContext !== $expectedContext) {
            return ['ok' => false, 'reason' =>
                "Theme [{$slug}] dành cho context [{$themeContext}], không thể kích hoạt cho [{$expectedContext}]."];
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * Kiểm selection đang active mà không đọc lại manifest theme ở mọi request.
     * Cache dạng chuỗi từ bản cũ vẫn được chấp nhận một lần rồi nâng cấp tại chỗ.
     *
     * @return array{ok: bool, reason: ?string}
     */
    protected function checkActiveCompatible(string $slug, string $context): array
    {
        $buildInfo = app(BundleManifest::class)->buildInfo();
        $selection = Cache::get(self::CACHE_PREFIX . $context);

        if (is_array($selection) && ($selection['slug'] ?? null) === $slug) {
            $appContract = $buildInfo['contract'] ?? null;
            $appIdMode = $buildInfo['idMode'] ?? null;
            $themeContract = $selection['contract'] ?? null;
            $themeIdMode = $selection['idMode'] ?? null;
            $revision = $selection['revision'] ?? null;
            $themeContext = $selection['context'] ?? null;
            $appContractValue = $this->contractValue($appContract);
            $themeContractValue = $this->contractValue($themeContract);

            if ($appContractValue === null) {
                return ['ok' => false, 'reason' => 'Ứng dụng thiếu contract trong public/static/saola/saola.json.'];
            }
            if ($themeContractValue === null) {
                return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu contract hợp lệ trong cache activation."];
            }
            if ($themeContractValue !== $appContractValue) {
                return ['ok' => false, 'reason' =>
                    "Theme [{$slug}] build cho contract {$themeContract}, app đang chạy contract {$appContract}."];
            }
            if (!in_array($appIdMode, ['terse', 'compact', 'md5', 'raw'], true)
                || $themeIdMode !== $appIdMode) {
                return ['ok' => false, 'reason' =>
                    "Theme [{$slug}] build với idMode [{$themeIdMode}], app đang chạy [{$appIdMode}]."];
            }
            if (!is_string($revision) || trim($revision) === '') {
                return ['ok' => false, 'reason' => "Theme [{$slug}] thiếu revision hợp lệ trong cache activation."];
            }
            if ($themeContext !== $context) {
                return ['ok' => false, 'reason' =>
                    "Theme [{$slug}] dành cho context [{$themeContext}], không thể áp cho [{$context}]."];
            }

            return ['ok' => true, 'reason' => null];
        }

        $check = $this->checkCompatible($slug, $buildInfo, $context);
        if ($check['ok']) {
            $this->cacheSelection($slug, $context, $this->manifest($slug));
        }

        return $check;
    }

    /** @param array<string, mixed> $manifest */
    protected function cacheSelection(string $slug, string $context, array $manifest): void
    {
        Cache::forever(self::CACHE_PREFIX . $context, [
            'slug' => $slug,
            'contract' => $manifest['contract'] ?? null,
            'idMode' => $manifest['idMode'] ?? null,
            'revision' => $manifest['revision'] ?? null,
            'context' => $manifest['context'] ?? null,
        ]);
    }

    protected function contractValue(mixed $value): ?string
    {
        if (is_int($value) && $value >= 0) {
            return (string) $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    protected function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $slug) === 1;
    }
}
