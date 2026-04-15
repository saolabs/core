<?php

namespace Saola\Core\Html;

use Saola\Core\Laravel\Router;
use Saola\Core\Files\Filemanager;
use Saola\Core\Magic\Arr;

trait InputTypes
{
    /**
     * @var boolean
     */
    protected $isPrepare = false;

    protected $d = null;

    protected static $templateConfig = [
        'switch'             => ['type' => ['checkbox', 'switch'], 'prepare' => 'prepareCrazySwitchProp'],
        'checklist'          => ['type' => ['checklist', 'select'], 'prepare' => 'prepareChecklistData'],
        'options'            => ['type' => ['radio', 'option'], 'prepare' => ''],
        'date'               => ['type' => ['text', 'date'], 'prepare' => ''],
        'time'               => ['type' => ['text', 'time'], 'prepare' => ''],
        'crazyslug'          => ['type' => ['text', 'slug', 'crazyslug'], 'prepare' => 'prepareCrazySlugData'],
        'daterange'          => ['type' => ['text', 'date', 'daterange'], 'prepare' => ''],
        'touchspin'          => ['type' => ['text', 'number', 'touchspin'], 'prepare' => ''],
        'deepselect'         => ['type' => ['deepselect', 'select'], 'prepare' => ''],
        'crazyselect'        => ['type' => ['crazyselect', 'select'], 'prepare' => 'prepareCrazySelectData'],
        'dateselect'         => ['type' => ['date', 'text', 'dateselect'], 'prepare' => 'prepareDateSelectData'],
        'crazytag'           => ['type' => ['crazytag', 'select'], 'prepare' => 'prepareCrazyTagData'],
        'multiselect'        => ['type' => ['multiselect', 'select'], 'prepare' => ''],
        'select2'            => ['type' => ['select', 'select2'], 'prepare' => ''],
        'cropit'             => ['type' => ['file', 'imagefile'], 'prepare' => ''],
        'crazyprop'          => ['type' => ['textarea', 'crazyprop', 'crazyInput', 'array'], 'prepare' => 'prepareCrazyPropData'],
        'specification'      => ['type' => ['textarea', 'specification', 'crazySpecification', 'array'], 'prepare' => 'prepareCrazySpecificationData'],
        'tinymce'            => ['type' => ['textarea', 'tinymce'], 'prepare' => ''],
        'gallery'            => ['type' => ['inputgallery', 'crazygallery', 'gallery', 'image', 'file'], 'prepare' => 'prepareCrazyGalleryData'],
        'videopreview'       => ['type' => ['text', 'url'], 'prepare' => ''],
        'attribute'          => ['type' => ['attribute', 'select', 'textarea'], 'prepare' => 'prepareCrazyAttributeData'],
        'variant-attribute'  => ['type' => ['attribute', 'select', 'textarea', 'variant-attribute'], 'prepare' => 'prepareCrazyVariantAttributeData'],
        'product'            => ['type' => ['product', 'select', 'textarea'], 'prepare' => 'prepareCrazyProductData'],
        'colorpicker'        => ['type' => ['text', 'colorpicker', 'color'], 'prepare' => 'prepareCrazyColorPicker'],
        'iconpicker'         => ['type' => ['text', 'iconpicker', 'icon'], 'prepare' => 'prepareCrazyIconPicker'],
        'checkmultilevel'    => ['type' => ['checkbox', 'checklist', 'checkmultilevel', 'textarea'], 'prepare' => 'prepareCheckMultiLevelData'],
        'ckeditor'           => ['type' => ['textarea', 'ckeditor'], 'prepare' => ''],
        'area'               => ['type' => ['textarea', 'area', 'hidden', 'text'], 'prepare' => 'prepareAreaData'],
        'colorselect'        => ['type' => ['radio', 'colorselect'], 'prepare' => 'prepareColorSelectData'],
        'affiliate'          => ['type' => ['text', 'textarea', 'affiliate'], 'prepare' => ''],
        'media'              => ['type' => ['inputmedia', 'crazymedia', 'media', 'image', 'file'], 'prepare' => 'prepareCrazyMediaData'],
        'seo'                => ['type' => ['inputseo', 'crazyseo', 'seo'], 'prepare' => 'prepareSEOData'],
        'content-seo'        => ['type' => ['content-seo', 'contentseo', 'seocontent', 'seo-content'], 'prepare' => 'prepareContentSEOData'],
        'user-select'        => ['type' => ['user-select', 'seo-content'], 'prepare' => 'prepareUserSelectData'],
    ];




