<?php

namespace Saola\Core\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface SocialUserResolver
{
    /** Resolve by provider + subject; do not silently link existing accounts by email. */
    public function resolve(string $provider, OAuthIdentity $identity, Request $request): ?Authenticatable;
}
