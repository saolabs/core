<?php

namespace Saola\Core\Html;

use Saola\Core\Magic\Arr;
use Saola\Core\Files\Filemanager;
use Illuminate\Support\Facades\URL;

class Menu extends HtmlDom
{
    /**
     * @var array $default_props
     */
    protected $default_props = [
        'menu_tag' => 'ul',
        'menu_class' => '',
        'menu_id' => '',

        'item_tag' => 'li',
        'item_class' => '',
        'item_active_class' => 'active',
        'use_active_key' => false,
        'item_attrs' => [],
        'item_has_sub_attrs' => [],
        'has_sub_class' => '',
        'has_sub_active_class' => '',

        'sub_type' => 'default', // 'default', 'mega', 'custom'
        'mega_menu_start' => '',
        'mega_menu_end' => '',


        // link attr
        'link_attrs' => [],
        'link_has_sub_attrs' => [],
        'link_class' => '',
        'link_has_sub_class' => '',
        'link_active_class' => '',

        // badge
        'use_badge' => false,
        'badge_tag' => 'i',
        'badge_data_type' => 'content',
        'badge_class' => '',
        'badge_pos' => 'append_link',
        'badge_prefix_class' => '',
        'badge_attrs' => [],
        'before_badge' => '',
        'after_badge' => '',
        'prepend_badge' => '',
        'appemd_badge' => '',


        // icon
        'use_icon' => false,
        'icon_tag' => 'i',
        'icon_data_type' => 'class',
        'icon_class' => '',
        'icon_pos' => 'prepend_link',
        'icon_prefix_class' => '',
        'icon_attrs' => [],
        'before_icon' => '',
        'after_icon' => '',
        'prepend_icon' => '',
        'appemd_icon' => '',

        // has_sub_icon
        'use_has_sub_icon' => false,
        'has_sub_icon_data_type' => 'class',
        'has_sub_icon_pos' => 'append_link',
        'has_sub_icon_tag' => 'i',
        'has_sub_icon_class' => '',
        'has_sub_icon_prefix_class' => '',
        'has_sub_icon_attrs' => [],
        'before_has_sub_icon' => '',
        'after_has_sub_icon' => '',
        'prepend_has_sub_icon' => '',
        'appemd_has_sub_icon' => '',

        'text_tag'   => null,
        'text_class' => '',
        'text_attrs' => [],

        //
        'action' => null,

    ];


    /**
     * @var string $prop_type
     */
    protected $prop_type = 'level';

    /**
     *
     * @var array
     * [
     *      [
     *          'key' => $value,
     *          ...
     *      ],
     *      ...
     * ]
     * hoặc
     * [
     *     'key' => $value,
     *     ...
     * ]
     * tuy vào prop type
     */
    protected $props = [];


    /**
     * @var string $active_name
     *
     */
    protected $active_name = null;
    /**
     * @var string $active_name
     *
     */
    protected $menu_active_name = null;

    /**
     * @var array $active_keys
     */
    protected static $active_keys = [];

    /**
     * url
     * @var string
     */
    protected static $active_url = null;

    /**
     * @var array
     */
    protected $orginalData = [];


    protected $menu = [];

    /**
     * thuc hiên cach hanh dong
     */

    protected $actions = [];

    /**
     * @var object|MenuItem
     */
    public $parent = null;
    /**
     * khoi tao form group
     */
    protected $menuData = [];

    /**
     * level
     * @var int $level
     */
    protected $level = 0;





    /**
     * khoi tao mang menu
     * @param array $menu
     * @param array $options
     * @param int $level
     * [
     * 'prop_type' => 'level' / 'loop',
     * 'props' => [0 => [key => value, ...], ...] / [key => value, ...]
     * ]
     *
     *
     */

