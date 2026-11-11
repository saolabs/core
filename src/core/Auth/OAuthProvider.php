<?php

namespace Saola\Core\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface OAuthProvider
{
    public function redirect(Request $request, string $callbackUrl): RedirectResponse;

    /** Must validate the callback state against the initiating browser session. */
    public function user(Request $request, string $callbackUrl): OAuthIdentity;
}
