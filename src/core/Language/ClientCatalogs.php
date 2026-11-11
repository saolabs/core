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
        if (!$held || !isset($config['scope']) || !is_array($config['catalogs'] ?? null)) {
            return $config;
        }
        $parts = explode(';', $held);
        if (array_shift($parts) !== $config['scope']) {
            return $config;
        }
        $have = [];
        foreach ($parts as $part) {
            [$code, $revision] = array_pad(explode('=', $part, 2), 2, '');
            if ($code !== '' && ctype_digit($revision)) {
                $have[$code] = (int) $revision;
            }
        }
        $config['catalogs'] = array_filter($config['catalogs'], fn ($catalog, $code) => ($have[$code] ?? null) !== ($catalog['revision'] ?? null), ARRAY_FILTER_USE_BOTH);

        return $config;
    }

    public function clientConfig(string $scope, ?string $locale = null): array
    {
        $raw = $this->resolver->clientConfig($locale);
        $catalogs = [];
        foreach (array_keys($raw['catalogs']) as $code) {
            $catalogs[$code] = $this->snapshot($scope, $code);
        }
        return [
            'locale' => $raw['locale'], 'fallbackLocale' => $raw['fallbackLocale'],
            'scope' => $catalogs[$raw['locale']]['scope'], 'catalogs' => $catalogs,
        ];
    }
}
