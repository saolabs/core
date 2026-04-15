<?php
namespace Saola\Core\Engines;

use Saola\Core\Repositories\Html\AreaRepository;
use Saola\Core\Repositories\Html\HtmlAreaList;
use Saola\Core\Repositories\Html\Options;
use Saola\Core\Files\Filemanager;
use Saola\Core\Helpers\Arr;

class ViewDataEngine
{
    static $shared = false;

    
    public static function share($name = null, $value=null)
    {
        if(static::$shared) return true;;
        $a = $name?(is_array($name)?$name:(is_string($name)?[$name=>$value]: [])):[];
        view()->share($a);

        static::$shared = true;

        return true;
    }
}
