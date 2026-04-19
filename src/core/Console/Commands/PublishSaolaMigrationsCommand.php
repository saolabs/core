<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishSaolaMigrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sao:publish-migrations {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Saola Core migrations to your application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourcePath = __DIR__ . '/../../../../database/migrations';
        $destinationPath = base_path('database/migrations');

        if (!File::exists($sourcePath)) {
            $this->error('Migrations source directory not found!');
            return 1;
        }

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $files = File::files($sourcePath);
        $publishedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $destination = $destinationPath . '/' . $filename;

            if (File::exists($destination) && !$this->option('force')) {
                $this->warn("Migration {$filename} already exists. Use --force to overwrite.");
                continue;
            }

            File::copy($file->getPathname(), $destination);
            $this->info("Published: {$filename}");
            $publishedCount++;
        }

        if ($publishedCount > 0) {
            $this->info("Successfully published {$publishedCount} migration(s)!");
            $this->info("Run 'php artisan migrate' to execute the migrations.");
        } else {
            $this->info("No migrations were published.");
        }

        return 0;
    }
} 