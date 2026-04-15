<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Saola\Core\View\Compilers\SmartBladeCompiler;

class CompileBladeViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'views:compile {scope} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile Blade views to SPA JavaScript templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scope = $this->argument('scope');
        $path = $this->argument('path');

        $this->info("🔍 Scanning blade views in: {$path}");
        $this->info("📦 Scope: {$scope}");

        // Tìm tất cả file blade trong thư mục
        $bladeFiles = $this->findBladeFiles($path);
        $this->info("📁 Tìm thấy " . count($bladeFiles) . " file blade");

        if (empty($bladeFiles)) {
            $this->error("❌ Không tìm thấy file blade nào trong {$path}");
            return 1;
        }

        // Biên dịch từng file
        $templates = [];
        $progressBar = $this->output->createProgressBar(count($bladeFiles));
        $progressBar->start();

        foreach ($bladeFiles as $file) {
            $relativePath = str_replace($path . '/', '', $file);
            $templateName = str_replace('.blade.php', '', $relativePath);
            
            $bladeContent = File::get($file);
            $jsTemplate = $this->convertBladeToJs($bladeContent);
            
            $templates[$templateName] = $jsTemplate;
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Tạo file output
        $outputPath = 'public/build/views.js';
        $jsOutput = $this->generateJsOutput($scope, $templates);
        
        // Đảm bảo thư mục tồn tại
        File::makeDirectory(dirname($outputPath), 0755, true, true);
        File::put($outputPath, $jsOutput);

        $this->info("✅ Đã biên dịch " . count($templates) . " template thành công!");
        $this->info("📁 File output: {$outputPath}");

        return 0;
    }

    /**
     * Tìm tất cả file blade trong thư mục
     */
    private function findBladeFiles(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $files = [];
        $items = File::allFiles($path);

        foreach ($items as $item) {
            if ($item->getExtension() === 'php' && str_contains($item->getFilename(), '.blade.php')) {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }

    /**
     * Convert Blade template to JavaScript using smart compiler
     */
    private function convertBladeToJs(string $bladeContent): string
    {
        // Use the new smart compiler
        $compiler = new SmartBladeCompiler();
        $jsTemplate = $compiler->compile($bladeContent);
        
        // Handle @vars directive for function parameters
        $jsTemplate = $this->handleVarsDirective($bladeContent, $jsTemplate);
        
        return $jsTemplate;
    }

    /**
     * Handle @vars directive to create function parameters
     */
    private function handleVarsDirective(string $originalContent, string $jsTemplate): string
    {
        // Extract @vars directive
        if (preg_match('/@vars\s*\(\s*([^)]+)\s*\)/', $originalContent, $matches)) {
            $varsString = $matches[1];
            $vars = $this->parseVarsString($varsString);
            
            // Create destructuring line
            $destructuring = $this->createDestructuring($vars);
            
            // Add destructuring to function
            $jsTemplate = preg_replace('/function\s*\(\s*__data\s*=\s*\{\s*\}\s*\)\s*\{/', 
                "function(__data = {}) {\n        let {$destructuring} = __data;", 
                $jsTemplate);
        }
        
        return $jsTemplate;
    }

    /**
     * Parse vars string like "name, age = 25, city = 'New York'"
     */
    private function parseVarsString(string $varsString): array
    {
        $vars = [];
        $parts = explode(',', $varsString);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($name, $default) = explode('=', $part, 2);
                $vars[trim($name)] = trim($default);
            } else {
                $vars[trim($part)] = null;
            }
        }
        
        return $vars;
    }

    /**
     * Create destructuring string like "{name, age = 25, city = 'New York'}"
     */
    private function createDestructuring(array $vars): string
    {
        $destructuring = [];
        foreach ($vars as $name => $default) {
            if ($default !== null) {
                $destructuring[] = "{$name} = {$default}";
            } else {
                $destructuring[] = $name;
            }
        }
        
        return '{' . implode(', ', $destructuring) . '}';
    }

    /**
     * Generate JavaScript output with SPA object and helper functions
     */
    private function generateJsOutput(string $scope, array $templates): string
    {
        $js = "// Generated at: " . now()->toDateTimeString() . "\n\n";
        
        // SPA global object
        $js .= "window.SPA = {\n";
        
        // Helper functions
        $js .= "    // Helper functions for PHP functions\n";
        $js .= "    count: (arr) => Array.isArray(arr) ? arr.length : 0,\n";
        $js .= "    isset: (val) => val !== undefined && val !== null,\n";
        $js .= "    empty: (val) => !val || (Array.isArray(arr) && arr.length === 0),\n";
        $js .= "    routeExists: (name) => true, // Placeholder\n";
        $js .= "    fileExists: (path) => true, // Placeholder\n";
        $js .= "    strReplace: (search, replace, subject) => subject.replace(new RegExp(search, 'g'), replace),\n";
        $js .= "    appLocale: () => 'en', // Placeholder\n";
        $js .= "    date: (format, timestamp) => new Date(timestamp || Date.now()).toLocaleDateString(),\n";
        $js .= "    config: (key) => null, // Placeholder\n";
        $js .= "    route: (name, params) => `/\${name}`, // Placeholder\n";
        $js .= "    url: (path) => path, // Placeholder\n";
        $js .= "    now: () => new Date().toISOString(),\n";
        $js .= "    json_encode: (data) => JSON.stringify(data),\n\n";
        
        // Template engine functions
        $js .= "    // Template engine functions\n";
        $js .= "    foreach: (array, callback) => {\n";
        $js .= "        if (!Array.isArray(array)) return '';\n";
        $js .= "        return array.map(callback).join('');\n";
        $js .= "    },\n\n";
        
        $js .= "    execute: (fn) => {\n";
        $js .= "        try {\n";
        $js .= "            return fn();\n";
        $js .= "        } catch (error) {\n";
        $js .= "            console.error('Template execution error:', error);\n";
        $js .= "            return '';\n";
        $js .= "        }\n";
        $js .= "    },\n\n";
        
        // Views object
        $js .= "    views: {\n";
        $js .= "        {$scope}: {\n";
        
        foreach ($templates as $templateName => $template) {
            $js .= "            '{$templateName}': function(__data = {}) {\n";
            $js .= "                return `{$template}`;\n";
            $js .= "            },\n";
        }
        
        $js .= "        }\n";
        $js .= "    }\n";
        $js .= "};\n";
        
        return $js;
    }
}
