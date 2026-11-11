<?php

namespace Tests\Unit;

use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\{Authenticatable, StatefulGuard};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Schema};
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\{SocialiteManager, SocialiteServiceProvider};
use Saola\Core\Auth\{AuthApi, OAuthIdentity, PasswordAuthenticator, SocialUserResolver};
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\ClientShare;
use Tests\TestCase;

class AuthApiUser extends User
{
    protected $table = 'auth_api_users';
    protected $guarded = [];
    public $timestamps = false;
}

class AuthApiResolver implements SocialUserResolver
{
    public array $resolved = [];
    public function resolve(string $provider, OAuthIdentity $identity, Request $request): ?Authenticatable
    {
        $this->resolved[] = [$provider, $identity];
        // A real application looks up a stored (provider, subject) identity here.
        return $identity->id === 'external-123' ? AuthApiUser::first() : null;
    }
}

class EnforceAuthCsrf extends VerifyCsrfToken
{
    protected function runningUnitTests() { return false; }
}

class AuthApiTest extends TestCase
{
    private AuthApiUser $user;
    private MockHandler $oauthHttp;

    protected function getPackageProviders($app): array
    {
        return [SaolaServiceProvider::class, SocialiteServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('auth.providers.users.model', AuthApiUser::class);
        $app['config']->set('sao.auth_api', [
            'enabled' => true, 'prefix' => 'session-auth', 'password' => true, 'username' => 'username',
            'social_user_resolver' => AuthApiResolver::class,
            'providers' => ['google' => ['enabled' => true, 'label' => 'Google'], 'github' => ['enabled' => true, 'label' => 'GitHub']],
        ]);
        foreach (['google', 'github'] as $provider) {
            $app['config']->set('services.'.$provider, [
                'client_id' => 'test-client', 'client_secret' => 'server-only-secret', 'redirect' => '/unused',
            ]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('auth_api_users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('username');
            $table->string('email'); $table->string('phone'); $table->string('password'); $table->rememberToken();
        });
        $this->user = AuthApiUser::create([
            'name' => 'Lan', 'username' => 'lan', 'email' => 'private@example.test', 'phone' => 'private-phone',
            'password' => Hash::make('correct-password'),
        ]);
        $this->app->singleton(AuthApiResolver::class);
        $this->oauthHttp = new MockHandler();
        $http = new Client(['handler' => HandlerStack::create($this->oauthHttp)]);
        $manager = new class($this->app, $http) extends SocialiteManager {
            public function __construct($app, private Client $http) { parent::__construct($app); }
            public function buildProvider($provider, $config) { return parent::buildProvider($provider, $config)->setHttpClient($this->http); }
        };
        $this->app->instance(Factory::class, $manager);
    }

    public function test_session_bootstrap_password_login_and_logout_share_the_public_contract(): void
    {
        $guest = $this->getJson('/session-auth/session')->assertOk()
            ->assertJsonPath('auth.guards.web.user', null)
            ->assertJsonPath('authClient.endpoints.login', '/session-auth/login')
            ->assertJsonPath('authClient.providers.0.url', '/session-auth/google/redirect');
        $this->assertStringContainsString('no-store', $guest->headers->get('Cache-Control'));
        $token = $guest->json('authClient.csrfToken');
        $sessionId = session()->getId();
        $login = $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'correct-password'])
            ->assertOk()->assertJsonPath('auth.guards.web.user', ['name' => 'Lan', 'username' => 'lan', 'id' => $this->user->id]);
        $this->assertNotSame($token, $login->json('authClient.csrfToken'));
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertStringNotContainsString('private@example', $login->getContent());
        $this->assertStringNotContainsString('server-only-secret', $login->getContent());
        // A new request guard must restore the authenticated user from the session.
        app('auth')->forgetGuards();
        $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user.id', $this->user->id);
        $shared = app(ClientShare::class)->export(app('request'));
        $this->assertSame($login->json('auth'), json_decode(json_encode($shared['auth']), true));
        $this->assertSame($login->json('authClient'), $shared['authClient']);
        $logout = $this->postJson('/session-auth/logout')->assertOk()->assertJsonPath('auth.guards.web.user', null);
        $this->assertNotSame($login->json('authClient.csrfToken'), $logout->json('authClient.csrfToken'));
        app('auth')->forgetGuards();
        $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user', null);
        $this->getJson('/session-auth/logout')->assertStatus(405);
    }

