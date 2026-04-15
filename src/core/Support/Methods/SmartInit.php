<?php

namespace Saola\Core\Support\Methods;

trait SmartInit
{
    protected static array $cachedInitMethods = [];

    /**
     * Lấy danh sách các method init
     * @return array
     */
    protected function getInitMethods()
    {
        $class = static::class;

        if (!isset(self::$cachedInitMethods[$class])) {
            $reflection = new \ReflectionClass($class);
            $methods = $reflection->getMethods(
                \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
            );

            self::$cachedInitMethods[$class] = [];

            foreach ($methods as $method) {
                if (
                    str_starts_with($method->name, 'init') && $method->name != 'init' &&
                    $method->getNumberOfRequiredParameters() === 0
                ) {
                    self::$cachedInitMethods[$class][] = $method->name;
                }
            }
        }


        return self::$cachedInitMethods[$class];
    }

    /**
     * Gọi tất cả các method init
     * @return void
     */
    protected function init()
    {
        foreach ($this->getInitMethods() as $method) {
            $this->{$method}();
        }
    }
}