    public function __construct($menu = null, array $options = [], int $level = 0, $parent = null)
    {
        self::$active_url = URL::full();
        // tao doi tuong option de truy cap key khong phai check isset
        $opt = new Arr($options);
        // gan gia tri
        $this->active_name = $opt->active_name ? $opt->active_name : 'name';
        $this->menu_active_name = $opt->menu_active_name ? $opt->menu_active_name : $this->active_name;
        $this->menu = $menu;
        $this->parent = $parent;
        //thiet llap thuoc tinh
        $this->setProps($opt->prop_type ?? 'loop', is_array($opt->props) ? $opt->props : []);

        //
        $lv = (int) $level;
        if ($lv > 0) {
            $this->level = $lv;
        }

        $props = new Arr($this->getProps());

        parent::__construct($props->tag ? $props->tag : $props->menu_tag);

        if ($props->menu_class) {
            $this->addClass($props->menu_class);
        }
        if ($props->menu_id) {
            $this->id = $props->menu_id;
        }

        if (is_array($opt->data)) {
            $this->orginalData = $opt->data;
            $this->setOption($opt->data);
        }

        if (is_array($opt->attrs)) {
            $this->attr($opt->attrs);
        }

        $this->checkMenuData();
        $this->prepare();
    }

    /**
     * set props
     * @param string $type
     * @param array $props
     *
     * @return Menu
     *
     */
    public function setProps(string $type = 'loop', array $props = [])
    {
        $this->prop_type = in_array($type, ['level', 'loop']) ? $type : 'loop';
        $this->props = is_array($props) ? $props : [];
        return $this;
    }




    /**
     * lấy thuộc tính của menu hiện tại
     * @param void
     *
     * @return array
     */
    public function getProps()
    {
        return array_merge(
            $this->default_props,
            ($this->prop_type == 'loop') ? $this->props : (isset($this->props[$this->level]) ? $this->props[$this->level] : [])
        );
    }

    /**
     * lay item prop
     */
    public function getItemProps()
    {
        $ignore = [
            'menu_tag', 'menu_class', 'menu_id'
        ];
        $props = $this->getProps();
        $data = [];
        foreach ($props as $key => $value) {
            if (!in_array($key, $ignore)) {
                $data[$key] = $value;
            }
        }
        return $data;
    }




    /**
     * set key active menu
     * @param string $name tên key
     * @param string $active_key  gia tri key
     */

    public static function addActiveKey($name = 'default', $active_key = null)
    {
        if (!isset(self::$active_keys[$name])) self::$active_keys[$name] = [];
        self::$active_keys[$name][] = $active_key;
    }



    /**
     * set key active menu
     * @param string $name tên key
     * @param string $active_key  gia tri key
     */

    public static function removeActiveKey($name = 'default', $active_key = null)
    {
        if (!isset(self::$active_keys[$name])) return true;
        if(!$active_key){
            self::$active_keys[$name] = [];
            return true;
        }
        if (in_array($active_key, self::$active_keys[$name])) {
            $index = array_search($active_key, self::$active_keys[$name]);
            if ($index >= 0)
                array_splice(self::$active_keys, $index, 1);
        }
    }

