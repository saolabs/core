# Submit saola/core lên Packagist

Repository: https://github.com/saolabs/core
Package: `saola/core` (`type: library`).
Hướng dẫn cập nhật ngày 2026-10-02.

## Kiểm tra trước khi phát hành

```bash
cd core # từ workspace saola-ecosystem
composer install
composer check
```

PHP phải đáp ứng cả manifest lẫn dependency đã khóa. Máy kiểm chứng hiện dùng PHP 8.5.1. Repo chưa có tag; chọn version đầu tiên sau khi duyệt release.

## Submit lần đầu

1. Đăng nhập [Packagist](https://packagist.org) bằng tài khoản có quyền vendor `saola`.
2. Mở [Submit](https://packagist.org/packages/submit), nhập `https://github.com/saolabs/core`, kiểm tra tên `saola/core` và submit.
3. Nếu package đã tồn tại, dùng trang package và quyền maintainer để Update.
4. Kết nối GitHub integration hoặc cấu hình webhook: payload `https://packagist.org/api/github?username=PACKAGIST_USERNAME`, content type `application/json`, secret là Packagist API token, event `push`.

## Phát hành version

Composer lấy version từ Git tag; giữ `composer.json` không có trường `version`.
Chỉ tạo tag trên commit đã kiểm tra. Ví dụ thay `1.2.3` bằng version đã chọn:

```bash
git fetch origin --tags
git status --short
git tag -a v1.2.3 -m "Release saola/core 1.2.3"
git push origin HEAD
git push origin v1.2.3
```

Branch phát triển hiện là `chore/remove-dead-hydrate`. Push branch chỉ cập nhật bản dev; stable cần tag.
Nếu chưa merge vào main, kiểm tra tag trỏ đúng commit cần phát hành.

## Kiểm tra từ project sạch

```bash
composer require saola/core:1.2.3
```

Consumer phải lấy package từ Packagist, không dùng sibling path repository.
Không đưa `vendor/`, `.env`, cache PHPUnit vào commit release.

Nguồn: [Packagist submit, tag và webhook](https://packagist.org/about), [Composer libraries](https://getcomposer.org/doc/02-libraries.md).

Lockfile của core bị Git ignore theo chính sách library. Khi check ngày 2026-10-02, lock local cũ cần `composer update league/commonmark --with-dependencies` để lấy 2.10.3 và hết advisory. Clone sạch chạy `composer install` để resolve dependency; không commit lock local vào library.