    public static function checkSupportTemplate($template, $type)
    {

        return ($template && $type) ? (array_key_exists($template, static::$templateConfig)
            && (
                (is_string(static::$templateConfig[$template]['type']) && static::$templateConfig[$template]['type'] == $type
                )
                || (is_array(static::$templateConfig[$template]['type'])
                    && in_array($type, static::$templateConfig[$template]['type'])
                )
            )
        ) : false;
    }


    /**
     * thêm template
     *
     * @param string $template
     * @param string|array<int, string> $type
     * @param \Closure|string $prepare
     * @return void
     */
    public static function addTemplate($template, $type = null, $prepare = null)
    {
        if(array_key_exists($template, static::$templateConfig)){
            if(is_array($type)){
                static::$templateConfig[$template]['type'] = array_merge(static::$templateConfig[$template]['type'], $type);
            }elseif($type){
                static::$templateConfig[$template]['type'][] = $type;
            }
            if($prepare){
                if(!is_array(static::$templateConfig[$template]['prepare'])){
                    static::$templateConfig[$template]['prepare'] = [static::$templateConfig[$template]['prepare'], $prepare];
                }
                else{
                    static::$templateConfig[$template]['prepare'][] = $prepare;
                }
            }
        }else{
            static::$templateConfig[$template] = [
                'type' => is_array($type) ? $type : [$type],
                'prepare' => $prepare
            ];
        }
    }


    public function checkDefaultValueDynamic()
    {
        if (!is_array($db = $this->hiddenData('defaultBy'))) return;
        $b = new Arr($db);
        $cf = null;
        if ($a = $b->get('action')) {
            $cf = $a;
        } elseif ($c = $b->get('call')) {
            $cf = $c;
        } elseif ($f = $b->get('func')) {
            $cf = $f;
        }
        $vl = null;

        if (is_callable($cf)) {

            if ($p = $b->get('params')) {
                // dd($p);
                $params = [];
                if (!is_array($p)) $p = [$p];


                foreach ($p as $param) {


                    if (is_string($param)) {

                        $params[] = $this->getParamFromString($param);
                    } elseif (is_array($param)) {
                        $arg = [];
                        foreach ($param as $kn => $par) {
                            $arg[$kn] = $this->getParamFromString($par);
                        }
                        $params[] = $arg;
                    } else {
                        $params[] = $param;
                    }
                }
                $vl = call_user_func_array($cf, $params);
            } else {
                $vl = call_user_func($cf);
            }
        }
        $this->_data['default'] = $vl;
        return $vl;
    }

    /**
     * chuan bị cho cac the loai crazy input
     * @return void
     */
    public function prepareCrazyInput()
    {
        if ($this->isPrepare) return false;
        if (array_key_exists($this->type, static::$templateConfig)) $this->template = $this->type;
        $this->d = new Arr($this->hiddenData());
        // if(($this->defVal() == null) && $v = request($this->name)) $this->value = old($this->name, $v);
        if (array_key_exists($this->template, static::$templateConfig)) {
            if(array_key_exists('prepare', static::$templateConfig[$this->template]) && static::$templateConfig[$this->template]['prepare']){
                if(is_string(static::$templateConfig[$this->template]['prepare'])){
                    call_user_func_array([$this, static::$templateConfig[$this->template]['prepare']], []);
                }
                elseif(is_array(static::$templateConfig[$this->template]['prepare'])){
                    foreach (static::$templateConfig[$this->template]['prepare'] as $prepare) {
                        if($prepare){
                            if(is_string($prepare) && is_callable([$this, $prepare])){
                                call_user_func_array([$this, $prepare], []);
                            }elseif(is_callable($prepare)){
                                call_user_func_array($prepare, []);
                            }
                        }
                    }
                }
                elseif(is_callable(static::$templateConfig[$this->template]['prepare'])){
                    call_user_func_array(static::$templateConfig[$this->template]['prepare'], [$this]);
                }
            }
            
            $this->isPrepare = true;
        } else {
            $this->parseTypeTemplate();
        }
    }

    public function parseTypeTemplate()
    {
        if (array_key_exists($this->type, static::$templateConfig)) {
            $this->template = $this->type;
            $this->isPrepare = true;
        }
    }


