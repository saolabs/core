<?php

namespace Saola\Core\View\Services;

use Illuminate\Support\Facades\App;

class ViewContextService
{
    protected array $stack = [];

    public function push(string $view, string $id): void
    {
        $this->stack[] = compact('view', 'id');
    }

    public function pop(): ?array
    {
        return array_pop($this->stack);
    }

    public function current(): ?array
    {
        return end($this->stack) ?: null;
    }

    public function parent(): ?array
    {
        $copy = $this->stack;
        array_pop($copy);
        return end($copy) ?: null;
    }

    public function cleanup(): void
    {
        $this->stack = [];
    }

    public static function instance(): self
    {
        return App::make(self::class);
    }
}