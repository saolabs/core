<?php

namespace Saola\Core\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/** Uses Socialite's stateful OAuth flow for Google, GitHub and installed drivers. */
class SocialiteProvider implements OAuthProvider
{
    public function __construct(private string $driverName, private array $scopes = []) {}

    private function driver(string $callbackUrl): object
    {
        $factory = 'Laravel\\Socialite\\Contracts\\Factory';
        if (!app()->bound($factory)) {
            throw new \LogicException('Install laravel/socialite to enable social authentication.');
        }
        // Build a fresh driver: Socialite caches drivers with request/session references.
        // Never reuse them across requests in a long-lived worker.
        $manager = app()->make($factory);
        $manager->setContainer(app());
        $manager->forgetDrivers();
        $driver = $manager->driver($this->driverName)->redirectUrl($callbackUrl);
        if ($this->scopes !== []) $driver->scopes($this->scopes);
        return $driver;
    }

    public function redirect(Request $request, string $callbackUrl): RedirectResponse
    {
        return $this->driver($callbackUrl)->redirect();
    }

    public function user(Request $request, string $callbackUrl): OAuthIdentity
    {
        $user = $this->driver($callbackUrl)->user(); // validates state; never stateless()
        return new OAuthIdentity((string) $user->getId(), $user->getName(), $user->getNickname(), $user->getEmail());
    }
}
