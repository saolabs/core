<?php

namespace Saola\Core\Concerns;

trait ModelFileMethods
{
    public function getSecretPath($path = null): string
    {
        return config('saola.file_storage_path', config('sao.file_storage_path', 'storage/app')) . ($path ? '/' . ltrim($path, '/') : '');
    }
}