    public static function checkActiveKey($name, $active_key = null)
    {
        if (is_string($name) && isset(self::$active_keys[$name])) {
            foreach (self::$active_keys[$name] as $key) {
                if ($active_key == $key) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getActiveList()
    {
        return static::$active_keys;
    }

    public static function checkActiveURL($url = null)
    {
        if (!$url || !is_string($url)) return false;
        if (self::$active_url) {
            $url = strtolower($url);
            $active_url = strtolower(self::$active_url);
            $ua = explode('?', $active_url);
            $uc = explode('?', $url);

            if ($active_url == $url) {
                return true;
            }

            $ucc = trim($url, '?');
            if (count($ua) == 2 && $ua[0] == $ucc) {
                return true;
            }


            if (count($ua) == 2 && count($uc) == 2) {
                parse_str($ua[1], $ap);
                parse_str($uc[1], $up);
                if ($up && $ap) {
                    foreach ($up as $k => $v) {

                        if (!isset($ap[$k])) {
                            return false;
                        } elseif ((is_array($ap[$k]) || is_array($up[$k]))) {
                            if ($ap[$k] != $v) return false;
                        } elseif (strtolower($ap[$k]) != strtolower($v)) {
                            if ((is_array($ap[$k]) || is_array($up[$k]))) {
                                if ($ap[$k] != $up[$k]) return false;
                            } else return false;
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * kiem tra du lieu menu
     */
    public function checkMenuData()
    {
        if (!$this->menuData) {
            if ($this->menu && $menuList = $this->getMenuList()) {
                $this->menuData = $menuList;
                return true;
            }
            return false;
        }
        return true;
    }

    public function getMenuList()
    {
        $menuList = [];
        $menu = new Arr(Arr::parse($this->menu));
        $t = strtolower($menu->type);
        if ($t == 'json') {
            if (!$menu->file) return null;
            $files = new Filemanager(base_path('json'));
            if ($data = $files->getJson($menu->file)) {
                $menuList = $data['data'];
            }
        } elseif ($t == 'define') {
            $c = null;
            if (isset($menu->call)) {
                $c = $menu->call;
            } elseif (isset($menu->func)) {
                $c = $menu->func;
            } elseif (isset($menu->method)) {
                $c = $menu->method;
            }

            if ($c && is_callable($c)) {
                $a = isset($menu->param) ? $menu->param : ($menu->args ? $menu->args : []);
                if ($d = $c($a)) {
                    $menuList = Arr::parse($d);
                }
            }
        } elseif ($t == 'list') {
            $menuList = $menu->list ? $menu->list : ($menu->data ? $menu->data : ($menu->items ? $menu->items : ([]
            )
            )
            );
        } elseif ($menu->get(0)) {
            $menuList = $menu->all();
        }
        return $menuList;
    }

    public function prepare($action = null)
    {
        if ($this->checkMenuData()) {
            $this->addAction($action);
            $menuList = $this->menuData;
            $d = $this->_data;
            $i = 0;
            $total = count($menuList);
            $itemProps = $this->getItemProps();
            $itemProps['tag'] = $itemProps['item_tag'];

            foreach ($menuList as $k => $v) {
                $item = new MenuItem(
                    $v,
                    array_merge($itemProps, [
                        'active_name' => $this->active_name,
                        'menu_active_name' => $this->menu_active_name,
                        'total' => $total,
                        'index' => $i,
                    ]),
                    [
                        'prop_type' => $this->prop_type,
                        'props' => $this->props
                    ],
                    $this->level,
                    $this->parent
                );
                //$item->addAction($this->actions);
                $this->_children[] = $item;

                $this->append($item);
                $i++;
            }
        }
    }

    public function count()
    {
        return count($this->_children);
    }

    /**
     * lấy số level con của các menu item
     *
     * @return int
     */
    public function getSonLevel()
    {
        $level = $this->level;
        if (count($this->_children)) {
            foreach ($this->_children as $key => $item) {
                $lv = $item->getSonLevel();
                if ($lv > $level) $level = $lv;
            }
        }
        return $level;
    }

    /**
     * lấy ra tiem được active
     *
     * @return MenuItem|null
     */
    public function getActiveItem()
    {
        if ($this->_children) {
            foreach ($this->_children as $child) {
                if (is_a($child, MenuItem::class)) {
                    if ($child->isActive()) return $child;
                }
            }
        }
        return null;
    }

    public function render($action = null)
    {
        $this->addAction($action);

        if ($this->_children) {
            foreach ($this->_children as $child) {
                if (is_a($child, MenuItem::class)) {
                    $child->addAction($this->actions);
                }
            }
        }
        return parent::render();
    }

    public function addAction($action = null)
    {
        if (is_callable($action)) {
            $this->actions[] = $action;
        } elseif (is_array($action)) {
            foreach ($action as $act) {
                if (is_callable($act)) {
                    $this->actions[] = $act;
                }
            }
        }
        return $this;
    }



    public function __toString()
    {
        return $this->render();
    }
}
