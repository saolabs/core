<?php

namespace Saola\Core\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;

interface PasswordAuthenticator
{
    /** A successful implementation must authenticate the supplied guard. */
    public function attempt(Request $request, StatefulGuard $guard, array $credentials, bool $remember): bool;
}
