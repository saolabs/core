<?php

declare(strict_types=1);

namespace Saola\Core\Language;

use Closure;

/** Request-scoped, synchronous lookup. Locale is read at call time, never cached globally. */
final class TextResolver
{
    private array $snapshots = [];

    public function __construct(
        private readonly LanguageStore $store,
        private readonly Closure $locale,
        private readonly Closure $fallbackLocale,
    ) {}

    public function text(string $key, array $replace = [], ?string $default = null, ?string $locale = null): string
    {
        $locale ??= ($this->locale)();
        $value = LanguageTree::get($this->snapshot($locale)['messages'], $key);
        $value ??= LanguageTree::get($this->snapshot(($this->fallbackLocale)())['messages'], $key);
        $value ??= $default ?? $key;
        $parameters = [];
        foreach ($replace as $name => $replacement) {
            $replacement = (string) $replacement;
            $parameters[':'.$name] = $replacement;
            $parameters[':'.ucfirst($name)] = mb_strtoupper(mb_substr($replacement, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($replacement, 1, null, 'UTF-8');
            $parameters[':'.strtoupper($name)] = mb_strtoupper($replacement, 'UTF-8');
        }
        return strtr($value, $parameters);
    }

    public function snapshot(string $locale): array
    {
        return $this->snapshots[$locale] ??= $this->store->snapshot($locale);
    }

    public function forget(): void
    {
        $this->snapshots = [];
    }

    /** Separate from legacy Laravel messages: root leaves are preserved. */
    public function clientConfig(?string $locale = null): array
    {
        $locale ??= ($this->locale)();
        $fallback = ($this->fallbackLocale)();
        $catalogs = [];
        foreach (array_unique([$locale, $fallback]) as $code) {
            $snapshot = $this->snapshot($code);
            $snapshot['messages'] = (object) $snapshot['messages'];
            $catalogs[$code] = $snapshot;
        }
        return ['locale' => $locale, 'fallbackLocale' => $fallback, 'catalogs' => $catalogs];
    }
}
