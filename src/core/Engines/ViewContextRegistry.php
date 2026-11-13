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

    /** @var array<string, list<\Closure(string): array>> */
    private array $sharers = [];

    private ?\Saola\Core\Contracts\SiteProvider $siteProvider = null;

    public function setSiteProvider(?\Saola\Core\Contracts\SiteProvider $provider): void
    {
        $this->siteProvider = $provider;
    }

    public function siteProvider(): ?\Saola\Core\Contracts\SiteProvider
    {
        return $this->siteProvider;
    }

    public function addSharer(string $context, \Closure $resolver): void
    {
        $this->sharers[$context][] = $resolver;
    }

    /** @return list<\Closure(string): array> */
    public function sharers(string $context): array
    {
        return $this->sharers[$context] ?? [];
    }

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