    public function test_csrf_is_required_and_failed_login_preserves_the_existing_identity(): void
    {
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->appendMiddlewareToGroup('web', EnforceAuthCsrf::class);
        $token = $this->getJson('/session-auth/session')->json('authClient.csrfToken');
        $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'correct-password'])->assertStatus(419);
        $login = $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'correct-password'], ['X-CSRF-TOKEN' => $token])->assertOk();
        $token = $login->json('authClient.csrfToken');
        $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'wrong'], ['X-CSRF-TOKEN' => $token])->assertStatus(422);
        $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user.id', $this->user->id);
        $this->postJson('/session-auth/logout')->assertStatus(419);
        $this->postJson('/session-auth/logout', [], ['X-CSRF-TOKEN' => $token])->assertOk()->assertJsonPath('auth.guards.web.user', null);
    }

    public function test_password_login_is_rate_limited_and_can_be_disabled(): void
    {
        for ($i = 0; $i < 5; $i++) $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'wrong'])->assertStatus(422);
        $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'wrong'])->assertStatus(429);
        config(['sao.auth_api.password' => false]);
        $this->getJson('/session-auth/session')->assertJsonPath('authClient.endpoints.login', null);
        $this->postJson('/session-auth/login', ['username' => 'different', 'password' => 'wrong'])->assertNotFound();
    }

    public function test_host_can_replace_the_password_authenticator(): void
    {
        $this->app->instance(PasswordAuthenticator::class, new class($this->user) implements PasswordAuthenticator {
            public function __construct(private Authenticatable $user) {}
            public function attempt(Request $request, StatefulGuard $guard, array $credentials, bool $remember): bool
            {
                if ($credentials['password'] !== 'host-accepted') return false;
                $guard->login($this->user, $remember);
                return true;
            }
        });
        $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'host-accepted'])->assertOk()->assertJsonPath('auth.guards.web.user.id', $this->user->id);
    }

    public function test_google_and_github_use_real_stateful_socialite_drivers(): void
    {
        foreach (['google' => 'accounts.google.com', 'github' => 'github.com'] as $provider => $host) {
            $this->postJson('/session-auth/logout')->assertOk();
            $redirect = $this->get('/session-auth/'.$provider.'/redirect?next='.urlencode('/agent/example/new?from=login'));
            $redirect->assertRedirect();
            $url = $redirect->headers->get('Location');
            $this->assertSame($host, parse_url($url, PHP_URL_HOST));
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame(route('saola.auth.callback', ['provider' => $provider]), $query['redirect_uri']);
            $this->assertNotEmpty($query['state']);
            $this->queueIdentity($provider);
            $this->get('/session-auth/'.$provider.'/callback?'.http_build_query(['code' => 'temporary-code', 'state' => $query['state']]))
                ->assertRedirect('/agent/example/new?from=login');
            app('auth')->forgetGuards();
            $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user', ['name' => 'Lan', 'username' => 'lan', 'id' => $this->user->id]);
            $resolved = app(AuthApiResolver::class)->resolved;
            $this->assertSame($provider, $resolved[array_key_last($resolved)][0]);
            $this->assertFalse(property_exists($resolved[array_key_last($resolved)][1], 'token'));
            $this->getJson('/session-auth/'.$provider.'/callback?'.http_build_query(['code' => 'temporary-code', 'state' => $query['state']]))->assertStatus(400);
        }
        $this->assertCount(0, $this->oauthHttp);
    }

    public function test_oauth_rejects_invalid_state_and_provider_without_network_or_login(): void
    {
        $this->get('/session-auth/google/redirect')->assertRedirect();
        $this->getJson('/session-auth/google/callback?code=do-not-echo&state=wrong')->assertStatus(422)->assertDontSee('do-not-echo');
        $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user', null);
        $this->assertCount(0, app(AuthApiResolver::class)->resolved);
        $this->get('/session-auth/google/redirect')->assertRedirect();
        $this->getJson('/session-auth/github/callback')->assertStatus(400);
        $this->getJson('/session-auth/unknown/redirect')->assertNotFound();
        $this->get('/session-auth/google/redirect')->assertRedirect();
        $this->getJson('/session-auth/google/callback?error=access_denied')->assertStatus(422);
    }

    public function test_oauth_denied_mapping_expiry_and_unsafe_return_urls(): void
    {
        $url = $this->get('/session-auth/google/redirect?next=https://evil.test')->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->queueIdentity('google', 'unlinked-id');
        $this->getJson('/session-auth/google/callback?'.http_build_query(['code' => 'code', 'state' => $query['state']]))->assertStatus(403);
        $this->getJson('/session-auth/session')->assertJsonPath('auth.guards.web.user', null);
        $this->withSession(['saola.auth.oauth' => ['provider' => 'google', 'expires' => time() - 1]])
            ->getJson('/session-auth/google/callback')->assertStatus(400);
        $api = app(AuthApi::class);
        foreach (['https://evil.test', '//evil.test', '/%2fevil.test', '/\\evil.test', "/\nevil.test", ['bad']] as $path) {
            $this->assertSame('/', $api->returnPath($path));
        }
        $this->assertSame('/agent/a?x=1#section', $api->returnPath('/agent/a?x=1#section'));
        $url = $this->get('/session-auth/google/redirect?next='.urlencode('//evil.test'))->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->queueIdentity('google');
        $this->get('/session-auth/google/callback?'.http_build_query(['code' => 'code', 'state' => $query['state']]))->assertRedirect('/');
    }

    public function test_a_non_default_api_guard_is_included_in_the_shared_snapshot(): void
    {
        config(['auth.guards.members' => ['driver' => 'session', 'provider' => 'users'], 'sao.auth_api.guard' => 'members']);
        $this->postJson('/session-auth/login', ['username' => 'lan', 'password' => 'correct-password'])
            ->assertOk()->assertJsonPath('authClient.guard', 'members')->assertJsonPath('auth.guards.web.user', null)
            ->assertJsonPath('auth.guards.members.user.id', $this->user->id);
        $this->postJson('/session-auth/logout')->assertJsonPath('auth.guards.members.user', null);
    }

    private function queueIdentity(string $provider, string $id = 'external-123'): void
    {
        $this->oauthHttp->append(new Response(200, [], json_encode(['access_token' => 'server-access-token', 'token_type' => 'Bearer'])));
        $this->oauthHttp->append(new Response(200, [], json_encode($provider === 'google'
            ? ['sub' => $id, 'name' => 'External', 'email' => 'external-private@example.test', 'email_verified' => true]
            : ['id' => $id, 'node_id' => 'node', 'login' => 'external', 'name' => 'External', 'avatar_url' => 'https://example.test/avatar'])));
        if ($provider === 'github') $this->oauthHttp->append(new Response(200, [], json_encode([['email' => 'external-private@example.test', 'primary' => true, 'verified' => true]])));
    }
}
