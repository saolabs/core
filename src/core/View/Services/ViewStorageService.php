<?php

namespace Saola\Core\View\Services;

use Saola\Core\Concerns\OctaneCompatibleMethods;
use Saola\Core\Contracts\OctaneCompatible;
use Saola\Core\System\OctaneAwareService;

class ViewStorageService implements OctaneCompatible
{
    use OctaneCompatibleMethods;
    protected $jsStorage = [];
    protected $cssStorage = [];
    public function addJs(string $id,string $js)
    {
        $this->jsStorage[$id] = $js;
    }
    public function addCss(string $id,string $css)
    {
        $this->cssStorage[$id] = $css;
    }
    public function getJs()
    {
        return $this->jsStorage;
    }
    public function getCss()
    {
        return $this->cssStorage;
    }
    public function clearJs()
    {
        $this->jsStorage = [];
    }
    public function clearCss()
    {
        $this->cssStorage = [];
    }
}