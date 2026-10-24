<?php

declare(strict_types=1);

namespace Saola\Core\Language;

use InvalidArgumentException;

/** Explicit registration prevents accidentally importing Laravel validation/auth files. */
final class LanguageSources
{
    private array $sources = [];

    public function register(string $owner, string $root, array $files, ?string $overrideRoot = null): void
    {
        if ($owner === '' || $files === []) {
            throw new InvalidArgumentException('A language source needs an owner and explicit file names.');
        }
        foreach ($files as $file) {
            if (!is_string($file) || !preg_match('/^[A-Za-z0-9_-]+$/D', $file)) {
                throw new InvalidArgumentException('Language file names must be basenames without .php.');
            }
        }
        $source = ['root' => rtrim($root, '/\\'), 'files' => array_values($files), 'override' => $overrideRoot];
        if (isset($this->sources[$owner]) && $this->sources[$owner] !== $source) {
            throw new InvalidArgumentException("Language source already registered: {$owner}");
        }
        $this->sources[$owner] = $source;
    }

    /** @return array<string, array<string, array{value:string, owner:string, file:string}>> */
    public function catalog(): array
    {
        $catalog = [];
        $owners = [];
        foreach ($this->sources as $owner => $source) {
            $directories = glob($source['root'].'/*', GLOB_ONLYDIR) ?: [];
            if ($source['override'] !== null) {
                $directories = array_merge($directories, glob($source['override'].'/*', GLOB_ONLYDIR) ?: []);
            }
            $locales = array_unique(array_map('basename', $directories));
            foreach ($locales as $locale) {
                if (!preg_match('/^[a-zA-Z]{2,3}(?:[_-][a-zA-Z0-9]{2,8})*$/D', $locale)) {
                    continue; // In particular, lang/vendor is not a locale.
                }
                foreach ($source['files'] as $file) {
                    $flat = [];
                    foreach (array_filter([$source['root'], $source['override']]) as $root) {
                        $path = $root.'/'.$locale.'/'.$file.'.php';
                        if (!is_file($path)) {
                            continue;
                        }
                        // Only developer-owned PHP. Upload/import is JSON, never PHP.
                        $tree = (static fn (string $path) => require $path)($path);
                        if (!is_array($tree)) {
                            throw new InvalidArgumentException("Language source must return an array: {$path}");
                        }
                        $flat = array_replace($flat, LanguageTree::flatten($tree));
                    }
                    LanguageTree::nest($flat);
                    foreach ($flat as $key => $value) {
                        $identity = $owner.'::'.$file;
                        if (isset($owners[$key]) && $owners[$key] !== $identity) {
                            throw new InvalidArgumentException("Duplicate language key {$key}: {$owners[$key]} and {$identity}");
                        }
                        $owners[$key] = $identity;
                        $catalog[$locale][$key] = compact('value', 'owner', 'file');
                    }
                }
            }
        }
        // Detect leaf/branch collisions across files, sources and locales too.
        LanguageTree::nest(array_fill_keys(array_keys($owners), ''));
        return $catalog;
    }
}
