<?php
namespace Saola\Core\Html;

use Saola\Core\Magic\Arr;
use Saola\Core\Files\Filemanager;
use URL;

class MenuItem extends HtmlDom
{
     /**
     * @var array $default_props 
     */
    protected $default_props = [
        'tag' => 'li',

        'item_class' => '',
        'item_active_class' => 'active',
        'use_active_key' => false,
        'item_attrs' => [],

        // sub
        'item_has_sub_attrs' => [],
        'has_sub_class' => '',
        'has_sub_active_class' => '',



        // link attr
        'link_attrs' => [],
        'link_has_sub_attrs' => [],
        
        'link_class' => '',
        'link_has_sub_class' => '',
        'link_active_class' => '',
    
         
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
        'icon_prefix_class' => 'fa fa-',
        'icon_attrs' => [],
        'before_icon' => '',
        'after_icon' => '',
        'prepend_icon' => '',
        'appemd_icon' => '',
        
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
        
        'active_name' => '',
        'menu_active_name' => '',
        'total' => 0,
        'index' => 0,

        'action' => null
    ];

    protected $_data = [
        'text' => "menu item",
        'title' => 'Menu item',
        'active_name' => '',
        'active_key' => '',

        'icon' => null,
        'has_sub_icon' => null,
        'badge' => null,
        'badge_content' => '',
        'icon_content' => '',
        'has_sub_icon_content' => '',

        'sub_type' => 'default', // 'default', 'mega', 'custom'
        'show_submenu' => 'show',
        'mega_menu_start' => '',
        'mega_menu_end' => '',

        'route' => '',
        'params' => [],
        'url' => '',
        'link' => '',
        'link_class' => '',
        'submenu' => [],

        'beforeLink' => '',
        'afterLink' => '',

        'target' => null,
    ];

    /**
     * thuộc tính
     * @var array
     * [
     *      key => value
     * ]
     */
    protected $props = [];

    /**
     * current prop
     * @var array
     */
    protected $current_props = [];

    /**
     * 
     * @var array
     */ 
    protected $menu_prop_args = [];






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
    
    protected $orginalData = [];

    protected $actions = [];

    public $link = null;

    /**
     * parent
     *
     * @var MenuItem
     */
    public $parent = null;
    
    public $sub = null;

    protected $_active = false;

    public $level = 0;
    
    /**
     * khoi tao menu item
     * @param array $data
     * @param array $props
     * @param array $menu_prop_args
     * @param int $parent
     * 
     */

    public function __construct(array $data=[], array $props=[], array $menu_prop_args = [], int $level = 0, $parent = null)
    {
        // dd($props);
        $tag = isset($props['tag'])?$props['tag']:(isset($props['item_tag'])?$props['item_tag']:'li');
        parent::__construct($tag);
        $this->menu_prop_args = $menu_prop_args;
        $this->level = $level;

        $this->parent = $parent;
        $this->setProps($props);
        
        
        $this->active_name = $props['active_name'];
        if(isset($props['menu_active_name'])){
            $this->menu_active_name = $props['menu_active_name'];
        }
        if(is_array($data)){
            $this->setOption($data);
        }
        $this->prepare();
        if($this->isActive()){
            $this->active();
        }
        
    }

    
    /**
     * them thuoc tinh 
     * @param array|string
     * @param mixed
     */
    public function setOption($name = null, $value = null)
    {
        if(is_string($name)){
            return $this->setOpt($name,$value);
        }elseif(is_array($name)){
            foreach($name as $key => $val){
                $this->setOpt($key,$val);
            }
        }
        return $this;
    }

    protected function setOpt($name,$value=null)
    {
        $n = strtolower($name);
        if (array_key_exists($n, $this->_data)) {
            $this->_data[$n] = $value;
            if($n=='item_class' || $n=='class' || $n=='classname'){
                return $this->attr('class',$value);
            }
            if($name=='item_attrs' || $name=='attrs'){
                return $this->attr($value);
            }
        }elseif($n=='item_class' || $n=='class' || $n=='classname'){
            return $this->attr('class',$value);
        }else{
            return $this->attr($name,$value);
        }
    }

    
    /**
     * set props
     * @param array $props
     * 
     * @return insteace
     * 
     */
    public function setProps(array $props = [])
    {
        $this->props = $props;

        $p = new Arr($props);
        if($p->class) $this->addClass($p->class);
        if($p->item_class) $this->addClass($p->item_class);

        return $this;
    }


