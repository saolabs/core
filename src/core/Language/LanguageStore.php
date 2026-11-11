<?php

declare(strict_types=1);

namespace Saola\Core\Language;

interface LanguageStore
{
    /** @return array{revision: int, messages: array} Published values only; no file fallback. */
    public function snapshot(string $locale): array;
}
