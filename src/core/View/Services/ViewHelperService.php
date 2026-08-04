<?php

namespace Saola\Core\View\Services;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Support\SPA;

class ViewHelperService
{
    protected $attrPrefix = "data-yield-attr-";
    protected $eventPrefix = "data-";
    protected $subscribePrefix = "data-yield-";
    protected $funtionalTemplates = [];

    public function __construct(
        protected ViewStorageManager $viewStorageManager,
        protected ?ViewContextManager $viewContextManager = null,
    )
    {
    }

    /**
     * Register view
     * @param string $viewName
     * @param string $viewId
     */
    public function registerView($viewName, $viewId){
        $this->viewStorageManager->registerView($viewName, $viewId);
    }

    public function startWrapper($tag, $attributes = [], $viewId = null){
        $this->viewStorageManager->startWrapper($tag, $attributes, $viewId);
    }

    public function endWrapper($viewId = null){
        $this->viewStorageManager->endWrapper($viewId);
    }

    public function startWrapperAttr($viewId = null){
        $this->viewStorageManager->startWrapperAttr($viewId);
    }

    public function wrapAttr($viewPath = null, $viewId = null){
        $this->viewStorageManager->registerView($viewPath, $viewId);
        return ' data-view-wrapper="'.$viewId.'"';
    }
    
    public function test(){
        return 'div';
    }
    
    public function reset(){
        $this->viewStorageManager->reset();
        $this->funtionalTemplates = [];
    }

    public function addFunctionalTemplate($viewPath, $templateName, $templateFunction){
        if(!isset($this->funtionalTemplates[$viewPath])){
            $this->funtionalTemplates[$viewPath] = [];
        }
        if(!is_callable($templateFunction)){
            return;
        }
        $this->funtionalTemplates[$viewPath][$templateName] = $templateFunction;
    }

    public function getFunctionalTemplate($viewPath, $templateName){
        if(!isset($this->funtionalTemplates[$viewPath])){
            return null;
        }
        return $this->funtionalTemplates[$viewPath][$templateName];
    }

    public function checkFunctionalTemplate($viewPath, $templateName){
        if(!isset($this->funtionalTemplates[$viewPath])){
            return false;
        }
        return isset($this->funtionalTemplates[$viewPath][$templateName]);
    }

    public function renderFunctionalTemplate($viewPath, $templateName, $viewId = null, $context = [], $data = [], $subscribe = []){
        if(!isset($this->funtionalTemplates[$viewPath]) || !isset($this->funtionalTemplates[$viewPath][$templateName]) || !is_callable($this->funtionalTemplates[$viewPath][$templateName])){
            return null;
        }
        return call_user_func($this->funtionalTemplates[$viewPath][$templateName], $viewId, $context, $data, $subscribe);
    }

    public function addScript($viewId, $scriptContent, $viewName){
        $this->viewStorageManager->addScript($viewId, $scriptContent, $viewName);
    }
    public function registerResources($viewId, $resourcesContent){
        // $this->viewStorageManager->registerResources($viewName, $resourcesContent, $viewId);
    }

    /**
     * Add event listener với multiple handlers
     * @param string $eventType Event type (click, mouseover, etc.)
     * @param array $handlers Array of handlers
     * @param string $elementId Unique element ID
     * @return string HTML attributes
     */
    public function addEventListener($viewPath = null, $viewId = null, $eventType = null, $handlers = [])
    {
        return $this->viewStorageManager->addEventListener($viewPath, $viewId, $eventType, $handlers);
    }

    /**
     * Add event quick handle for expressions
     * @param string $viewPath View path
     * @param string $viewId View ID
     * @param string $eventType Event type (click, change, etc.)
     * @param array $quickHandlers Quick handle data
     * @return string HTML attributes
     */
    public function addEventQuickHandle($viewPath = null, $viewId = null, $eventType = null, $quickHandlers = [])
    {
        return $this->viewStorageManager->addEventQuickHandle($viewPath, $viewId, $eventType, $quickHandlers);
    }

