<?php

namespace Saola\Core\Http\Controllers;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\ValidationException;
use Saola\Core\Auth\{AuthApi, PasswordAuthenticator};
use Saola\Core\Services\ClientAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController
{
    public function __construct(private AuthApi $api, private ClientAuth $auth) {}

    public function session(Request $request): JsonResponse
    {
        return response()->json(['auth' => $this->auth->export($request), 'authClient' => $this->api->configuration($request)])
            ->header('Cache-Control', 'no-store, private');
    }

    public function login(Request $request, PasswordAuthenticator $passwords): JsonResponse
    {
        abort_unless(config('sao.auth_api.password', true), 404);
        $field = config('sao.auth_api.username', 'email');
        $data = $request->validate([$field => 'required|string|max:255', 'password' => 'required|string|max:4096', 'remember' => 'sometimes|boolean']);
        $guard = $this->api->guard();
        if (!$passwords->attempt($request, $guard, [$field => $data[$field], 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([$field => [__('auth.failed')]]);
        }
        $request->session()->regenerate();
        return $this->session($request);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->api->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        app('auth')->forgetGuards(); // re-resolve all exported guards from the new session
        return $this->session($request);
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $driver = $this->api->provider($provider);
        $this->api->resolver(); // configuration errors must fail before leaving the application
        $response = $driver->redirect($request, $this->api->route('callback', ['provider' => $provider], true));
        $request->session()->put('saola.auth.oauth', [
            'provider' => $provider,
            'next' => $this->api->returnPath($request->query('next', config('sao.auth_api.redirect', '/'))),
            'expires' => time() + 600,
        ]);
        return $response->setPrivate()->setMaxAge(0);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $driver = $this->api->provider($provider);
        $flow = $request->session()->pull('saola.auth.oauth');
        abort_unless(is_array($flow) && ($flow['provider'] ?? null) === $provider && ($flow['expires'] ?? 0) >= time(), 400, 'Invalid or expired authentication request.');
        abort_if($request->has('error'), 422, 'Social authentication was not completed.');
        try {
            $identity = $driver->user($request, $this->api->route('callback', ['provider' => $provider], true));
        } catch (\Throwable $exception) {
            // Do not expose provider responses, tokens or callback codes in an error page.
            abort(422, 'Unable to verify social authentication.');
        }
        $user = $this->api->resolver()->resolve($provider, $identity, $request);
        abort_unless($user, 403, 'This account cannot sign in.');
        $this->api->guard()->login($user);
        $request->session()->regenerate();
        return redirect($this->api->returnPath($flow['next'] ?? '/'))->header('Cache-Control', 'no-store, private');
    }
}
