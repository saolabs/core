<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Saola\Core\View\Compilers\BladeToSpaCompiler;

use Symfony\Component\Finder\Finder;

class CompileViews extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'one:compile 
                            {context? : The context to compile (web, admin, api, or all)}
                            {--watch : Watch for file changes}
                            {--force : Force recompilation}
                            {--minify : Minify output}
                            {--sourcemap : Generate source maps}';

    /**
     * Backward-compatible aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['saola:compile'];

    /**
     * The console command description.
     */
    protected $description = 'Compile Blade views to TypeScript for Saola';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $context = $this->argument('context') ?? 'all';
        $watch = $this->option('watch');
        $force = $this->option('force');
        
        $this->info('Saola compiler');
        $this->newLine();
        
        if ($watch) {
            $this->watchMode($context);
        } else {
            $this->compileOnce($context, $force);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Compile once
     */
    protected function compileOnce(string $context, bool $force): void
    {
        $config = config('saola.compiler', config('one.compiler', []));
        $contexts = $context === 'all' 
            ? array_keys($config)
            : [$context];
        
        $totalFiles = 0;
        $totalTime = 0;
        
        foreach ($contexts as $ctx) {
            if (!isset($config[$ctx])) {
                $this->error("Context '{$ctx}' not found in config");
                continue;
            }
            
            $this->info("📦 Compiling context: {$ctx}");
            
            $ctxConfig = $config[$ctx];
            
            // Clean output directory first
            $this->cleanOutputDirectory($ctxConfig['output']);
            
            $compiler = new BladeToSpaCompiler();
            
            // Find all blade files
            $files = $this->findBladeFiles($ctxConfig['views']);
            $bar = $this->output->createProgressBar(count($files));
            $bar->start();
            
            $compiled = 0;
            $errors = 0;
            $viewRegistry = []; // Track compiled views for registry
            
            foreach ($files as $file) {
                try {
                    $relativePath = $this->getRelativePath($file->getPathname(), $ctxConfig['views']);
                    $outputPath = $this->getOutputPath($relativePath, $ctxConfig['output']);
                    
                    // Skip if not changed (unless force)
                    if (!$force && $this->isUpToDate($file->getPathname(), $outputPath)) {
                        // Still add to registry even if skipped
                        $this->addToRegistry($viewRegistry, $relativePath, $outputPath, $ctxConfig['output']);
                        $bar->advance();
                        continue;
                    }
                    
                    // Compile
                    $result = $this->compileBladeFile($compiler, $file->getPathname());
                    
                    // Write output
                    $this->writeOutput($outputPath, $result['code']);
                    
                    // Add to registry
                    $this->addToRegistry($viewRegistry, $relativePath, $outputPath, $ctxConfig['output']);
                    
                    $compiled++;
                    $totalTime += $result['compilationTime'];
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("  ✗ {$file->getFilename()}: {$e->getMessage()}");
                }
            
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
            
            // Generate view registry after all files compiled
            $this->generateViewRegistry($ctx, $viewRegistry, $ctxConfig['output']);
            
            $this->info("  ✓ Compiled: {$compiled}");
            if ($errors > 0) {
                $this->error("  ✗ Errors: {$errors}");
            }
            
            $totalFiles += $compiled;
        }
        
        $this->newLine();
        $this->info("✨ Done! Compiled {$totalFiles} files in " . round($totalTime * 1000, 2) . "ms");
    }
    
    /**
     * Watch mode
     */
    protected function watchMode(string $context): void
    {
        $this->info("👀 Watching for changes... (Ctrl+C to stop)");
        $this->newLine();
        
        $fileHashes = [];
        
        while (true) {
            usleep(1000000); // 1 second
            
            $config = config('saola.compiler', config('one.compiler', config('sao.compiler', [])));
            $contexts = $context === 'all' 
                ? array_keys($config)
                : [$context];
            
            foreach ($contexts as $ctx) {
                if (!isset($config[$ctx])) continue;
                
                $ctxConfig = $config[$ctx];
                $files = $this->findBladeFiles($ctxConfig['views']);
                
                foreach ($files as $file) {
                    $path = $file->getPathname();
                    $hash = md5_file($path);
                    
                    if (!isset($fileHashes[$path]) || $fileHashes[$path] !== $hash) {
                        $fileHashes[$path] = $hash;
                        
                        $this->info("[" . date('H:i:s') . "] Compiling: {$file->getFilename()}");
                        
                        try {
                            $compiler = new BladeToSpaCompiler();
                            $result = $this->compileBladeFile($compiler, $path);
                            
                            $relativePath = $this->getRelativePath($path, $ctxConfig['views']);
                            $outputPath = $this->getOutputPath($relativePath, $ctxConfig['output']);
                            $this->writeOutput($outputPath, $result['code']);
                            
                            $this->info("  ✓ Done in " . round($result['compilationTime'] * 1000, 2) . "ms");
                        } catch (\Exception $e) {
                            $this->error("  ✗ Error: {$e->getMessage()}");
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Find all blade files
     */
    protected function findBladeFiles(array $directories): Finder
    {
        $finder = new Finder();
        $finder->files()->in($directories)->name('*.blade.php');
        
        return $finder;
    }
    
    /**
     * Get relative path
     */
    protected function getRelativePath(string $file, array $directories): array
    {
        foreach ($directories as $dir) {
            if (str_starts_with($file, $dir)) {
                $relative = str_replace($dir . DIRECTORY_SEPARATOR, '', $file);
                
                // Determine which source directory this file came from
                $sourceType = $this->getSourceType($dir);
                
                return ['path' => $relative, 'source' => $sourceType];
            }
        }
        
        return ['path' => basename($file), 'source' => 'unknown'];
    }
    
    /**
     * Get source type from directory path
     */
    protected function getSourceType(string $dir): string
    {
        // Extract the last directory name
        $parts = explode(DIRECTORY_SEPARATOR, rtrim($dir, DIRECTORY_SEPARATOR));
        $lastDir = end($parts);
        
        // If it's a special directory like _system, return it
        if (str_starts_with($lastDir, '_')) {
            return $lastDir;
        }
        
        // Otherwise return the directory name (web, admin, etc.)
        return $lastDir;
    }
    
    /**
     * Get output path
     */
    protected function getOutputPath(string|array $relativePath, string $outputDir): string
    {
        // Handle both old format (string) and new format (array)
        if (is_array($relativePath)) {
            $path = $relativePath['path'];
            $source = $relativePath['source'];
        } else {
            $path = $relativePath;
            $source = null;
        }
        
        // Convert .blade.php to .ts
        $path = str_replace('.blade.php', '.ts', $path);
        
        // If source is _system or other special directory, preserve it in output
        if ($source && str_starts_with($source, '_')) {
            // Get parent of output dir (ts/onejs/views)
            $baseOutputDir = dirname($outputDir);
            return $baseOutputDir . DIRECTORY_SEPARATOR . $source . DIRECTORY_SEPARATOR . $path;
        }
        
        // Normal case: output to context directory
        return $outputDir . DIRECTORY_SEPARATOR . $path;
    }
    
    /**
     * Write output
     */
    protected function writeOutput(string $path, string $content): void
    {
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, $content);
    }

    /**
     * Compile a Blade file with the available compiler implementation.
     *
     * @return array{code: string, compilationTime: float}
     */
    protected function compileBladeFile(BladeToSpaCompiler $compiler, string $path): array
    {
        $startedAt = microtime(true);
        $bladeContent = File::get($path);

        return [
            'code' => $compiler->compile($bladeContent),
            'compilationTime' => microtime(true) - $startedAt,
        ];
    }
    
    /**
     * Check if output is up to date
     */
    protected function isUpToDate(string $sourcePath, string $outputPath): bool
    {
        if (!file_exists($outputPath)) {
            return false;
        }
        
        return filemtime($sourcePath) <= filemtime($outputPath);
    }
    
    /**
     * Clean output directory
     */
    protected function cleanOutputDirectory(string $outputDir): void
    {
        if (!is_dir($outputDir)) {
            return;
        }
        
        // Remove all .ts and .ts.map files recursively (but keep registry files)
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($outputDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                // Skip registry files
                if (str_starts_with($filename, 'registry.') || str_starts_with($filename, 'index.')) {
                    continue;
                }
                if (str_ends_with($filename, '.ts') || str_ends_with($filename, '.ts.map')) {
                    unlink($file->getPathname());
                }
            }
        }
        
        // Remove empty directories
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            }
        }
    }
    
    /**
     * Add view to registry
     */
    protected function addToRegistry(array &$registry, array $relativePath, string $outputPath, string $outputDir): void
    {
        $path = $relativePath['path'];
        $source = $relativePath['source'];
        
        // Convert file path to blade name (remove .blade.php and use dot notation)
        $bladeName = str_replace('.blade.php', '', $path);
        $bladeName = str_replace('/', '.', $bladeName);
        $bladeName = str_replace('\\', '.', $bladeName);
        
        // Get import path relative to views base directory
        $viewsBase = dirname($outputDir);
        $importPath = str_replace($viewsBase . DIRECTORY_SEPARATOR, '', $outputPath);
        $importPath = str_replace('.ts', '', $importPath);
        $importPath = str_replace('\\', '/', $importPath);
        
        // Group by source
        if (!isset($registry[$source])) {
            $registry[$source] = [];
        }
        
        $registry[$source][$bladeName] = $importPath;
    }
    
    /**
     * Generate view registry file
     */
    protected function generateViewRegistry(string $context, array $registry, string $outputDir): void
    {
        $baseDir = dirname($outputDir); // Get views base directory
        $registryPath = $baseDir . DIRECTORY_SEPARATOR . "registry.{$context}.ts";
        // Filter registry: only include current context and _system (if context != _system)
        $filteredRegistry = [];
        foreach ($registry as $source => $views) {
            // Include context-specific views
            if ($source === $context) {
                $filteredRegistry[$source] = $views;
            }
            // Include _system views only if compiling non-system context
            elseif (str_starts_with($source, '_') && $context !== '_system') {
                $filteredRegistry[$source] = $views;
            }
        }
        
        if (empty($filteredRegistry)) {
            return;
        }
        
        $content = "/**\n";
        $content .= " * Auto-generated View Registry for context: {$context}\n";
        $content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= " * DO NOT EDIT MANUALLY\n";
        $content .= " */\n\n";
        
        $content .= "export type ViewLoader = () => Promise<any>;\n\n";
        $content .= "export interface ViewRegistry {\n";
        $content .= "  [key: string]: ViewLoader;\n";
        $content .= "}\n\n";
        
        // Generate registry by source
        foreach ($filteredRegistry as $source => $views) {
            $sourceName = $source === $context ? 'main' : str_replace('_', '', $source);
            $varName = "{$sourceName}Views";
            
            $content .= "/**\n";
            $content .= " * Views from source: {$source}\n";
            $content .= " */\n";
            $content .= "export const {$varName}: ViewRegistry = {\n";
            
            foreach ($views as $bladeName => $importPath) {
                $content .= "  '{$bladeName}': () => import('./{$importPath}'),\n";
            }
            
            $content .= "};\n\n";
        }
        
        // Generate combined registry
        $content .= "/**\n";
        $content .= " * Combined view registry for {$context} context\n";
        $content .= " * Use this to load views by blade name\n";
        $content .= " */\n";
        $content .= "export const viewRegistry: ViewRegistry = {\n";
        
        foreach ($filteredRegistry as $source => $views) {
            foreach ($views as $bladeName => $importPath) {
                $content .= "  '{$bladeName}': () => import('./{$importPath}'),\n";
            }
        }
        
        $content .= "};\n\n";
        
        // Generate helper functions
        $content .= "/**\n";
        $content .= " * Load a view by blade name\n";
        $content .= " * @param name Blade view name (e.g., 'pages.home', 'components.header')\n";
        $content .= " * @returns Promise resolving to view class\n";
        $content .= " */\n";
        $content .= "export async function loadView(name: string): Promise<any> {\n";
        $content .= "  const loader = viewRegistry[name];\n";
        $content .= "  if (!loader) {\n";
        $content .= "    throw new Error(`View not found: \${name}`);\n";
        $content .= "  }\n";
        $content .= "  const module = await loader();\n";
        $content .= "  return module.default;\n";
        $content .= "}\n\n";
        
        $content .= "/**\n";
        $content .= " * Check if a view exists\n";
        $content .= " */\n";
        $content .= "export function hasView(name: string): boolean {\n";
        $content .= "  return name in viewRegistry;\n";
        $content .= "}\n\n";
        
        $content .= "/**\n";
        $content .= " * Get all available view names\n";
        $content .= " */\n";
        $content .= "export function getViewNames(): string[] {\n";
        $content .= "  return Object.keys(viewRegistry);\n";
        $content .= "}\n";
        
        $this->writeOutput($registryPath, $content);
        $this->info("  📝 Generated registry: registry.{$context}.ts");
    }
    

}