    public function addViewData($viewPath, $viewId, $data){
        $this->viewStorageManager->addViewData($viewPath, $viewId, $data);
    }

    /**
     * Add subscribe attributes for @subscribe directive
     * @param array $subscribeData Array of subscribe mappings
     * @param string $viewId View ID
     * @return string HTML attributes
     */
    public function addSubscribeAttributes($subscribeData, $viewId = null)
    {
        if (!is_array($subscribeData)) {
            return '';
        }
        
        $attributes = [];
        foreach ($subscribeData as $selector => $yieldKey) {
            $selector = trim($selector, "'\"");
            $yieldKey = trim($yieldKey, "'\"");
            
            // Tạo attribute cho selector
            $attributes[] = "data-yield-target=\"{$selector}\"";
            $attributes[] = "data-yield-key=\"{$yieldKey}\"";
        }
        
        return implode(' ', $attributes);
    }

    /**
     * Process @subscribe directive with array syntax (giống Python compiler)
     * @param array $subscribeArray Array of subscribe mappings
     * @return string HTML attributes
     */
    public function registerOnYield($__env, $attr = null, $yieldKey = null, $default = null)
    {
        if(!$attr){
            return "";
        }
        $attributeString = "";
        $subscribe = [];
        if(is_string($attr)){
            $attributeString .= " {$attr}=\"".e($__env->yieldContent($yieldKey, $default))."\" {$this->subscribePrefix}{$attr}=\"{$yieldKey}\"";
            return $attributeString;
        }elseif(is_array($attr)){
            foreach($attr as $key => $yield){
                if(!in_array($key, ['#content', '#children'])){
                    $attributeString .= " {$key}=\"".e($__env->yieldContent($yield, $default))."\"";
                    $subscribe[] = "{$key}:{$yield}";
                }
                elseif($key == '#content'){
                    $attributeString .= " {$this->subscribePrefix}content=\"{$yield}\"";
                }elseif($key == '#children'){
                    $attributeString .= " {$this->subscribePrefix}children=\"{$yield}\"";
                }
            }
        }
        return $attributeString . " {$this->subscribePrefix}attr=\"" . implode(',', $subscribe) . "\"";
    }

    /**
     * Process @onBlock directive - tương tự registerOnYield nhưng dành cho blocks
     * @param object $__env Blade environment
     * @param mixed $attr Attribute name hoặc array of attributes
     * @param string $blockKey Block key (sẽ được thêm prefix "block:")
     * @param mixed $default Default value
     * @return string HTML attributes
     */
    public function registerOnBlock($__env, $attr = null, $blockKey = null, $default = null)
    {
        if(!$attr){
            return "";
        }
        
        $attributeString = "";
        $subscribe = [];
        

        if(is_string($attr)){
            // Simple syntax: registerOnBlock($attr, $blockKey, $default)
            if($attr && !$blockKey){
                $blockKey = $attr;
                $attr = ['#children' => $blockKey];
            }
            else{
                $a = [
                    $attr => $blockKey
                ];
                $attr = $a;
            }
        }
        
        if(is_array($attr)){
            // Array syntax: registerOnBlock(['attr' => 'blockKey', ...])
            foreach($attr as $key => $yield){
                $fullBlockKey = "block.{$yield}";
                
                if(!in_array($key, ['#content', '#children'])){
                    $attributeString .= " {$key}=\"".e($__env->yieldContent($fullBlockKey, $default))."\"";
                    $subscribe[] = "{$key}:{$fullBlockKey}";
                }
                elseif($key == '#content'){
                    $attributeString .= " {$this->subscribePrefix}content=\"{$fullBlockKey}\"";
                }elseif($key == '#children'){
                    $attributeString .= " {$this->subscribePrefix}children=\"{$fullBlockKey}\"";
                }
            }
        }
        
        return $attributeString . ($subscribe ? " {$this->subscribePrefix}attr=\"" . implode(',', $subscribe) . "\"" : "");
    }

    public function subscribeState($viewPath = null, $viewId = null, $subscribe = null){
        return $this->viewStorageManager->subscribeState($viewPath, $viewId, $subscribe);
    }