    /**
     * convert sang crazy select
     *
     */
    public function prepareCrazySelectData()
    {

        $this->template = 'crazyselect';
        $this->id = $this->id ? $this->id : $this->name;
        $this->data('id', $this->id);
        // nếu có search url trực tiếp
        $this->parseRouteUrl('search');
        if ($sf = $this->hidden('search-field')) {
            $this->data('search-field', $sf);
        } else {
            $this->data('search-field', 'search');
        }
        $this->parseDataEvent('change');

        if ($sp = $this->hidden('search-params')) {
            $this->data('search-params', $sp);
        }


        if ($this->parseRouteUrl('add')) {
            if ($af = $this->hidden('add-field')) {
                $this->data('add-field', $af);
            } else {
                $this->data('add-field', 'name');
            }

            if ($add_params = $this->hidden('add-params')) {
                $arr = [];
                if ($params = $this->parseInputParams($add_params)) {
                    $this->data('add-params', $params);
                }
            }
        }
        if (in_array(strtolower($type = $this->hidden('select-type')), ['dynamic', 'search'])) {
            $this->data('select-type', $type);
            $this->data('advance-click', $this->hidden('advance-click'));
            if ($at = $this->hidden('advance-text')) {
                $this->data('advance-text', $at);
            } else {
                $this->data('advance-text', 'Thêm');
            }
        } else {
            $this->data('select-type', 'static');
        }

        if (in_array(strtolower($typ = $this->hidden('label-type')), ['header', 'value', 'label'])) {
            $this->data('label-type', $typ);
        } else {
            $this->data('label-type', 'label');
        }

        if ($cc = $this->hidden('confirm-change')) {
            $this->data('confirm-change', $cc);
        }
        if ($dis = $this->hidden('disable-search')) {
            $this->data('disable-search', 'true');
        }
        
    }




    public function prepareCheckMultiLevelData()
    {

        $this->template = 'checkmultilevel';
        $this->data('id', $this->id);
        $this->parseDataEvent('check');

        if (in_array(strtolower($typ = $this->hidden('label-type')), ['header', 'value', 'label'])) {
            $this->data('label-type', $typ);
        } else {
            $this->data('label-type', 'label');
        }

        if ($cc = $this->hidden('confirm-change')) {
            $this->data('confirm-change', $cc);
        }
    }

    public function prepareColorSelectData()
    {

        $this->template = 'colorselect';
        $this->data('id', $this->id);
        $this->parseDataEvent('check');

        if (!$this->data) {
            if ($colors = $this->hiddenData('colors')) {
                $this->data = $colors;
            }
        }
    }

    public function prepareDateSelectData()
    {

        $this->template = 'dateselect';
        $this->data('id', $this->id);
        $this->parseDataEvent('change');
        $this->parseDataEvent('day-change');
        $this->parseDataEvent('year-change');
        $this->parseDataEvent('month-change');
        if ($sortType = $this->hidden('sort-type')) {
            $st = strtolower($sortType) == 'en' ? 'en' : 'vi';
            $this->data('sort-type', $st);
        }
        if ($dis = $this->hidden('disable-search')) {
            $this->data('disable-search', 'true');
        }

        if ($val = $this->defVal()) {
            if (is_string($val)) {
                $this->val(strtodate($val));
            }
        }
    }


    public function prepareAreaData()
    {

        $this->template = 'area';
        $this->data('id', $this->id);
    }

