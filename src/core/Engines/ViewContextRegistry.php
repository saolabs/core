<?php

namespace Saola\Core\Engines;

/**
 * Worker-wide registry for stable view context definitions.
 *
 * Request-specific choices (for example the active tenant theme) must live in
 * ViewContextManager, which is registered as a scoped service.
 */
final class ViewContextRegistry
{
    private array $contexts = [];

    private string $defaultContext = '';

    public function get(string $context): ?array
    {
        return $this->contexts[$context] ?? null;
    }

    public function put(string $context, array $config): void
    {
        $this->contexts[$context] = $config;
    }

    public function has(string $context): bool
    {
        return isset($this->contexts[$context]);
    }

    public function names(): array
    {
        return array_keys($this->contexts);
    }

    public function setDefaultContext(string $context): void
    {
        $this->defaultContext = $context;
    }

    public function getDefaultContext(): string
    {
        return $this->defaultContext;
    }
}
