<?php

declare(strict_types=1);

namespace Saola\Core\Language;

use InvalidArgumentException;

/** Client visibility is explicit and independent of optional context branches in keys. */
final class ClientCatalogs
{
    public function __construct(private readonly TextResolver $resolver, private readonly array $scopes = []) {}

    public function definition(string $name): array
    {
        $definition = $this->scopes[$name] ?? null;
        if (!is_array($definition) || !is_array($definition['keys'] ?? null)) {
            throw new InvalidArgumentException('Unknown language scope.');
        }
        foreach ($definition['keys'] as $path) {
            if (!is_string($path) || $path === '' || $path === '*' || str_starts_with($path, '.') || str_ends_with($path, '.')) {
                throw new InvalidArgumentException('Language scope requires explicit key paths.');
            }
        }
        return $definition;
    }

    public function snapshot(string $scope, string $locale): array
    {
        $definition = $this->definition($scope);
        $paths = array_values(array_unique($definition['keys']));
        sort($paths);
        $snapshot = $this->resolver->snapshot($locale);
        $visible = [];
        foreach (LanguageTree::flatten($snapshot['messages']) as $key => $value) {
            foreach ($paths as $path) {
                if ($key === $path || str_starts_with($key, $path.'.')) {
                    $visible[$key] = $value;
                    break;
                }
            }
        }
        return [
            'revision' => $snapshot['revision'],
            // A changed allowlist cannot reuse a dictionary from the old scope.
            'scope' => $scope.':'.substr(hash('sha256', json_encode($paths, JSON_THROW_ON_ERROR)), 0, 16),
            'messages' => (object) LanguageTree::nest($visible),
        ];
    }

    /**
     * The client config without the catalogs the browser already holds at the same revision. A running app declares them in
     * `X-Saola-Language: <scope>;<locale>=<revision>;…`; without that header (first page load, older clients, tests) everything is
     * sent as before. A changed scope or revision always travels again.
     */
    public static function withoutHeld(array $config, ?string $held): array
    {
        if (!isset($config['scope']) || !is_array($config['catalogs'] ?? null)) {
            return $config;
        }
        $have = self::held($held, $config['scope']);
        $config['catalogs'] = array_filter($config['catalogs'], fn ($catalog, $code) => ($have[$code] ?? null) !== ($catalog['revision'] ?? null), ARRAY_FILTER_USE_BOTH);

        return $config;
    }

    /** @return array<string, int> locale => revision the browser holds for this scope; empty when it declares another scope or none. */
    private static function held(?string $held, string $scope): array
    {
        $parts = explode(';', (string) $held);
        if (array_shift($parts) !== $scope) {
            return [];
        }
        $have = [];
        foreach ($parts as $part) {
            [$code, $revision] = array_pad(explode('=', $part, 2), 2, '');
            if ($code !== '' && ctype_digit($revision)) {
                $have[$code] = (int) $revision;
            }
        }

        return $have;
    }

    /** With `$held` (the `X-Saola-Language` header) a catalog the browser already holds is not even projected. */
    public function clientConfig(string $scope, ?string $locale = null, ?string $held = null): array
    {
        $raw = $this->resolver->clientConfig($locale);
        $paths = array_values(array_unique($this->definition($scope)['keys']));
        sort($paths);
        $id = $scope.':'.substr(hash('sha256', json_encode($paths, JSON_THROW_ON_ERROR)), 0, 16);
        $have = self::held($held, $id);
        $catalogs = [];
        foreach ($raw['catalogs'] as $code => $catalog) {
            if (($have[$code] ?? null) !== $catalog['revision']) {
                $catalogs[$code] = $this->snapshot($scope, $code);
            }
        }
        return array_filter(['locale' => $raw['locale'], 'fallbackLocale' => $raw['fallbackLocale'], 'scope' => $id, 'catalogs' => $catalogs,
            'dictionary' => self::dictionaryUrl($scope)], static fn ($value) => $value !== null);
    }

    /**
     * Mẫu URL tải từ điển của một scope (`_locale_` là chỗ điền locale), hoặc null khi
     * dự án không có route từ điển (route do package admin đăng ký). Client dùng nó
     * khi header `X-Saola-Language-Revision` báo catalog đang giữ đã cũ.
     */
    public static function dictionaryUrl(string $scope): ?string
    {
        $name = (string) config('sao.i18n.dictionary_route', 'system.language.dictionary');

        return \Illuminate\Support\Facades\Route::has($name)
            ? route($name, ['scope' => $scope, 'locale' => '_locale_'], false)
            : null;
    }
}
