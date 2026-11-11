<?php

declare(strict_types=1);

namespace Saola\Core\Language;

use InvalidArgumentException;

/** Strict object/string JSON parser: duplicate keys must fail before decoding loses them. */
final class LanguageJson
{
    private int $offset = 0;
    private int $keys = 0;

    private function __construct(private readonly string $json, private readonly int $maxKeys) {}

    public static function decode(string $json, int $maxBytes = 2097152, int $maxKeys = 10000): array
    {
        if (strlen($json) > $maxBytes) throw new InvalidArgumentException('Language JSON exceeds the byte limit.');
        $parser = new self($json, $maxKeys);
        $tree = $parser->object(0);
        $parser->whitespace();
        if ($parser->offset !== strlen($json)) throw new InvalidArgumentException('Unexpected data after language JSON.');
        LanguageTree::flatten($tree);
        return $tree;
    }

    private function object(int $depth): array
    {
        if ($depth > 32) throw new InvalidArgumentException('Language JSON exceeds 32 levels.');
        $this->whitespace();
        if (($this->json[$this->offset++] ?? '') !== '{') throw new InvalidArgumentException('Language JSON must contain objects and string leaves only.');
        $tree = [];
        $this->whitespace();
        if (($this->json[$this->offset] ?? '') === '}') { $this->offset++; return $tree; }
        while (true) {
            $key = $this->string();
            if (array_key_exists($key, $tree)) throw new InvalidArgumentException("Duplicate language JSON key: {$key}");
            if (++$this->keys > $this->maxKeys) throw new InvalidArgumentException('Language JSON exceeds the key limit.');
            $this->whitespace();
            if (($this->json[$this->offset++] ?? '') !== ':') throw new InvalidArgumentException('Missing JSON colon.');
            $this->whitespace();
            $tree[$key] = ($this->json[$this->offset] ?? '') === '{' ? $this->object($depth + 1) : $this->string();
            $this->whitespace();
            $separator = $this->json[$this->offset++] ?? '';
            if ($separator === '}') return $tree;
            if ($separator !== ',') throw new InvalidArgumentException('Missing JSON comma or closing brace.');
        }
    }

    private function string(): string
    {
        $this->whitespace();
        $start = $this->offset;
        if (($this->json[$this->offset++] ?? '') !== '"') throw new InvalidArgumentException('Language JSON keys and leaves must be strings.');
        while ($this->offset < strlen($this->json)) {
            $char = $this->json[$this->offset++];
            if ($char === '\\') { $this->offset++; continue; }
            if ($char === '"') {
                try {
                    return json_decode(substr($this->json, $start, $this->offset - $start), true, 4, JSON_THROW_ON_ERROR);
                } catch (\JsonException $error) {
                    throw new InvalidArgumentException('Invalid UTF-8 or JSON string.', 0, $error);
                }
            }
        }
        throw new InvalidArgumentException('Unterminated JSON string.');
    }

    private function whitespace(): void
    {
        $this->offset += strspn($this->json, " \t\r\n", $this->offset);
    }
}
