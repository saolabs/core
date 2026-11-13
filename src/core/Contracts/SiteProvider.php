<?php

namespace Saola\Core\Contracts;

/**
 * Nguồn của biến hệ thống `__site`: các nhóm thiết lập CÔNG KHAI của website
 * (thông tin chung, branding, seo, pwa…), có mặt ở mọi view của mọi context.
 *
 * Đăng ký MỘT lần lúc boot (`ViewContextManager::provideSite()`); instance sống
 * cả worker Octane nên không được giữ state của request. Cả hai method chạy ở
 * mọi request và phải rẻ: tự cache, không query mỗi lần.
 */
interface SiteProvider
{
    /** Đổi mỗi khi dữ liệu đổi (admin lưu thiết lập). Đi vào revision view context. */
    public function version(): string;

    /**
     * Dữ liệu đã giải theo `$locale`, gom theo nhóm: `['general' => [...], 'branding' => [...]]`.
     * Đi nguyên văn ra HTML và client: chỉ dữ liệu công khai.
     */
    public function data(string $locale): array;
}