    public function registerViewType(...$args){

    }

    public function setParentView($viewPath, $viewId, $parentViewPath, $parentViewId){
        $this->viewStorageManager->setParentView($viewPath, $viewId, $parentViewPath, $parentViewId);
    }

    public function setOriginView($viewPath, $viewId, $originViewPath, $originViewId){
        $this->viewStorageManager->setOriginView($viewPath, $viewId, $originViewPath, $originViewId);
    }

    public function setSuperView($viewPath, $viewId, $superViewPath, $superViewId){
        $this->viewStorageManager->setSuperView($viewPath, $viewId, $superViewPath, $superViewId);
    }

    public function addChildrenView($viewPath, $viewId, $childrenViewPath, $childrenViewId){
        $this->viewStorageManager->addChildrenView($viewPath, $viewId, $childrenViewPath, $childrenViewId);
    }

    public function addOutputComponent($viewPath, $viewId, $ocTaskId, $stateKeys){
        return $this->viewStorageManager->addOutputComponent($viewPath, $viewId, $ocTaskId, $stateKeys);
    }

    public function addTagAttribute($viewPath, $viewId, $config = [], $attr = null, $value = null){
        return $this->viewStorageManager->addTagAttribute($viewPath, $viewId, $config, $attr, $value);
    }

    public function setState($viewPath, $viewId, $stateKey, $stateValue){
        return $this->viewStorageManager->setState($viewPath, $viewId, $stateKey, $stateValue);
    }

    public function setSystemData($data = []){
        return $this->viewStorageManager->setSystemData($data);
    }

    public function exportSystemData(){
        return $this->viewStorageManager->exportSystemData();
    }

    /**
     * Store event data để client-side có thể access
     */
    protected function storeEventData($elementId, $eventType, $handlers)
    {
        if (!isset($GLOBALS['__event_registry'])) {
            $GLOBALS['__event_registry'] = [];
        }
        
        $GLOBALS['__event_registry'][$elementId] = [
            'event_type' => $eventType,
            'handlers' => $handlers
        ];
    }

    /**
     * Get event registry để output ra client-side
     */
    public function getEventRegistry()
    {
        return $GLOBALS['__event_registry'] ?? [];
    }

    /**
     * Generate JavaScript event registry script
     */
    public function generateEventRegistryScript()
    {
        $eventRegistry = $this->getEventRegistry();
        
        if (empty($eventRegistry)) {
            return '';
        }
        
        $jsonData = json_encode($eventRegistry, JSON_PRETTY_PRINT);
        
        return "<script type=\"application/json\" data-type=\"event-registry\">{$jsonData}</script>";
    }

    public function exportApplicationViewData(){
        $data = $this->viewStorageManager->exportApplicationData();
        return $data;
        // return "<script type=\"application/json\" data-type=\"application-data\">".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."</script>";
    }

    public function exportSpaRoutes($context = null){
        return $this->materializeRoutes(SPA::getRoutes($context), $context);
    }

    public function exportComponentRoutes($context = null){
        return $this->materializeRoutes(SPA::getComponentRoutes($context), $context, true);
    }

    public function exportViewContext(string $context): array
    {
        $state = $this->getViewContextManager()->exportContextState($context);
        $state['routes'] = $this->exportSpaRoutes($context);
        return $state;
    }

    public function resolveRouteComponent(string $context, string $routeName): ?string
    {
        foreach ($this->exportComponentRoutes($context) as $route) {
            if (($route['name'] ?? null) === $routeName) {
                return $route['component'] ?? null;
            }
        }

        return null;
    }

    protected function materializeRoutes(array $routes, ?string $context = null, bool $componentOnly = false): array
    {
        $manager = $this->getViewContextManager();

        $materialized = array_map(function (array $route) use ($manager, $context) {
            $routeContext = $route['context'] ?? $context;
            $component = $route['component'] ?? null;
            if (!$routeContext || !$component) {
                return $route;
            }

            $route['logicalComponent'] = is_array($component)
                ? ($component['logical'] ?? null)
                : $component;
            $route['component'] = $manager->resolveRouteComponent($routeContext, $component);
            return $route;
        }, $routes);

        if ($componentOnly) {
            $materialized = array_filter(
                $materialized,
                static fn (array $route) => is_string($route['component'] ?? null)
                    && $route['component'] !== ''
            );
        }

        return array_values($materialized);
    }