    public function prepareChecklistData()
    {

        $this->template = 'checklist';
        $this->data('id', $this->id);
    }



    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyTagData()
    {
        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('crazy-tag');
        // nếu có search url trực tiếp
        $this->parseRouteUrl('search');

        if ($sf = $this->hidden('search-field')) {
            $this->data('search-field', $sf);
        } else {
            $this->data('search-field', 'search');
        }
        if ($searchParams = $this->hidden('search-params')) {
            $this->data('search-params', $searchParams);
        }

        $this->parseDataEvent('add');
        $this->parseDataEvent('remove');
        $this->parseDataEvent('create');




        if ($this->parseRouteUrl('create')) {
            if ($af = $this->hidden('create-field')) {
                $this->data('create-field', $af);
            } else {
                $this->data('create-field', 'name');
            }

            if ($create_params = $this->hidden('create-params')) {
                $arr = [];
                if ($params = $this->parseInputParams($create_params)) {
                    $this->data('create-params', $params);
                }
            }
        }

        if (in_array(strtolower($type = $this->hidden('type')), ['dynamic', 'search', 'default'])) {
            $this->data('type', $type);
        } else {
            $this->data('type', 'default');
        }

        $this->data('value-key', $this->hidden('value-key') ?? 'id');
        $this->data('text-key', $this->hidden('text-key') ?? 'name');

        $this->addClass($this->data('type'));
    }



    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyAttributeData()
    {
        $this->data('id', str_slug($this->id ?? $this->name, '-'));
        $this->data('name', $this->name);
        $this->addClass('crazy-attribute');
        $this->parseRouteUrl('load');
        $this->data('load-param-selectors', $this->hidden('load-param-selectors'));

        $this->parseRouteUrl('add-value');
        $this->parseRouteUrl('detail');


        $this->parseDataEvent('add');
        $this->parseDataEvent('remove');
    }
    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyVariantAttributeData()
    {
        $this->data('id', str_slug($this->id ?? $this->name, '-'));
        $this->data('name', $this->name);
        $this->addClass('crazy-attribute');
        $this->parseRouteUrl('load');
        $this->data('load-param-selectors', $this->hidden('load-param-selectors'));

        $this->parseRouteUrl('add-value');
        $this->parseRouteUrl('detail');

        $this->parseDataEvent('add');
        $this->parseDataEvent('remove');
    }



    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyProductData()
    {
        $this->data('id', str_slug($this->id ?? $this->name, '-'));
        $this->data('name', $this->name);
        $this->addClass('crazy-products');

        $this->parseRouteUrl('add');
        $this->data('add-param-selectors', $this->hidden('add-param-selectors'));

        $this->parseDataEvent('add');
        $this->parseDataEvent('remove');
    }





    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyPropData()
    {
        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('crazy-prop');
    }

    public function prepareUserSelectData()
    {
        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('user-select');
        $this->template = 'user-select';
    }
    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazySpecificationData()
    {
        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('crazy-specification');
    }
    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyGalleryData()
    {
        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('crazy-gallery input-gallery');
    }

    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazyMediaData()
    {
        $this->data('id', $this->id);
        $this->template = 'media';
        $this->data('name', $this->name);
        $this->addClass('crazy-library input-library');
    }



    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazySlugData()
    {

        $this->data('id', $this->id);
        $this->data('name', $this->name);
        $this->addClass('crazy-slug');
        $this->data('check-field', $this->hidden('check-field') ?? 'custom_slug');

        $this->parseDataEvent('check');
        $this->data('extension', $this->hidden('extension'));
        $this->data('slug-field', $this->hidden('slug-field') ?? 'slug');
        $this->data('source-id', $this->hidden('source-id'));
        $this->data('ajax-param-selectors', $this->hidden('ajax-param-selectors'));
        $this->data('ajax-get-name', $this->hidden('ajax-get-name'));
        $this->data('ajax-check-name', $this->hidden('ajax-check-name'));
        foreach (['get-slug', 'check-slug'] as $key) {
            if ($this->parseRouteUrl($key)) {
                if ($slug_params = $this->hidden($key . '-params')) {
                    if ($params = $this->parseInputParams($slug_params)) {
                        $this->data($key . '-params', $params);
                    }
                }
            }
        }
    }


    /**
     * convert sang crazy tag
     *
     */
    public function prepareCrazySwitchProp()
    {

        $this->data('id', str_slug($this->id ?? $this->name, '-'));
        $this->data('name', $this->name);
        $this->addClass('crazy-switch');
        $this->parseDataEvent('check');
        $this->parseDataEvent('uncheck');
        $this->parseDataEvent('change');
    }


    public function prepareCrazyColorPicker()
    {
        $this->template = 'colorpicker';
    }

    public function prepareCrazyIconPicker()
    {
        $this->template = 'iconpicker';
    }



    /**
     * fill route url
     * @param string $key
     * @param string
     */
    public function parseRouteUrl(string $key)
    {
        $url = null;

        // nếu có search url trực tiếp
        if ($a = $this->hidden($key . '-url')) {
            $url = $a;
        }
        // nếu dùng route
        elseif ($ar = $this->hidden($key . '-route')) {
            if (Router::getByName($ar)) {
                if ($arp = $this->hidden($key . '-route-params')) {
                    if (is_array($arp)) {
                        $url = route($ar, $arp);
                    } else {
                        $url = route($ar, $this->parseRouteParams($arp));
                    }
                } else {
                    $url = route($ar, $arp);
                }
            }
        } elseif ($get_url = $this->hidden('get-' . $key . '-url')) {
            if (is_callable($get_url)) {
                if ($gp = $this->hidden('get-' . $key . '-url-param')) {
                    $url = $get_url($this->getInputDataFromString($gp));
                } elseif ($gps = $this->hidden('get-' . $key . '-url-params')) {
                    if ($gpsx = $this->parseInputParams($gps)) {
                        if (Arr::isNumericKeys($gpsx)) {
                            $url = $get_url(...$gpsx);
                        } else {
                            $url = $get_url($gpsx);
                        }
                    } else {
                        $url = $get_url();
                    }
                } else {
                    $url = $get_url();
                }
            }
        }

        if ($url) {
            $this->data($key . '-url', $url);
        }
        return $url;
    }




