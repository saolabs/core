<?php

declare(strict_types=1);

namespace Saola\Core\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Language\ClientCatalogs;
use Saola\Core\View\Services\ViewHelperService;

final class ContextBootstrap
{
    public const ROUTE_PREFIX = 'saola.bootstrap.';

    private const SCHEMA = 1;

    public function __construct(
        private readonly ClientCatalogs $catalogs,
        private readonly ViewContextManager $contexts,
        private readonly ViewHelperService $views,
    ) {}

    public static function settings(string $context): ?array
    {
        $definition = config('sao.bootstrap.contexts', [])[$context] ?? null;
        if (!is_array($definition) || !($definition['enabled'] ?? true)) {
            return null;
        }

        $policy = isset($definition['policy']) ? config($definition['policy'], []) : [];
        if (!($policy['enabled'] ?? true)) {
            return null;
        }

        $scope = $definition['scope'] ?? $policy['language_scope'] ?? $context;
        $scopeDefinition = config('sao.i18n.client_scopes', [])[$scope] ?? null;
        if (!is_array($scopeDefinition)) {
            return null;
        }

        $ability = $definition['ability'] ?? $policy['ability'] ?? null;

        return [
            'scope' => $scope,
            'public' => ($scopeDefinition['public'] ?? false) === true && $ability === null,
            'guard' => $definition['guard'] ?? $policy['guard'] ?? $scopeDefinition['guard'] ?? null,
            'ability' => $ability,
            'proxy_ttl' => (int) ($definition['proxy_ttl'] ?? 0),
        ];
    }

    /** Luật quyền của một context không public — bootstrap.js và endpoint view context dùng chung. */
    public static function authorize(Request $request, array $settings, array $scope): void
    {
        $user = $request->user($settings['guard']);
        abort_unless($user, 401);
        if ($settings['ability'] !== null) {
            abort_unless(Gate::forUser($user)->allows($settings['ability']), 403);
        }
        if (($scope['public'] ?? false) !== true) {
            $scopeUser = $request->user($scope['guard'] ?? null);
            $ability = $scope['ability'] ?? null;
            abort_unless($scopeUser && $ability && Gate::forUser($scopeUser)->allows($ability, [$settings['scope']]), 403);
        }
    }

    public function enabled(string $context): bool
    {
        return config('sao.bootstrap.external', false)
            && $this->contexts->hasContext($context)
            && self::settings($context) !== null
            && app('router')->has(self::ROUTE_PREFIX.$context);
    }

    public function forPage(Request $request, string $context): ?array
    {
        if (!$this->enabled($context)) {
            return null;
        }

        $key = self::ROUTE_PREFIX.$context;
        if ($request->attributes->has($key)) {
            return $request->attributes->get($key);
        }

        // Protected pages opt in after preparing their authorized client catalog.
        if (!self::settings($context)['public'] && !$request->attributes->has('saola.i18n')) {
            return null;
        }

        $this->preparePage($request, $context, app()->getLocale(), $this->describe($context));

        return $request->attributes->get($key);
    }

    public function preparePage(Request $request, string $context, string $locale, array $descriptor): bool
    {
        $codes = array_column($descriptor['uiLocales'], 'code');
        if (!in_array($locale, [...$codes, $descriptor['uiDefault'], $descriptor['fallbackLocale']], true)) {
            return false;
        }

        $request->attributes->set(self::ROUTE_PREFIX.$context, [
            'url' => route(self::ROUTE_PREFIX.$context, [], false),
            'context' => $context,
            'locale' => $locale,
            'revision' => $descriptor['revision'],
        ]);

        return true;
    }

    public function describe(string $context): array
    {
        $source = config('sao.bootstrap.locale_state');
        $state = $source ? app()->call($source) : [
            'uiDefault' => config('sao.i18n.default_locale', 'en'),
            'uiFallback' => config('sao.i18n.fallback_locale', 'en'),
            'locales' => [],
        ];
        $locales = array_values(array_filter($state['locales'], static fn (array $locale) => $locale['uiEnabled'] && $locale['revision'] > 0));
        $scope = self::settings($context)['scope'];
        $keys = array_values(array_unique($this->catalogs->definition($scope)['keys']));
        sort($keys);
        $routes = $this->views->exportSpaRoutes($context);

        $revision = hash('sha256', json_encode([
            self::SCHEMA, $context, $scope, $state['uiDefault'], $state['uiFallback'], $locales, $keys,
            // Không tính shared data: script này công khai, dùng chung cho mọi locale và cache được ở proxy.
            $this->contexts->getContextViewRevision($context, false), $routes,
        ], JSON_THROW_ON_ERROR));

        return [
            'schema' => self::SCHEMA,
            'context' => $context,
            'revision' => $revision,
            'uiDefault' => $state['uiDefault'],
            'fallbackLocale' => $state['uiFallback'],
            'uiLocales' => $locales,
            'routes' => $routes,
        ];
    }

    public function script(string $context): string
    {
        $config = $this->describe($context);
        $scope = self::settings($context)['scope'];
        $catalogs = [];
        $codes = array_unique([...array_column($config['uiLocales'], 'code'), $config['uiDefault'], $config['fallbackLocale']]);
        foreach ($codes as $code) {
            $catalogs[$code] = $this->catalogs->snapshot($scope, $code);
        }

        $payload = [
            'schema' => $config['schema'],
            'context' => $config['context'],
            'revision' => $config['revision'],
            'router' => ['mode' => 'history', 'base' => '/', 'routes' => $config['routes']],
            'i18n' => [
                'uiDefault' => $config['uiDefault'],
                'fallbackLocale' => $config['fallbackLocale'],
                'scope' => $catalogs[$config['uiDefault']]['scope'],
                'uiLocales' => $config['uiLocales'],
                'catalogs' => $catalogs,
                'dictionary' => ClientCatalogs::dictionaryUrl($scope),
            ],
        ];

        return 'window.SaolaBootstrap?.register(document.currentScript, '.Js::from($payload)->toHtml().');';
    }
}
