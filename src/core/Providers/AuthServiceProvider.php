<?php

namespace Saola\Core\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Saola\Core\Auth\{AuthApi, PasswordAuthenticator, SessionPasswordAuthenticator};
use Saola\Core\Services\ClientShare;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AuthApi::class);
        $this->app->bindIf(PasswordAuthenticator::class, SessionPasswordAuthenticator::class);
    }

    public function boot(): void
    {
        $this->app->make(ClientShare::class)->share('authClient', fn ($request) => app(AuthApi::class)->configuration($request));
        if (!config('sao.auth_api.enabled', false)) return;
        RateLimiter::for('saola.auth.login', function ($request) {
            $field = config('sao.auth_api.username', 'email');
            $name = $request->input($field);
            $identity = is_string($name) ? mb_strtolower($name) : '';
            return [Limit::perMinute(30)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('user:'.hash('sha256', $identity.'|'.$request->ip()))];
        });
        $this->loadRoutesFrom(__DIR__.'/../../routes/auth.php');
    }
}
