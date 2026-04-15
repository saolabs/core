<?php

namespace Saola\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:module {name : The name of the module}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new module from template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $moduleName = $this->argument('name');
        
        // Parse module name for nested structure (ParentModule/ChildModule/SubModule/...)
        $moduleParts = explode('/', $moduleName);
        
        // Validate each part of module name
        foreach ($moduleParts as $part) {
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $part)) {
                $this->error("Module part '{$part}' must start with uppercase letter and contain only letters and numbers.");
                return 1;
            }
        }

        // Determine paths and names for multi-level nesting
        $targetModule = end($moduleParts); // Last part is the target module
        $parentModules = array_slice($moduleParts, 0, -1); // All parts except the last
        
        if (empty($parentModules)) {
            // Single level module
            $fullModulePath = "src/Modules/{$targetModule}";
            $namespacePrefix = "Saola\\Modules\\{$targetModule}";
            $displayName = $targetModule;
        } else {
            // Multi-level nested module
            $parentPath = 'src/Modules/' . implode('/', $parentModules);
            $fullModulePath = "{$parentPath}/{$targetModule}";
            
            // Build namespace: Modules\Parent\Child\Sub\...
            $namespaceParts = ['Modules'];
            foreach ($parentModules as $parent) {
                $namespaceParts[] = $parent;
            }
            $namespaceParts[] = $targetModule;
            $namespacePrefix = implode('\\', $namespaceParts);
            
            $displayName = implode('/', $moduleParts);
        }

        // Check if module already exists
        $modulePath = base_path($fullModulePath);
        if (File::exists($modulePath)) {
            $this->error("Module '{$displayName}' already exists!");
            return 1;
        }

        // Check if parent modules exist (for nested modules) and create if missing
        if (!empty($parentModules)) {
            $currentPath = 'src/Modules';
            foreach ($parentModules as $parent) {
                $currentPath .= "/{$parent}";
                $parentPath = base_path($currentPath);
                if (!File::exists($parentPath)) {
                    $this->info("Creating parent module directory: {$currentPath}");
                    File::makeDirectory($parentPath, 0755, true);
                }
            }
        }

        $this->info("Creating module '{$displayName}'...");

        // Create module directory structure
        $this->createModuleStructure($fullModulePath, $targetModule);

        // Copy and process template files
        $this->processTemplateFiles($fullModulePath, $targetModule, $namespacePrefix);

        $this->info("Module '{$displayName}' created successfully!");
        $this->info("Module path: {$modulePath}");
        
        return 0;
    }

    /**
     * Create module directory structure
     */
    private function createModuleStructure(string $fullModulePath, string $moduleName): void
    {
        $directories = [
            $fullModulePath,
            "{$fullModulePath}/Http/Controllers/Web",
            "{$fullModulePath}/Http/Controllers/Api", 
            "{$fullModulePath}/Http/Controllers/Admin",
            "{$fullModulePath}/Services",
            "{$fullModulePath}/Providers",
            "{$fullModulePath}/Models",
            "{$fullModulePath}/Repositories",
            "{$fullModulePath}/Masks",
        ];

        foreach ($directories as $directory) {
            File::makeDirectory(base_path($directory), 0755, true);
            $this->line("Created directory: {$directory}");
        }
    }

    /**
     * Process template files and create module files
     */
    private function processTemplateFiles(string $fullModulePath, string $moduleName, string $namespacePrefix): void
    {
        $templatePath = base_path('templates/module');
        $modulePath = base_path($fullModulePath);
        
        // Define file mappings
        $fileMappings = [
            'BootstrapProvider.php' => 'BootstrapProvider.php',
            'Providers/{{ModuleName}}RouteServiceProvider.php' => "Providers/{$moduleName}RouteServiceProvider.php",
            'Services/{{ModuleName}}ServiceInterface.php' => "Services/{$moduleName}ServiceInterface.php",
            'Services/{{ModuleName}}Service.php' => "Services/{$moduleName}Service.php",
            'Http/Controllers/Web/{{ModuleName}}Controller.php' => "Http/Controllers/Web/{$moduleName}Controller.php",
            'Http/Controllers/Api/{{ModuleName}}Controller.php' => "Http/Controllers/Api/{$moduleName}Controller.php",
            'Http/Controllers/Admin/{{ModuleName}}Controller.php' => "Http/Controllers/Admin/{$moduleName}Controller.php",
            'Models/{{ModuleName}}.php' => "Models/{$moduleName}.php",
            'Repositories/{{ModuleName}}Repository.php' => "Repositories/{$moduleName}Repository.php",
            'Masks/{{ModuleName}}Mask.php' => "Masks/{$moduleName}Mask.php",
        ];

        foreach ($fileMappings as $templateFile => $targetFile) {
            $this->processTemplateFile($templatePath, $templateFile, $modulePath, $targetFile, $moduleName, $namespacePrefix);
        }
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
        File::put($targetFilePath, $content);
        
        $this->line("Created file: {$targetFile}");
    }

    /**
     * Replace placeholders in content
     */
    private function replacePlaceholders(string $content, string $moduleName, string $namespacePrefix): string
    {
        $replacements = [
            '{{ModuleName}}' => $moduleName,
            '{{module_name}}' => Str::snake($moduleName),
            '{{MODULE_NAME}}' => Str::upper(Str::snake($moduleName)),
            '{{Namespace}}' => $namespacePrefix,
        ];

        foreach ($replacements as $placeholder => $replacement) {
            $content = str_replace($placeholder, $replacement, $content);
        }

        return $content;
    }
}
