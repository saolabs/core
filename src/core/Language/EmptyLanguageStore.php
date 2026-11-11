<?php

declare(strict_types=1);

namespace Saola\Core\Language;

final class EmptyLanguageStore implements LanguageStore
{
    public function snapshot(string $locale): array
    {
        return ['revision' => 0, 'messages' => []];
    }
}
