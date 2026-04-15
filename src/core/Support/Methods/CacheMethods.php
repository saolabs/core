<?php

namespace Saola\Core\Support\Methods;

use Saola\Core\Files\Filemanager;
use Saola\Core\Engines\CacheEngine;

trait CacheMethods
{
    const NO_CACHE_VALUE_RETURN = '--!!!no-cache-value-return!!!--';
    /**
     * @var integer $cacheTime time cache
     */
    protected $cacheTime = 0;
    /**
     * @var string $cacheKey key cache
     */
    protected $cacheKey = '';

    public function initCache()
    {
        $this->setCacheKey(static::class . (isset($this->context) ? '.' . $this->context .(isset($this->module) ? '.' . $this->module : '') : ''));
        return $this;
    }

    public function setCacheKey($key)
    {
        if(is_string($key) && !empty($key)){
            $this->cacheKey = $key;
        }
        return $this;
    }
    public function getCacheKey()
    {
        return $this->cacheKey;
    }
    public function setCacheTime($time)
    {
        $this->cacheTime = $time;
        return $this;
    }
    public function getCacheTime()
    {
        return $this->cacheTime;
    }
    /**
     * lấy cache
     * @param string $key
     * @param array $params
     * @param callable $callback
     * @return mixed
     */
    public function cache($key, $params = [], $callback = null)
    {
        return CacheEngine::cache($key, $params, $callback, $this->cacheTime);
    }
    /**
     * lấy cache
     * @param string $key
     * @param array $params
     * @return mixed
     */
    public function getCached($key, $params = [])
    {
        return CacheEngine::get($key, $params);
    }
    /**
     * lưu cache
     * @param string $key
     * @param mixed $value
     * @param array $params
     * @return void
     */
    public function setCached($key, $value = null, $params = [])
    {
        return CacheEngine::set($key, $value, $this->cacheTime, $params);
    }

    public function cacheMacro($method, $params = [])
    {
        // $name = preg_replace('/Cache$/i', '', $method);
        // if ($name != $method && method_exists($this, $name)) {
        //     return $this->cache($this->cacheKey . '-' . $name, $params, function () use ($params, $name) {
        //         return $this->{$name}(...$params);
        //     }, $this->cacheTime);
        // }

        if (preg_match('/^get([A-Z][a-z0-9_]+)Cache$/i', $method, $matches) && method_exists($this, $name = 'get' . $matches[1])) {
            return $this->cache($this->cacheKey . '-' . $name, $params, function () use ($params, $name) {
                return $this->{$name}(...$params);
            }, $this->cacheTime);
        }

        if (preg_match('/^getCached([A-Z][a-z0-9_]+)$/i', $method, $matches) && method_exists($this, $name = 'get' . $matches[1])) {
            return $this->cache($this->cacheKey . '-' . $name, $params, function () use ($params, $name) {
                return $this->{$name}(...$params);
            }, $this->cacheTime);
        }

        return self::NO_CACHE_VALUE_RETURN;
    }
}