    protected function getViewContextManager(): ViewContextManager
    {
        return $this->viewContextManager ??= app(ViewContextManager::class);
    }

    public function addReactiveRegistry($type, $registryID, $stateKeys, $options = []){
        
    }

    /**
     * Mở một vùng reactive.
     *
     * @param  string  $type        Loại directive: 'if', 'foreach', 'for', 'while', 'switch', 'output', 'include', 'isset', 'empty'
     * @param  string  $registryID  ID duy nhất của vùng reactive, format: 'rc-{viewID}-{type}-{N}'
     * @param  array   $stateKeys   Danh sách state variables mà vùng này phụ thuộc
     * @param  array   $options     (Optional) Tùy chọn bổ sung — hiện dùng cho type 'output'
     * @return string  HTML marker output
     */
    public function startReactive(
        string $type,
        string $registryID,
        array $stateKeys,
        array $options = []
    ): string {
        // Đăng ký vùng reactive trong ViewStorageManager
        $this->viewStorageManager->addReactiveRegistry($type, $registryID, $stateKeys, $options);
        
        // Trả về marker mở vùng reactive
        return $this->viewStorageManager->getMarkerOpenTag('reactive', $registryID);
    }

    /**
     * Đóng một vùng reactive.
     *
     * @param  string  $type        Loại directive (cùng với startReactive tương ứng)
     * @param  string  $registryID  ID duy nhất (cùng với startReactive tương ứng)
     * @return string  HTML marker output
     */
    public function endReactive(string $type, string $registryID): string
    {
        return $this->viewStorageManager->getMarkerCloseTag('reactive', $registryID);
    }

    public function startMarker($name, $markerId, $config = []){
        $this->viewStorageManager->addMarkerRegistry($name, $markerId, $config);
        return $this->viewStorageManager->getMarkerOpenTag($name, $markerId);
    }

    public function endMarker($name, $markerId){
        return $this->viewStorageManager->getMarkerCloseTag($name, $markerId);
    }

    /**
     * Render SSR boot script cho client hydrate (RUNTIME_CONTRACT §6).
     *
     * Phát ra:
     *   <script type="application/json" data-ref="saola-ssr">
     *     {"container":"#app-root","view":"<viewPath>","viewId":"<viewId>"}
     *   </script>
     *
     * Client (App.start → readSSRBoot) đọc script này để hydrate route đầu tiên
     * thay vì mount mới. viewId của layout chain client tự discover từ DOM marker,
     * nên chỉ cần page entry ở đây.
     *
     * @param string $view      Registry path của page entry (thường $__VIEW_PATH__)
     * @param string $viewId    viewId server đã render (thường $__VIEW_ID__)
     * @param string $container CSS selector container chứa SSR HTML
     */
    public function renderSSRBoot($view = '', $viewId = '', $container = '#app-root'){
        // $view/$viewId truyền từ shell (_system.page.end) trỏ về CHÍNH page-shell
        // partial, KHÔNG phải route component → client consumeSSRViewId không khớp.
        // Ưu tiên derive page entry thật (route component) từ render tree.
        $entry = $this->viewStorageManager->getEntryView();
        if ($entry) {
            $view = $entry['name'];
            $viewId = $entry['id'];
        }
        // Thiếu view/viewId → KHÔNG emit (client readSSRBoot trả null → CSR boot).
        // Tránh phát boot rỗng làm hydrate sai. An toàn khi var chưa có trong scope.
        if (empty($view) || empty($viewId)) {
            return '';
        }
        $payload = json_encode([
            'container' => $container,
            'view'      => $view,
            'viewId'    => $viewId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/json" data-ref="saola-ssr">' . $payload . '</script>';
    }

}
