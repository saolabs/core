<?php

namespace Saola\Core\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/** Request-scoped projection of Laravel authentication for the client. Never serializes a whole user model. */
class ClientAuth
{
    public function export(Request $request): array
    {
        $default = app('auth')->getDefaultDriver();
        $names = array_values(array_unique([$default, ...config('sao.client_auth.guards', [])]));
        if (config('sao.auth_api.enabled', false) && ($guard = config('sao.auth_api.guard'))) $names[] = $guard;
        $names = array_unique($names);
        $guards = [];
        foreach ($names as $name) {
            $guards[$name] = ['user' => $this->user($request->user($name))];
        }
        return ['version' => 1, 'defaultGuard' => $default, 'guards' => (object) $guards];
    }

    private function user(?Authenticatable $user): ?array
    {
        if (!$user) return null;
        $data = [];
        foreach (config('sao.client_auth.user_fields', ['name', 'username']) as $field) {
            $data[$field] = data_get($user, $field);
        }
        // A canonical id also supports models whose identifier column is not named "id".
        $data['id'] = $user->getAuthIdentifier();
        return $data;
    }
}
