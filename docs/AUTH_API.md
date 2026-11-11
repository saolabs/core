# Standard Auth API

The API is independent of view contexts. Laravel owns authentication and
authorization; the client receives the same public auth snapshot through SSR,
SPA page-data and auth responses. The standard transport is opt-in so existing
applications can keep their auth routes and controllers.

## Enable the routes

In `config/sao.php`:

```php
'auth_api' => [
    'enabled' => true,
    'prefix' => 'auth', // change if the application already uses these paths
    'guard' => null, // default Laravel session guard
    'password' => true,
    'username' => 'email', // or 'username', matching the auth provider
    'redirect' => '/',
    'social_user_resolver' => App\Auth\SocialUserResolver::class,
    'providers' => [
        'google' => ['enabled' => true, 'label' => 'Google'],
        'github' => ['enabled' => true, 'label' => 'GitHub'],
    ],
],
'client_auth' => [
    'guards' => [], // additional guards; default and API guards are included
    'user_fields' => ['name', 'username'], // canonical id is always included
],
```

The routes use Laravel's `web` middleware (session, cookies and CSRF), with
throttling on login and provider endpoints. Routes are named and cacheable:

| Method | Default path | Route name |
| --- | --- | --- |
| GET | `/auth/session` | `saola.auth.session` |
| POST | `/auth/login` | `saola.auth.login` |
| POST | `/auth/logout` | `saola.auth.logout` |
| GET | `/auth/{provider}/redirect` | `saola.auth.redirect` |
| GET | `/auth/{provider}/callback` | `saola.auth.callback` |

After changing route configuration, rebuild the application's route cache.
`password => false` disables password login and sends `endpoints.login: null`.
The API requires a stateful Laravel guard and its configured user provider.
It does not add user tables, registration, password reset, email verification,
or a login page to the application.

Successful session/login/logout responses have the same contract:

```json
{
  "auth": {
    "version": 1,
    "defaultGuard": "web",
    "guards": {"web": {"user": {"name": "Lan", "username": "lan", "id": 1}}}
  },
  "authClient": {
    "version": 1,
    "guard": "web",
    "csrfToken": "current-session-csrf-token",
    "usernameField": "email",
    "endpoints": {"session": "/auth/session", "login": "/auth/login", "logout": "/auth/logout"},
    "providers": [{"name": "google", "label": "Google", "url": "/auth/google/redirect"}]
  }
}
```

Guests have `user: null`. The initial SSR config contains both objects in
`APP_CONFIGS.shared`; no private user model or OAuth token is serialized.
The configured login field is independent of public user fields: accepting
email credentials does not expose email in the returned user. JSON responses
are private and not cached. Login rotates the session ID and CSRF token;
logout invalidates the session and issues a fresh CSRF token. Invalid
credentials return 422, CSRF failure 419, and throttled requests 429.

## Google, GitHub and additional providers

Install Socialite in the application when using the built-in adapter:

```sh
composer require laravel/socialite
```

Set the provider credentials in `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => '/auth/google/callback',
],
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => '/auth/github/callback',
],
```

The adapter uses the absolute named callback route for each request, overriding
the `redirect` value above. Register that exact URL in the provider console:
`route('saola.auth.callback', ['provider' => 'google'])` (and GitHub equivalent).
Use the application's public origin and configured trusted proxies in production.
Credentials remain on the server; only the enabled provider's name, label and
local redirect URL reach the browser.

The application must implement `Saola\Core\Auth\SocialUserResolver`. This lets
it use its own user model and external identity table. For example, with an
application-owned `SocialAccount` model having `provider`, `provider_user_id`
and a `user` relationship:

```php
namespace App\Auth;

use App\Models\SocialAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Saola\Core\Auth\OAuthIdentity;
use Saola\Core\Auth\SocialUserResolver as Resolver;

final class SocialUserResolver implements Resolver
{
    public function resolve(string $provider, OAuthIdentity $identity, Request $request): ?Authenticatable
    {
        return SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $identity->id)
            ->first()?->user;
    }
}
```

Bind the interface in a service provider instead of configuring its class if
preferred. A missing resolver fails before redirecting away. Returning `null`
rejects sign-in with 403. The example only signs in linked accounts; provisioning
new users and linking identities are application policy. Use the provider plus
stable provider ID, enforce a unique constraint, and do not link an existing
account solely because an email address matches.

`OAuthIdentity` includes ID and optional name, nickname and email for server-side
resolution. It excludes access/refresh tokens. The default Socialite adapter
uses stateful OAuth, validates state, and creates a fresh driver per request.
The pending flow expires after ten minutes and is consumed on callback;
callbacks cannot be replayed. Starting a second OAuth flow replaces the first
flow in that session. The `next` destination must be a local path; external
destinations fall back to `/`. Provider cancellation/verification failure returns
422 without returning provider tokens, codes or raw errors.

Other installed Socialite OAuth2 drivers can be enabled under `providers` with a
`driver` name and optional `scopes` array. For providers requiring another flow,
set `adapter` to an implementation of `Saola\Core\Auth\OAuthProvider`; it must
validate the provider's callback/state before returning `OAuthIdentity`.

## Application-owned authentication

Bind `Saola\Core\Auth\PasswordAuthenticator` to replace password authentication
while keeping validation, throttling, session rotation and response contracts.
An accepted attempt must authenticate the supplied guard before returning true.
Applications with MFA or additional challenges should use their own endpoint
flow and report auth only after the final server confirmation.

Alternatively leave `auth_api.enabled` false and keep all existing auth routes.
The public SSR/page-data snapshot and client AuthService still work. After a
custom endpoint succeeds, call `App.Auth.login(publicUser)`, `logout()` or
`sync(snapshot)`. These methods update the local auth service and emit
`login`, `logout`, `change`; they do not create a server session. Standard
`App.Auth.client.login/logout/refresh` calls do both HTTP and synchronization.

See [client auth usage](../../client/docs/AUTH.md) for subscriptions, reactive
layouts, provider redirects and custom integrations.
