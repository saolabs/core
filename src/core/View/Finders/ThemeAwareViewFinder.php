<?php

namespace Saola\Core\View\Finders;

use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Saola\Core\Engines\ViewContextManager;

/**
 * View finder biết rơi từ theme về base gốc.
 *
 * Không có lớp này thì theme phải là bản sao ĐẦY ĐỦ của context: `__layout__`,
 * `__partial__`, `__module__`… là biến của cả context, nên `@extends`/`@include`
 * trong bất kỳ view nào cũng trỏ vào `themes.{slug}.*`. Thiếu một file — kể cả
 * `partials/head` mà theme chẳng có lý do gì để đổi — là cả trang 500.
 *
 * Đặt ở tầng finder vì đó là cửa DUY NHẤT mọi đường đi qua: `@extends`,
 * `@include`, `view()`, và cả `view()->exists()` mà routeToViewPathConfig dùng
 * để dò. Vá ở từng chỗ gọi thì chắc chắn sót.
 */
class ThemeAwareViewFinder extends FileViewFinder
{
    /**
     * Tên view của theme → file của base gốc.
     *
     * Giữ RIÊNG, không trộn vào `$this->views` của lớp cha: `$this->views` là
     * "tên này có file thật", và `existsWithoutFallback()` dựa vào đúng nghĩa đó
     * để trả lời câu hỏi của phía client.
     *
     * @var array<string,string>
     */
    protected array $themeFallbacks = [];

    public function find($name)
    {
        if (isset($this->themeFallbacks[$name])) {
            return $this->themeFallbacks[$name];
        }

        try {
            return parent::find($name);
        } catch (InvalidArgumentException $e) {
            $fallback = $this->fallbackFor($name);
            if ($fallback === null) {
                throw $e;
            }

            return $this->themeFallbacks[$name] = parent::find($fallback);
        }
    }

    /**
     * Theme có file THẬT cho tên này không — bỏ qua fallback.
     *
     * Cần cho phía client: registry JS chỉ có khoá của những view theme thật sự
     * mang. Trả khoá theme cho một view mà theme không đè thì SSR ra HTML của
     * base còn client không tìm thấy view để hydrate.
     */
    public function existsWithoutFallback(string $name): bool
    {
        if (isset($this->views[$name])) {
            return true;
        }

        try {
            parent::find($name);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function flush()
    {
        $this->themeFallbacks = [];

        parent::flush();
    }

    protected function fallbackFor(string $name): ?string
    {
        if (!app()->bound(ViewContextManager::class)) {
            return null;
        }

        return app(ViewContextManager::class)->fallbackViewName($name);
    }
}
