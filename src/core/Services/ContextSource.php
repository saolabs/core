<?php

namespace Saola\Core\Services;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Saola;

/** Source layout selection shared by SSR, build metadata and the client. */
class ContextSource
{
    public function definition(string $context): array
    {
        $key = str_replace('-', '_', $context);
        $configured = config('saola-contexts.modes.'.$key) ?? config('saola-contexts.default', 'auto');
        if (!in_array($configured, ['auto', 'standalone', 'theme', 'package', 'published'], true)) {
            throw new \InvalidArgumentException('Invalid source mode for context '.$context);
        }
        $build = app(BundleManifest::class)->contextBuild($context);
        $mode = $configured === 'auto' ? ($build['mode'] ?? 'auto') : $configured;
        $theme = config('saola-contexts.themes.'.$key) ?? ($build['theme'] ?? null);
        if ($configured !== 'auto' && isset($build['mode']) && $build['mode'] !== $configured) {
            throw new \RuntimeException('Context '.$context.' source mode differs from the build; rebuild the context after changing .env.');
        }
        if ($mode === 'theme') {
            if (!is_string($theme) || !preg_match('/^[a-z][a-z0-9_-]*$/D', $theme)) throw new \InvalidArgumentException('Invalid local theme for context '.$context);
            if (($build['theme'] ?? null) !== $theme) throw new \RuntimeException('Context '.$context.' theme differs from the build; rebuild the context after changing .env.');
        } else $theme = null;
        $namespace = $build['namespace'] ?? $context;
        if (!is_string($namespace) || !preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*$/D', $namespace)) {
            throw new \InvalidArgumentException('Invalid view namespace for context '.$context);
        }
        return ['mode' => $mode, 'theme' => $theme, 'namespace' => $namespace];
    }

    public function isLocal(string $context): bool
    {
        return in_array($this->definition($context)['mode'], ['standalone', 'theme'], true);
    }

    public function apply(string $context): bool
    {
        if (!$this->isLocal($context)) return false;
        $manager = app(ViewContextManager::class);
        $manager->clearContextViews($context);
        $manager->setContextViews($context, $this->definition($context)['namespace']);
        Saola::share('saola.context.'.$context, static fn () => [
            ...app(self::class)->definition($context),
            'bundleKinds' => app(BundleManifest::class)->bundleKinds($context),
        ]);
        return true;
    }
}
