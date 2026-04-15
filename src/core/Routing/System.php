<?php

namespace Saola\Core\Routing;

use Saola\Core\Routing\Router;

class System
{
    const WEB = 'web';
    const ADMIN = 'admin';
    const API = 'api';
    /**
     * @var array<string,Context|Router>
     */
    private static $contexts = [];
    /**
    * thêm Context
     *
     * @param string $slug
     * @param array $data
     * @return Context
     */
    public static function addContext($slug, $data = [])
    {
        if(!array_key_exists($slug, self::$contexts))
        {
            if(!array_key_exists('slug', $data) || $data['slug'] == null || $data['slug'] == ''){
                $data['slug'] = $slug;
            }
            $data['type'] = 'context';
            $data['context'] = $slug;
            if(!array_key_exists('as', $data) || $data['as'] == null || $data['as'] == ''){
                $data['as'] = $slug;
            }
            self::$contexts[$slug] = new Context($data);
        }
        return self::$contexts[$slug];
    }
    /**
     * lấy context
     *
     * @param string $slug
     * @return Context|null
     */
    public static function getContext($slug)
    {
        if(!array_key_exists($slug, self::$contexts))
        {
            return null;
        }
        return self::$contexts[$slug];
    }
    /**
     * lấy tất cả Context
     *
     * @return array<string, Context>
     */
    public static function getContexts()
    {
        return self::$contexts;
    }

    /**
     * lấy Context hoặc tạo mới
     *
     * @param string $slug
     * @param array $defaultData
     * @return Context
     */
    public static function context($slug, $defaultData = [])
    {
        return ($context = self::getContext($slug)) ? $context : self::addContext($slug, $defaultData);
    }



    public static function pushLaravelRoute($context){

    }

    /**
     * lấy context admin
     *
     * @return Context
     */
    public static function admin($defaultData = []){
        return self::context(self::ADMIN, $defaultData);
    }

    /**
     * lấy context web
     *
     * @return Context
     */
    public static function web($defaultData = []){
        return self::context(self::WEB, $defaultData);
    }

    /**
     * lấy context api
     *
     * @return Context
     */
    public static function api($defaultData = []){
        return self::context(self::API, $defaultData);
    }

}