    public function getSonLevel()
    {
        if(!$this->hasSubMenu()){
            return $this->level;
        }
        return $this->sub->getSonLevel();
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
            $this->props
        );
    }

    
    /**
     * lấy thuộc tính
     * @param string $name
     * 
     * @return mixed
     */
    public function getProp($name = null)
    {
        if(!$this->current_props) $this->current_props = $this->getProps();
        if(is_null($name)) return $this->current_props;
        return isset($this->current_props[$name])?$this->current_props[$name]:null;
    }

    /**
     * chuan bi cho render
     * @param function $actions
     */

    public function prepare($actions=null)
    {
        // them action neu co
        $this->addAction($actions);
        
        // tao doi tuong de truy cap data
        $d = new Arr($this->_data);

        // tao dou tuong de truy cap thuoc tinh
        $prop = new Arr($this->getProps());
        
        

        // link

        // submenu
        $sub = null;
        if(count($this->submenu)){
            $sm = $this->submenu;
            if(is_array($sm)){
                #sub = null;
                if(isset($sm[0])){
                    $sub = ['type'=>'list','list'=>$sm];
                }elseif(isset($sm['type'])){
                    $sub = $sm;
                }
                if($sm){
                    $submenu = new Menu(
                        $sm,
                        array_merge($this->menu_prop_args, [
                            'active_name' => $this->active_name.'_'.$this->active_key,
                            'menu_active_name' => $this->menu_active_name
                        ]),
                        $this->level+1,
                        $this
                    );
                    if($submenu->checkMenuData()){
                        $this->sub = $submenu;
                        if($d->show_submenu != 'hidden'){
                            $this->append($submenu);
                        }
                    }else{
                        $this->submenu = [];
                    }
                    
                }
            }
        }

        // nru co submenu
        $has_submenu = $this->hasSubMenu();
        $active = $this->isActive();
        

        $link_attrs = $prop->link_attrs;

        // url
        $u = '#';
        if($d->route){
            $u = route($d->route,$d->params);
            $link_attrs['href'] = $u;
            $this->url = $u;
        }
        elseif($d->url){
            $u = $d->url;
            $link_attrs['href'] = $u;
            $this->url = $u;
        }
        elseif(isset($link_attrs['href'])){
            $u = $link_attrs['href'];
        }
        if(!isset($link_attrs['title'])){
            $link_attrs['title'] = $this->title;
        }

        $link = new MenuLink($u, ($d->text?$d->text:$d->title).' ', $link_attrs);
        if($this->target && $this->target != 'none'){
            $link->attr('target', $this->target);
        }
        if($prop->link_class){
            $link->addClass($prop->link_class);
        }
        if($d->link_class){
            $link->addClass($d->link_class);
        }
        if($active && $prop->link_active_class){
            $link->addClass($prop->link_active_class);
        }

        if($d->link_id){
            $link->id=$d->link_id;
        }

        $link->text->tagName = $prop->text_tag;

        $link->text->addClass($prop->text_class);

        if($prop->text_attrs) $link->text->attr($prop->text_attrs);
        
        $this->link = $link;
        $this->prepend($link);


        if($has_submenu){
            if($prop->link_has_sub_class){
                $link->addClass($prop->link_has_sub_class);
            }
            if($prop->item_has_sub_attrs){
                $this->attr($prop->item_has_sub_attrs);
            }

            if($prop->link_has_sub_attrs){
                $link->attr($prop->link_has_sub_attrs);
            }
            $this->addIcon('has_sub_icon', true);
        }
        
        if($prop->item_class){
            $this->addClass($prop->item_class);
        }
        
        $this->addClass(
            (
                (count($this->submenu) > 0)?' '.$prop->has_sub_class:''
            ).(
                (!$active)?'':(
                    ($prop->item_active_class?' '.$prop->item_active_class:'')
                    .(
                        (!$has_submenu)?'':(
                            $prop->has_sub_active_class?' '.$prop->has_sub_active_class:''
                        )
                    )
                )
            )
        );

        foreach(['badge', 'icon'] as $icon){
            $this->addIcon($icon);
        }        
    }

    /**
     * thêm biểu tượng
     * @param string $slug
     * 
     * @return void
     */
    public function addIcon($slug, $required = false)
    {
        // thuộc tính mặc định
        $props = new Arr($this->getProps());
        $data = new Arr($this->_data);
        // nếu có biểu tượng hoặc yêu cầu
        $icon = $data->get($slug);
        if($slug == 'icon' && !$icon){
            $fc = $this->attr("{$slug}_full_class");
            // dd($fc);
            $icon = $fc;
        }
        if($props->get("use_$slug") || $icon || $required){
            // nếu sử dụng như nội dung
            $use_content = ($props->get($slug.'_data_type') == 'content');
            // lấy nội dung
            $content = $data->get($slug.'_content');
            $content = (!$content && $icon)?$icon:$content;
            // tạo thẻ icon

            $tag = new HTML(
                $props->get($slug.'_tag'), // tag html
                $use_content ? $content : null, // noi dung
                $props->get($slug.'_attrs') // thuoc tinh
            );
            // nếu dc set loai class icon
            if($class = $props->get("{$slug}_class")){
                $tag->addClass($class);
            }
            // ko nếu sử dụng content
            if(!$use_content){
                if(!$this->attr("{$slug}_full_class")){
                    $iconClass = $data->get($slug);
                    if(preg_match('/^(fa\s+fa|fab\s+fa|fas\s+fa|far\s+fa|la\s+la|socicon|flaticon)\-/', $iconClass)){
                        //
                    }else{
                        $iconClass = $props->get("{$slug}_prefix_class").$iconClass;
                    }
                    $tag->addClass($iconClass);
                }else{
                    $tag->addClass($icon);
                }
                
            }
            // if($classss) die($classss);

            // các vị trí chèn icon
            $insert_pos = ['before', 'after', 'prepend', 'append'];

            foreach ($insert_pos as $key) {
                if($cont = $props->get("{$key}_$slug")){
                    call_user_func_array([$tag, $key], [$cont]);
                }
            }
            
            $pos = strtolower($props->get("{$slug}_pos"));
            
            $pos_part = explode('_', $pos);
            if(count($pos_part) == 2){
                $p = $pos_part[0];
                if(in_array($p, ['before', 'prepend'])){
                    $tag->after(' ');
                }
                elseif(in_array($p, ['after', 'append'])){
                    $tag->before(' ');
                }
                if(in_array($p, $insert_pos)){
                    if($pos_part[1] == 'item'){
                        call_user_func_array([$this, $p], [$tag]);
                    }else{
                        $this->link->addIcon($slug, $p, $tag);

                    }
                }else{
                    $tag->before(' ');
                    $this->link->append($tag);
                }
            }else{
                $tag->before(' ');
                $this->link->append($tag);
            }
        }
    }
    
    /**
     * render
     * @param \Closure
     * @return string
     * 
     */
    public function render($action=null)
    {
        $this->addAction($action);
        
        
        // end submenu
        if(count($this->actions)>0){
            foreach($this->actions as $act){
                $act($this,$this->link,$this->sub);
            }
            if($this->menu_prop_args && isset($this->menu_prop_args['prop_type']) && $this->menu_prop_args['prop_type'] == 'loop' && $this->hasSubMenu() && $this->sub){
                foreach($this->actions as $act){
                    $this->sub->addAction($act);
                }
            }
        }

        if($a = $this->getProp('action')){
            if(is_callable($a)){
                $a($this,$this->link,$this->sub);
            }
        }
        
        return parent::render();
    }

    public function isActive()
    {
        
        if(Menu::checkActiveKey($this->active_name,$this->active_key)) return true;
        if($this->menu_active_name && Menu::checkActiveKey($this->menu_active_name,$this->active_key)) return true;
        if($this->_active) return true;
        return Menu::checkActiveURL($this->url);
    }

    public function active()
    {
        $this->_active = true;
        if($this->parent && is_a($this->parent, static::class)){
            $this->parent->active();
        }
    }

    public function index()
    {
        return $this->getProp('index');
    }
    public function isFirst()
    {
        return ($this->getProp('index') == 0);
    }
    public function isLast()
    {
        return ($this->getProp('total') - $this->getProp('index') == 1);
    }
    public function inList()
    {
        return !($this->isFirst() || $this->isLast());
    }
    
    public function hasSubMenu()
    {
        return count($this->submenu) && $this->show_submenu != 'hidden';
    }

    public function hasHiddenSubmenu()
    {
        return count($this->submenu) && $this->show_submenu == 'hidden';
    }

    public function getSubmenu()
    {
        return $this->sub;
    }    
    public function addAction($action=null)
    {
        if(is_callable($action)){
            $this->actions[] = $action;
        }elseif(is_array($action)){
            foreach($action as $act){
                if(is_callable($act)){
                    $this->actions[] = $act;
                }
            }
        }
        return $this;
    }

    
    public function __get($name)
    {
        $n = strtolower($name);
        if(in_array($n,['innerhtml', 'innertext'])){
            if($n=='innertext'){
                return $this->text();
            }
            return $this->html();
        }
        if(array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        elseif (array_key_exists($name, $this->_attrs)) {
            return $this->_attrs[$name];
        }
        return null;
    }

    public function __toString(){
        return $this->render();
    }
}