<?php

namespace Saola\Core\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;

class SessionPasswordAuthenticator implements PasswordAuthenticator
{
    public function attempt(Request $request, StatefulGuard $guard, array $credentials, bool $remember): bool
    {
        return $guard->attempt($credentials, $remember);
    }
}