    /**
     * them event data
     * @param string $key
     * @return mixed
     */
    public function parseDataEvent($key)
    {
        foreach ([$key, 'on' . ucfirst($key), 'on-' . $key, rtrim($key, 'e') . 'ed', $key . '-callback'] as $k) {
            if ($c = $this->hidden($k)) {
                $this->data('on-' . $key, $c);
                return true;
            }
        }
        return false;
    }





    /**
     * taich lấy param
     * @param mixed $raw
     * @return array
     */
    public function parseRouteParams($raw = null)
    {
        $data = [];
        if (is_array($raw)) {
            $data = $raw;
        } elseif (is_callable($raw)) {
            $data = $raw();
        } elseif (in_array($s = substr($raw, 0, 1), ['@', ':'])) {
            if ($s == '@' && $this->parent) {
                $inp = substr($raw, 1);
                $prop = null;
                if (count($ip = explode(':', $inp)) == 2) {
                    $inp = $ip[0];
                    $prop = $ip[1];
                }
                if ($this->parent->{$inp}) {
                    if ($prop) $val = $this->parent->{$inp}->{$prop};
                    else $val = $this->parent->{$inp}->defVal();
                    $data[$inp] = $val;
                }
            } elseif ($s == ':') {
                $prop = substr($raw, 1);
                $data[$prop] = $prop;
            }
        }

        return $data;
    }


    /**
     * taich lấy param
     * @param mixed $raw
     * @return array
     */
    public function parseInputParams($raw = null)
    {
        $data = [];
        if (is_array($raw)) {
            foreach ($raw as $key => $value) {
                $data[$key] = $this->getInputDataFromString($value);
            }
        } elseif (is_callable($raw)) {
            $data = $raw();
        } elseif ($raw) {
            $data = $this->getInputDataFromString($raw);
        }
        return $data;
    }

    public function getInputDataFromString($raw)
    {
        if (in_array($s = substr($raw, 0, 1), ['#', ':', '@'])) {
            $nsp = substr($raw, 1);
            $arrParams = explode('|', $nsp);
            $nsp = array_shift($arrParams);
            
            if ($s == '#' && $this->parent) {
                $prop = null;
                $inp = $nsp;
                if (count($ip = explode(':', $nsp)) == 2) {
                    $inp = $ip[0];
                    $prop = $ip[1];
                }
                if ($this->parent->{$inp}) {
                    if ($prop) $val = $this->parent->{$inp}->{$prop};
                    else $val = $this->parent->{$inp}->defVal();
                    return $val;
                }
            } elseif ($s == ':') {
                if ($nsp == 'defval') {
                    $prop = $this->defVal();
                } else {
                    $prop = $this->{$nsp};
                }
                return $prop;
            } elseif (is_callable($nsp)) {
                return $nsp();
            }
            if(count($arrParams)){
                foreach ($arrParams as $key) {
                    if (in_array($s = substr($key, 0, 1), ['#', ':', '@'])){
                        $val = $this->getInputDataFromString($key);
                        if($val !== null){
                            return $val;
                        }
                    }
                    else return $key;
                }
            }
            return null;
        } elseif (is_array($raw)) {
            $data = [];
            foreach ($raw as $key => $value) {
                $data[$key] = $this->getInputDataFromString($value);
            }
            return $data;
        }

        return $raw;
    }

    public function prepareInputGroup()
    {
        # code...
    }


    public function prepareFrontendData()
    {
        $this->template = 'frontend';
    }
    public function preparePackage()
    {
        $this->template = 'package';
    }

    public function prepareSEOData()
    {
        $this->template = 'seo';
    }
    public function prepareContentSEOData()
    {
        $this->template = 'content-seo';
    }
}
