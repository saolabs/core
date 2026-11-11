<?php

namespace Tests\Unit;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\{ClientAuth, ClientShare};
use Saola\Core\Support\Methods\ResponseMethods;
use Tests\TestCase;

class ClientAuthTest extends TestCase
{
    protected function getPackageProviders($app): array { return [SaolaServiceProvider::class]; }

    public function test_boot_user_is_projected_without_private_model_fields(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn ($guard) => new GenericUser([
            'id' => 7, 'name' => 'Lan', 'username' => 'lan', 'email' => 'private@example.test',
            'phone' => 'private-phone', 'phone_number' => 'private-phone-number',
            'password' => 'secret', 'remember_token' => 'private-token', 'api_key' => 'private-key',
        ]));
        $snapshot = app(ClientShare::class)->export($request)['auth'];
        $this->assertSame(1, $snapshot['version']);
        $this->assertSame(['name' => 'Lan', 'username' => 'lan', 'id' => 7], $snapshot['guards']->web['user']);
        $this->assertStringNotContainsString('private', json_encode($snapshot));
        $this->assertStringNotContainsString('secret', json_encode($snapshot));
    }

    public function test_guards_and_user_changes_are_resolved_for_each_request(): void
    {
        config(['sao.client_auth.guards' => ['admin'], 'sao.client_auth.user_fields' => ['username']]);
        $first = Request::create('/first');
        $first->setUserResolver(fn ($guard) => $guard === 'admin' ? new GenericUser(['id' => 'staff', 'name' => 'Admin', 'username' => 'admin', 'email' => 'staff@example.test']) : null);
        $service = app(ClientAuth::class);
        $snapshot = $service->export($first);
        $this->assertNull($snapshot['guards']->web['user']);
        $this->assertSame('staff', $snapshot['guards']->admin['user']['id']);
        $this->assertSame(['username' => 'admin', 'id' => 'staff'], $snapshot['guards']->admin['user']);
        $second = Request::create('/second');
        $second->setUserResolver(fn () => null);
        $this->assertNull($service->export($second)['guards']->admin['user']);
        app('auth')->shouldUse('admin');
        $this->assertSame('admin', $service->export($second)['defaultGuard']);
    }

    public function test_page_data_synchronizes_auth_without_modifying_view_data(): void
    {
        $request = Request::create('/page');
        $this->app->instance('request', $request);
        $request->setUserResolver(fn () => new GenericUser([
            'id' => 9, 'name' => 'Mai', 'username' => 'mai',
            'email' => 'private@example.test', 'phone' => 'private-phone', 'password' => 'secret',
        ]));
        $controller = new class {
            use ResponseMethods;
            public function page() { return $this->jsonResponse(['title' => 'Page']); }
        };
        $payload = $controller->page()->getData(true);
        $this->assertSame(['title' => 'Page'], $payload['data']);
        $this->assertSame(['name' => 'Mai', 'username' => 'mai', 'id' => 9], $payload['auth']['guards']['web']['user']);
        $request->setUserResolver(fn () => null);
        $this->assertNull($controller->page()->getData(true)['auth']['guards']['web']['user']);
    }

    public function test_custom_identifier_columns_are_exported_as_the_canonical_id(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => new class(['account_key' => 'account-1', 'name' => 'Lan']) extends GenericUser {
            public function getAuthIdentifierName() { return 'account_key'; }
        });
        $user = app(ClientAuth::class)->export($request)['guards']->web['user'];
        $this->assertSame('account-1', $user['id']);
        $this->assertArrayNotHasKey('account_key', $user);
    }
}
