<?php

// Read environment only while building Laravel config, so config:cache remains valid.
$modes = [];
$themes = [];
foreach (array_keys($_ENV + $_SERVER) as $name) {
    if (!preg_match('/^SAOLA_([A-Z][A-Z0-9_]*)_(MODE|THEME)$/D', $name, $match)
        || $match[1] === 'CONTEXT' || str_starts_with($match[1], 'DIST_')) continue;
    $context = strtolower($match[1]);
    if ($match[2] === 'MODE') $modes[$context] = env($name);
    else $themes[$context] = env($name);
}

return ['default' => env('SAOLA_CONTEXT_MODE', 'auto'), 'modes' => $modes, 'themes' => $themes];
