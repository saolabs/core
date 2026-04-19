<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * @var array<string, bool>
     */
    private array $ignoredTargets = [];

    private string $moduleRouteSlug = '';

    private string $modelName = '';

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:module
                            {name : The name of the module}
                            {--slug= : Custom route slug/prefix/name for generated routes}
                            {--model= : Custom model class name used in generated model/repository/mask files}
                            {--ignore=* : Comma-separated or repeated ignore targets: api,admin,web,mask,model,repository,service}
                            {--ignore-api : Skip generating API context/controller/routes}
                            {--ignore-admin : Skip generating admin context/controller/routes}
                            {--ignore-mask : Skip generating mask files}
                            {--ignore-model : Skip generating model files}
                            {--ignore-repository : Skip generating repository files}
                            {--ignore-service : Skip generating service files and service binding}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new module from template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $moduleName = str_replace('\\', '/', trim((string) $this->argument('name')));
        
        // Parse module name for nested structure (ParentModule/ChildModule/SubModule/...)
        $moduleParts = explode('/', $moduleName);
        
        // Validate each part of module name
        foreach ($moduleParts as $part) {
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $part)) {
                $this->error("Module part '{$part}' must start with uppercase letter and contain only letters and numbers.");
                return 1;
            }
        }

        // Determine paths and names
        $targetModule = end($moduleParts); // Last part is the target module
        $moduleSlug = Str::snake($targetModule);
        $customModelName = trim((string) $this->option('model'));
        if ($customModelName !== '' && !preg_match('/^[A-Z][a-zA-Z0-9]*$/', $customModelName)) {
            $this->error("Model name '{$customModelName}' must start with uppercase letter and contain only letters and numbers.");
            return 1;
        }

        $this->ignoredTargets = $this->resolveIgnoredTargets();
        $this->moduleRouteSlug = $this->resolveRouteSlug($moduleSlug);
        $this->modelName = $this->resolveModelName($targetModule);

        $fullModulePath = 'app/Modules/' . implode('/', $moduleParts);
        $namespacePrefix = 'App\\Modules\\' . implode('\\', $moduleParts);
        $displayName = implode('/', $moduleParts);

        // Check if module already exists
        $modulePath = base_path($fullModulePath);
        if (File::exists($modulePath)) {
            $this->error("Module '{$displayName}' already exists!");
            return 1;
        }

        $this->info("Creating module '{$displayName}'...");
        $this->line("Route slug: {$this->moduleRouteSlug}");
        $this->line("Model name: {$this->modelName}");
        if (!empty($this->ignoredTargets)) {
            $this->line('Ignored targets: ' . implode(', ', array_keys($this->ignoredTargets)));
        }

        // Copy and process template files
        $this->processTemplateFiles($fullModulePath, $targetModule, $namespacePrefix);

        $this->info("Module '{$displayName}' created successfully!");
        $this->info("Module path: {$modulePath}");
        
        return 0;
    }

    /**
     * Process template files and create module files
     */
    private function processTemplateFiles(string $fullModulePath, string $moduleName, string $namespacePrefix): void
    {
        $templatePath = $this->resolveTemplatePath();
        $modulePath = base_path($fullModulePath);

        File::ensureDirectoryExists($modulePath);

        $templateFiles = File::allFiles($templatePath);
        foreach ($templateFiles as $templateFile) {
            $relativePath = str_replace($templatePath . DIRECTORY_SEPARATOR, '', $templateFile->getPathname());
            if ($this->shouldSkipTemplateFile($relativePath)) {
                continue;
            }

            $targetFile = $this->replacePlaceholders($relativePath, $moduleName, $namespacePrefix);
            if ($this->modelName !== $moduleName && $targetFile === "Models/{$moduleName}.php") {
                $targetFile = "Models/{$this->modelName}.php";
            }

            $this->processTemplateFile($templatePath, $relativePath, $modulePath, $targetFile, $moduleName, $namespacePrefix);
        }
    }

    /**
     * Resolve ignored targets from options.
     *
     * @return array<string, bool>
     */
    private function resolveIgnoredTargets(): array
    {
        $ignored = [];

        foreach ((array) $this->option('ignore') as $rawValue) {
            foreach (preg_split('/\s*,\s*/', (string) $rawValue, -1, PREG_SPLIT_NO_EMPTY) as $value) {
                $normalized = strtolower(trim($value));
                $normalized = str_replace(['_', '-'], '', $normalized);

                if (in_array($normalized, ['api', 'admin', 'web'], true)) {
                    $ignored[$normalized] = true;
                }

                if (in_array($normalized, ['mask', 'masks'], true)) {
                    $ignored['mask'] = true;
                }

                if (in_array($normalized, ['model', 'models'], true)) {
                    $ignored['model'] = true;
                }

                if (in_array($normalized, ['repository', 'repositories', 'repo'], true)) {
                    $ignored['repository'] = true;
                }

                if (in_array($normalized, ['service', 'services'], true)) {
                    $ignored['service'] = true;
                }
            }
        }

        if ((bool) $this->option('ignore-api')) {
            $ignored['api'] = true;
        }

        if ((bool) $this->option('ignore-admin')) {
            $ignored['admin'] = true;
        }

        if ((bool) $this->option('ignore-mask')) {
            $ignored['mask'] = true;
        }

        if ((bool) $this->option('ignore-model')) {
            $ignored['model'] = true;
        }

        if ((bool) $this->option('ignore-repository')) {
            $ignored['repository'] = true;
        }

        if ((bool) $this->option('ignore-service')) {
            $ignored['service'] = true;
        }

        return $ignored;
    }

    /**
     * Resolve route slug from --slug option or module name.
     */
    private function resolveRouteSlug(string $defaultModuleSlug): string
    {
        $customSlug = trim((string) $this->option('slug'));
        if ($customSlug === '') {
            return Str::plural($defaultModuleSlug);
        }

        $customSlug = str_replace('\\', '/', $customSlug);
        $customSlug = preg_replace('/\s+/', '-', $customSlug) ?? $customSlug;

        return trim(Str::lower($customSlug), '/');
    }

    /**
     * Resolve model class name from --model option or module name.
     */
    private function resolveModelName(string $defaultModelName): string
    {
        $modelName = trim((string) $this->option('model'));
        return $modelName === '' ? $defaultModelName : $modelName;
    }

    /**
     * Determine whether a template path should be skipped.
     */
    private function shouldSkipTemplateFile(string $relativePath): bool
    {
        $path = str_replace('\\', '/', $relativePath);

        if (($this->ignoredTargets['api'] ?? false) && str_starts_with($path, 'Http/Controllers/Api/')) {
            return true;
        }

        if (($this->ignoredTargets['admin'] ?? false) && str_starts_with($path, 'Http/Controllers/Admin/')) {
            return true;
        }

        if (($this->ignoredTargets['web'] ?? false) && str_starts_with($path, 'Http/Controllers/Web/')) {
            return true;
        }

        if (($this->ignoredTargets['mask'] ?? false) && str_starts_with($path, 'Masks/')) {
            return true;
        }

        if (($this->ignoredTargets['model'] ?? false) && str_starts_with($path, 'Models/')) {
            return true;
        }

        if (($this->ignoredTargets['repository'] ?? false) && str_starts_with($path, 'Repositories/')) {
            return true;
        }

        if (($this->ignoredTargets['service'] ?? false) && str_starts_with($path, 'Services/')) {
            return true;
        }

        return false;
    }

    /**
     * Resolve template path, allowing user project override.
     */
    private function resolveTemplatePath(): string
    {
        $projectTemplatePath = base_path('templates/module');
        if (File::isDirectory($projectTemplatePath)) {
            return $projectTemplatePath;
        }

        return dirname(__DIR__, 3) . '/templates/module';
    }

    /**
     * Process a single template file
     */
    private function processTemplateFile(string $templatePath, string $templateFile, string $modulePath, string $targetFile, string $moduleName, string $namespacePrefix): void
    {
        $templateFilePath = $templatePath . '/' . $templateFile;
        
        if (!File::exists($templateFilePath)) {
            $this->warn("Template file not found: {$templateFile}");
            return;
        }

        $content = File::get($templateFilePath);
        
        // Replace placeholders
        $content = $this->replacePlaceholders($content, $moduleName, $namespacePrefix);
        
        // Write to target file
        $targetFilePath = $modulePath . '/' . $targetFile;
        File::ensureDirectoryExists(dirname($targetFilePath));
        File::put($targetFilePath, $content);
        
        $this->line("Created file: {$targetFile}");
    }

    /**
     * Replace placeholders in content
     */
    private function replacePlaceholders(string $content, string $moduleName, string $namespacePrefix): string
    {
        $moduleSlug = Str::snake($moduleName);
        $modelSlug = Str::snake($this->modelName);

        $providerReplacements = [
            '{{ServiceUseStatement}}' => ($this->ignoredTargets['service'] ?? false)
                ? ''
                : 'use {{Namespace}}\\Services\\{{ModuleName}}Service;',
            '{{AdminControllerUseStatement}}' => ($this->ignoredTargets['admin'] ?? false)
                ? ''
                : 'use {{Namespace}}\\Http\\Controllers\\Admin\\{{ModuleName}}Controller as Admin{{ModuleName}}Controller;',
            '{{ApiControllerUseStatement}}' => ($this->ignoredTargets['api'] ?? false)
                ? ''
                : 'use {{Namespace}}\\Http\\Controllers\\Api\\{{ModuleName}}Controller as Api{{ModuleName}}Controller;',
            '{{WebControllerUseStatement}}' => ($this->ignoredTargets['web'] ?? false)
                ? ''
                : 'use {{Namespace}}\\Http\\Controllers\\Web\\{{ModuleName}}Controller as Web{{ModuleName}}Controller;',
            '{{RegisterBindings}}' => ($this->ignoredTargets['service'] ?? false)
                ? '        // Service binding is skipped via --ignore-service option.'
                : '        $this->app->singleton({{ModuleName}}Service::class, {{ModuleName}}Service::class);',
            '{{AdminRoutesBlock}}' => ($this->ignoredTargets['admin'] ?? false)
                ? ''
                : "        System::context('admin')\n            ->module('{{module_route_name}}')\n            ->controller(Admin{{ModuleName}}Controller::class)\n            ->prefix('{{module_route_name}}')\n            ->as('{{module_route_name}}')\n            ->group(function (\$module) {\n                \$module->get('/', 'index')->name('index');\n            });",
            '{{ApiRoutesBlock}}' => ($this->ignoredTargets['api'] ?? false)
                ? ''
                : "        System::context('api')\n            ->module(['slug' => '{{module_route_name}}', 'prefix' => '/{{module_route_name}}', 'priority' => 1])\n            ->controller(Api{{ModuleName}}Controller::class)\n            ->as('{{module_route_name}}')\n            ->group(function (\$module) {\n                \$module->get('/', 'index')->name('index');\n            });",
            '{{WebRoutesBlock}}' => ($this->ignoredTargets['web'] ?? false)
                ? ''
                : "        System::context('web')\n            ->module(['slug' => '{{module_route_name}}', 'prefix' => '/{{module_route_name}}', 'priority' => 1])\n            ->controller(Web{{ModuleName}}Controller::class)\n            ->as('{{module_route_name}}')\n            ->group(function (\$module) {\n                \$module->get('/', 'index')->name('index');\n            });",
        ];

        $replacements = [
            '{{ModuleName}}' => $moduleName,
            '{{module_name}}' => $moduleSlug,
            '{{module_route_name}}' => $this->moduleRouteSlug,
            '{{ModelName}}' => $this->modelName,
            '{{model_name}}' => $modelSlug,
            '{{MODULE_NAME}}' => Str::upper($moduleSlug),
            '{{MODULE_ROUTE_NAME}}' => Str::upper($this->moduleRouteSlug),
            '{{MODEL_NAME}}' => Str::upper($modelSlug),
            '{{Namespace}}' => $namespacePrefix,
        ];

        // Replace provider-specific blocks first, then resolve nested placeholders they contain.
        foreach ($providerReplacements as $placeholder => $replacement) {
            $content = str_replace($placeholder, $replacement, $content);
        }

        foreach ($replacements as $placeholder => $replacement) {
            $content = str_replace($placeholder, $replacement, $content);
        }

        return $content;
    }
}
