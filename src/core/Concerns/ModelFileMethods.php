<?php

namespace Saola\Core\Concerns;

trait ModelFileMethods
{
    public function getSecretPath($path = null): string
    {
        return config('one.file_storage_path', 'storage/app') . ($path ? '/' . ltrim($path, '/') : '');
    }
}