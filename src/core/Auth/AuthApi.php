<?php

namespace Saola\Core\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;

/** Request-scoped configuration bridge; independent of view/context APIs. */
class AuthApi
{
    public function guardName(): string
    {
        return config('sao.auth_api.guard') ?: app('auth')->getDefaultDriver();
    }

    public function guard(): StatefulGuard
    {
        $guard = app('auth')->guard($this->guardName());
        if (!$guard instanceof StatefulGuard) throw new \LogicException('Auth API requires a session guard.');
        return $guard;
    }

    public function route(string $action, array $parameters = [], bool $absolute = false): string
    {
        return route('saola.auth.'.$action, $parameters, $absolute);
    }

    public function configuration(Request $request): ?array
    {
        if (!config('sao.auth_api.enabled', false)) return null;
        $providers = [];
        foreach (config('sao.auth_api.providers', []) as $name => $provider) {
            if (!($provider['enabled'] ?? false)) continue;
            $providers[] = ['name' => $name, 'label' => $provider['label'] ?? $name,
                'url' => $this->route('redirect', ['provider' => $name])];
        }
        return [
            'version' => 1,
            'guard' => $this->guardName(),
            'csrfToken' => $request->hasSession() ? $request->session()->token() : null,
            'usernameField' => config('sao.auth_api.username', 'email'),
            'endpoints' => [
                'session' => $this->route('session'),
                'login' => config('sao.auth_api.password', true) ? $this->route('login') : null,
                'logout' => $this->route('logout'),
            ],
            'providers' => $providers,
        ];
    }

    public function provider(string $name): OAuthProvider
    {
        $providers = config('sao.auth_api.providers', []);
        abort_unless(isset($providers[$name]) && ($providers[$name]['enabled'] ?? false), 404);
        $config = $providers[$name];
        return isset($config['adapter']) ? app($config['adapter'])
            : new SocialiteProvider($config['driver'] ?? $name, $config['scopes'] ?? []);
    }

    public function resolver(): SocialUserResolver
    {
        $class = config('sao.auth_api.social_user_resolver');
        if (!$class && !app()->bound(SocialUserResolver::class)) {
            throw new \LogicException('Configure auth_api.social_user_resolver before enabling OAuth.');
        }
        return app($class ?: SocialUserResolver::class);
    }

    public function returnPath(mixed $path): string
    {
        if (!is_string($path) || $path === '') return '/';
        $decoded = rawurldecode($path);
        if (!str_starts_with($decoded, '/') || str_starts_with($decoded, '//')
            || preg_match('/[\\\\\x00-\x20\x7f]/', $decoded)) return '/';
        return $path;
    }
}
