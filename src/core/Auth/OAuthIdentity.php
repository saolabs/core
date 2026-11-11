<?php

namespace Saola\Core\Auth;

/** Server-only identity. OAuth access/refresh tokens never enter the client snapshot. */
final class OAuthIdentity
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $nickname = null,
        public readonly ?string $email = null,
    ) {
        if ($id === '') throw new \InvalidArgumentException('OAuth identity requires a provider subject.');
    }
}
