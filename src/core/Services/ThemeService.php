<?php

namespace Saola\Core\Services;

class ThemeService extends Service
{
    
    protected $theme = '';
    protected $themeFolder = 'themes';
    protected $module = '';
    protected $viewFolder = '';

    protected $data = []; // data for the view
    protected $context = 'web'; // global, admin, web, account

    protected $themeType = 'static'; // static, dynamic

    protected $viewMap = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
        
        return $this;
    }

    public function getViewPath()
    {
        return $this->themeType == 'static' ? "$this->themeFolder." : "$this->themeFolder/$this->theme/";
    }



    public function render($view, $data = [])
    {
        return view($view, $data);
    }
}