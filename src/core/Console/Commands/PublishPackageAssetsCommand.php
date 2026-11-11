<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Saola\Core\Services\PackageAssets;

/**
 * Chép bundle client của các package (`extra.saola.client`) ra public.
 *
 * Chạy sau mỗi `composer install/update` — gắn vào `post-autoload-dump` của app:
 *     "@php artisan saola:assets"
 */
class PublishPackageAssetsCommand extends Command
{
    protected $signature = 'saola:assets';

    protected $description = 'Publish client bundles declared by packages (extra.saola.client) to public/';

    public function handle(PackageAssets $assets): int
    {
        $copied = $assets->publish();
        foreach ($copied as $source => $target) {
            $this->line("  {$source} → {$target}");
        }
        $this->info(count($copied) . ' bundle client đã publish.');

        return self::SUCCESS;
    }
}
