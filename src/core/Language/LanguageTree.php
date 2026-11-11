<?php

declare(strict_types=1);

namespace Saola\Core\Language;

use InvalidArgumentException;

/** One tree contract for PHP sources, DB snapshots and JSON exchange. */
final class LanguageTree
{
    public static function flatten(array $tree, string $prefix = '', int $depth = 0): array
    {
        if ($depth > 32) {
            throw new InvalidArgumentException('Language tree exceeds 32 levels.');
        }
        $flat = [];
        foreach ($tree as $segment => $value) {
            if (!is_string($segment) || $segment === '' || str_contains($segment, '.') || preg_match('/\s/u', $segment)
                || in_array($segment, ['__proto__', 'constructor', 'prototype'], true)) {
                throw new InvalidArgumentException('Invalid language key segment: '.(string) $segment);
            }
            $key = $prefix === '' ? $segment : $prefix.'.'.$segment;
            if (strlen($key) > 512) {
                throw new InvalidArgumentException('Language key exceeds 512 bytes.');
            }
            if (is_array($value)) {
                $flat += self::flatten($value, $key, $depth + 1);
            } elseif (is_string($value)) {
                $flat[$key] = $value;
            } else {
                throw new InvalidArgumentException("Language value must be a string or keyed array: {$key}");
            }
        }
        return $flat;
    }

    public static function nest(array $flat): array
    {
        $tree = [];
        foreach ($flat as $key => $value) {
            $parts = explode('.', $key);
            $node = &$tree;
            foreach ($parts as $index => $part) {
                if ($index === count($parts) - 1) {
                    if (array_key_exists($part, $node)) {
                        throw new InvalidArgumentException("Conflicting language path: {$key}");
                    }
                    $node[$part] = $value;
                } else {
                    if (array_key_exists($part, $node) && !is_array($node[$part])) {
                        throw new InvalidArgumentException("Conflicting language path: {$key}");
                    }
                    $node[$part] ??= [];
                    $node = &$node[$part];
                }
            }
            unset($node);
        }
        self::flatten($tree);
        return $tree;
    }

    public static function get(array $tree, string $key): ?string
    {
        $value = $tree;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }
        return is_string($value) ? $value : null;
    }
}
