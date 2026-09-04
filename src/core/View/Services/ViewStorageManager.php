<?php

namespace Saola\Core\View\Services;

class ViewStorageManager
{
    protected $wrapperLevel = -1;
    protected $wrapperStack = [];
    protected $viewScripts = [];
    protected $viewResources = [];
    protected $viewStyles = [];
    protected $vueComponents = [];
    protected $registeredResources = [];
    protected $viewStorage = [];
    protected $eventRegistry = [];
    protected $systemData = [];

    protected $markerPrefix = 's'; // Saola Marker
    protected $markerTagShortcut = [
        "view" => 'v',                           // View Marker
        "component" => 'c',                      // Component Marker
        "layout" => 'l',                         // Layout Marker
        "template" => 't',                       // Template Marker
        "block" => 'b',                          // Block Marker
        "reactive" => 'r',                       // Reactive Marker
        "section" => 's',                        // Section Marker
        "fragment" => 'frg',                     // Fragment Marker
        "blockoutlet" => 'bo',                   // Block outlet Marker
        "for" => 'fo',                           // For loop Marker
        "forin" => 'fi',                         // For-in loop Marker
        "foreach" => 'fe',                       // For-each loop Marker
        "forelse" => 'fls',                      // Forelse loop Marker
        "each" => 'ea',                          // Each loop Marker
        "while" => 'wh',                         // While loop Marker
        "if" => 'if',                            // If condition Marker
        "switch" => 'sw',                        // Switch condition Marker
        "include" => 'inc',                      // Include Marker
        "echo" => 'e',                           // Echo Marker
        "echoescaped" => 'ee',                   // Echo escaped Marker
        "output" => 'o',                         // Output Marker (generic)
        "yield" => 'y',                          // Yield Marker
        "slot" => 'st',                          // Slot Marker
        "useblock" => 'ub',                      // Use block Marker
        "extend" => 'ex',                        // Extend Marker
        "style" => 'sty',                        // Style Marker
        "script" => 'sc',                        // Script Marker
    ];

    public $markerRegistery = [];

    
    public function __construct() {}

    public function reset()
    {
        $this->wrapperLevel = -1;
        $this->wrapperStack = [];
        $this->viewScripts = [];
        $this->viewResources = [];
        $this->viewStyles = [];
        $this->vueComponents = [];
        $this->registeredResources = [];
        $this->viewStorage = [];
        $this->eventRegistry = [];
        $this->systemData = [];
        $this->markerRegistery = [];
        $this->headAssets = [];
    }

    public function registerView(string $viewName, string $viewId)
    {
        if (!isset($this->viewStorage[$viewName])) {
            $this->viewStorage[$viewName] = [
                'scripts' => [],
                'styles' => [],
                'resources' => [],
                'instances' => [],
            ];
        }

        if (!isset($this->viewStorage[$viewName]['instances'][$viewId])) {
            $this->viewStorage[$viewName]['instances'][$viewId] = [
                'viewId' => $viewId,
                'data' => [],
                'events' => [],
            ];
        }
    }



    public function addViewData(string $viewName, string $viewId, array $data)
    {
        $this->registerView($viewName, $viewId);
        if (!isset($this->viewStorage[$viewName]['instances'][$viewId])) {
            return;
        }
        $this->viewStorage[$viewName]['instances'][$viewId]['data'] = $this->deepArrayConvert($data);
    }

    public function getViewData(string $viewName, string $viewId)
    {
        $this->registerView($viewName, $viewId);
        return $this->viewStorage[$viewName]['instances'][$viewId]['data'];
    }

    public function setParentView(string $viewName, string $viewId, string $parentViewName, string $parentViewId)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName]['instances'][$viewId]['parent'] = [
            'name' => $parentViewName,
            'id' => $parentViewId
        ];
    }

    public function setOriginView(string $viewName, string $viewId, string $originViewName, string $originViewId)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName]['instances'][$viewId]['origin'] = [
            'name' => $originViewName,
            'id' => $originViewId
        ];
    }
    public function setSuperView(string $viewName, string $viewId, string $superViewName, string $superViewId)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName]['instances'][$viewId]['super'] = [
            'name' => $superViewName,
            'id' => $superViewId
        ];
    }

    public function addChildrenView(string $viewName, string $viewId, string $childrenViewName, string $childrenViewId)
    {
        $this->registerView($viewName, $viewId);
        if (!isset($this->viewStorage[$viewName]['instances'][$viewId]['children'])) {
            $this->viewStorage[$viewName]['instances'][$viewId]['children'] = [];
        }
        $this->viewStorage[$viewName]['instances'][$viewId]['children'][] = [
            'name' => $childrenViewName,
            'id' => $childrenViewId
        ];
    }

    /**
     * Tìm view "entry" của trang — page/module được route render đầu tiên.
     *
     * Trong render tree, page entry là instance DUY NHẤT không có `parent`
     * (layout/partial/system đều được include từ view khác nên có parent). Bỏ
     * qua các view `_system.*` để chắc chắn lấy đúng route component, không lấy
     * page-shell partial.
     *
     * Dùng cho SSR boot: client hydrate route đầu cần biết component path + viewId
     * của PAGE, không phải của `_system.page.end` đang render lúc emit boot script.
     *
     * @return array{name:string,id:string}|null
     */
    public function getEntryView(): ?array
    {
        foreach ($this->viewStorage as $viewName => $view) {
            if (str_starts_with($viewName, '_system')) {
                continue;
            }
            foreach ($view['instances'] as $viewId => $instance) {
                if (!isset($instance['parent'])) {
                    return ['name' => $viewName, 'id' => (string) $viewId];
                }
            }
        }
        return null;
    }

    public function exportViewData()
    {
        $exportData = [];
        foreach ($this->viewStorage as $viewName => $view) {
            foreach ($view['instances'] as $viewId => $viewData) {
                $data = $viewData['data'];
                $exportData[] = [
                    'tag' => 'script',
                    'attributes' => [
                        'type' => 'application/json',
                        'data-view-id' => $viewId,
                        'data-view-name' => $viewName,
                        'data-ref' => 'view-data'
                    ],
                    'content' => json_encode($data)
                ];
            }
        }
        return $exportData;
    }

    public function exportApplicationData()
    {
        $exportData = array_map(function ($view) {
            return $this->deepArrayConvert($view);
        }, $this->viewStorage);
        return $exportData;
    }

    public function setSystemData(array $data = [])
    {
        $this->systemData = array_merge($this->systemData, $data);
    }

    public function exportSystemData()
    {
        return $this->systemData;
    }

    /**
     * Deep convert tất cả objects/collections sang array
     * Nếu object có method toArray() thì gọi nó
     */
    protected function deepArrayConvert($data)
    {
        if (is_array($data)) {
            // Nếu là array, recursively convert từng element
            return array_map([$this, 'deepArrayConvert'], $data);
        } elseif (is_object($data)) {
            // Nếu object có method toArray(), gọi nó
            if (method_exists($data, 'toArray')) {
                return $this->deepArrayConvert($data->toArray());
            }
            // Nếu không có toArray(), convert properties sang array
            elseif ($data instanceof \stdClass) {
                return $this->deepArrayConvert((array) $data);
            }

            // For other objects, try several strategies:
            // 1) If object implements __toString, use its string representation
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }

            // 2) Try casting to array to get protected/private properties
            $cast = (array) $data;
            $result = [];
            foreach ($cast as $key => $value) {
                // Normalize keys coming from (array) cast: "\0*\0prop" or "\0ClassName\0prop"
                $normKey = preg_replace('/^\x00.*\x00/', '', $key);
                $result[$normKey] = $this->deepArrayConvert($value);
            }

            if (!empty($result)) {
                return $result;
            }

            // 3) Fallback: try get_object_vars (public properties)
            $pub = get_object_vars($data);
            $array = [];
            foreach ($pub as $key => $value) {
                $array[$key] = $this->deepArrayConvert($value);
            }
            return $array;
        } else {
            // Primitive types (string, int, bool, null) - return as is
            return $data;
        }
    }

    public function addInitCode(string $viewName, string $viewId, string $code)
    {
        $this->registerView($viewName, $viewId);
        preg_match('/<script[^>]*>(.*?)<\/script>/s', $code, $matches);
        $script = $matches[1];
        if ($script) {

            $this->viewStorage[$viewName]['scripts']['init'] = $script;
        }
        preg_match('/<style[^>]*>(.*?)<\/style>/s', $code, $matches);
        $style = $matches[1];
        if ($style) {
            $this->viewStorage[$viewName]['styles']['init'] = $style;
        }
    }

    public function startWrapper(mixed $tags, array $attributes = [], string $viewId = '')
    {
        $this->wrapperLevel++;
        if (is_array($tags)) {
            $tags = $tags['tag'];
            unset($tags['tag']);
            $attributes = $tags;
        } elseif (is_string($tags)) {
            $tags = $tags;
        } else {
            $tags = 'div';
        }
        if (!is_array($attributes)) {
            $attributes = [];
        }
        $attributes['data-view-wrapper'] = $viewId;
        $this->wrapperStack[$this->wrapperLevel] = [
            'tag' => $tags,
            'attributes' => $attributes,
            'viewId' => $viewId
        ];

        echo '<' . $tags . ' ' . implode(' ', array_map(function ($key, $value) {
            return $key . '="' . $value . '"';
        }, array_keys($attributes), $attributes)) . '>';
    }

    public function endWrapper($viewId = null)
    {
        if ($this->wrapperLevel < 0) {
            return;
        }
        echo '</' . $this->wrapperStack[$this->wrapperLevel]['tag'] . '>';
        $this->wrapperLevel--;
    }

    public function startWrapperAttr($viewId = null)
    {
        echo ' data-view-wrapper="' . $viewId . '"';
    }

    /**
     * Asset của <head>: `<link rel=stylesheet>` / `<script src>` khai báo trong
     * view, ĐĂNG KÝ ở đây thay vì in tại chỗ khai báo.
     *
     * In tại chỗ là nguồn của lỗi quirks mode: với trang `@extends`, output nằm
     * ngoài block của view con được echo TRƯỚC khi layout in `<!DOCTYPE html>`,
     * nên thẻ đứng trước doctype và trình duyệt bỏ luôn doctype.
     *
     * @var array<string, array{kind: string, url: string, attrs: array<string, mixed>, flushed: bool}>
     */
    private array $headAssets = [];

    /**
     * Khoá trùng: `id` nếu có, không thì chính url. Cùng một khoá gọi bao nhiêu
     * lần cũng chỉ ra MỘT thẻ — layout, page và mọi @include dùng chung một file
     * css chỉ tốn một <link>, kể cả khi view được include nhiều lần trong trang.
     */
    public function addHeadAsset(string $kind, string $url, array $attributes = []): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }
        $id = isset($attributes['id']) ? trim((string) $attributes['id']) : '';
        $key = $kind.':'.($id !== '' ? 'id='.$id : $url);
        if (isset($this->headAssets[$key])) {
            return;
        }
        $this->headAssets[$key] = [
            'kind' => $kind,
            'url' => $url,
            'attrs' => $attributes,
            'flushed' => false,
        ];
    }

    /**
     * Lấy các asset CHƯA in (và đánh dấu đã in) — gọi được nhiều lần ở nhiều chỗ.
     *
     * @param  string|null $kind  null = mọi loại
     * @return list<array{kind: string, url: string, attrs: array<string, mixed>}>
     */
    public function pullHeadAssets(?string $kind = null): array
    {
        $out = [];
        foreach ($this->headAssets as $key => $asset) {
            if ($asset['flushed'] || ($kind !== null && $asset['kind'] !== $kind)) {
                continue;
            }
            $this->headAssets[$key]['flushed'] = true;
            unset($asset['flushed']);
            $out[] = $asset;
        }
        return $out;
    }

    public function addScript(string $viewName, string $viewId, $scripts)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName][$viewId]['scripts'] = $scripts;
    }
    public function addStyle(string $viewName, string $viewId, $styles)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName][$viewId]['styles'] = $styles;
    }

    public function addEventListener(string $viewPath = null, string $viewId = null, string $eventType = null, array $handlers = [])
    {
        if ($viewPath) {
            $this->registerView($viewPath, $viewId);
        }
        // $eventID = uniqid();
        if (!isset($this->viewStorage[$viewPath]['instances'][$viewId]['events'])) {
            $this->viewStorage[$viewPath]['instances'][$viewId]['events'] = [];
        }
        $eventIndex = count($this->viewStorage[$viewPath]['instances'][$viewId]['events']);
        $eventID = $viewId . '-' . $eventType . '-' . $eventIndex;
        $this->viewStorage[$viewPath]['instances'][$viewId]['events'][] = [
            'id' => $eventID,
            'type' => $eventType,
            'handlers' => $handlers
        ];
        return " data-{$eventType}-id=\"{$eventID}\"";
    }

    public function addEventQuickHandle(string $viewPath = null, string $viewId = null, string $eventType = null, array $quickHandlers = [])
    {
        if ($viewPath) {
            $this->registerView($viewPath, $viewId);
        }
        $eventID = uniqid();
        if (!isset($this->viewStorage[$viewPath]['instances'][$viewId]['quickHandles'][$eventType])) {
            $this->viewStorage[$viewPath]['instances'][$viewId]['quickHandles'][$eventType] = [];
        }
        $this->viewStorage[$viewPath]['instances'][$viewId]['quickHandles'][$eventType][$eventID] = $quickHandlers;
        return " data-{$eventType}-quick-id=\"{$eventID}\"";
    }

    public function subscribeState(string $viewPath = null, string $viewId = null, bool $subscribe = true)
    {
        if ($viewPath) {
            $this->registerView($viewPath, $viewId);
        }
        $this->viewStorage[$viewPath]['instances'][$viewId]['subscribe'] = $subscribe;
    }

    public function addOutputComponent(string $viewPath, string $viewId, string $ocTaskId, string $stateKeys)
    {
        $this->registerView($viewPath, $viewId);
        if (!isset($this->viewStorage[$viewPath]['instances'][$viewId]['outputComponents'])) {
            $this->viewStorage[$viewPath]['instances'][$viewId]['outputComponents'] = [];
        }
        $outputComponentIndex = count($this->viewStorage[$viewPath]['instances'][$viewId]['outputComponents']);
        $this->viewStorage[$viewPath]['instances'][$viewId]['outputComponents'][$outputComponentIndex] = [
            'id' => $ocTaskId,
            'stateKeys' => explode(',', $stateKeys)
        ];
        return $outputComponentIndex;
    }

    public function addTagAttribute(string $viewPath, string $viewId, array $config = [], $attr = null, $value = null)
    {
        // LEGACY removed 2026-06-23: từng lưu $config vào ssrData['attributes'] +
        // đánh dấu element bằng `data-one-attribute-id` cho hydration CŨ (client
        // tìm element qua id này rồi khôi phục reactive-attr từ APP_CONFIGS.ssrData).
        // Client hiện tại claim element qua class {viewId}-{id} và lấy reactivity từ
        // compiled factory (attrs:{...}) — KHÔNG đọc ssrData/data-one-attribute-id.
        // Nên chỉ render giá trị attr TĨNH (giá trị khởi tạo) ra HTML, bỏ marker thừa.
        $this->registerView($viewPath, $viewId);

        $output = '';
        if (!$attr) {
            return $output;
        }
        if (!is_array($attr)) {
            $attr = [$attr => $value];
        }
        foreach ($attr as $key => $val) {
            if (in_array($key, ['#children', '#content', '#value', '#text'])) {
                continue;
            }
            $eValue = e($val);
            $output .= " {$key}=\"{$eValue}\"";
        }
        return $output;
    }

    public function setState(string $viewPath, string $viewId, string $stateKey, mixed $stateValue)
    {
        $this->registerView($viewPath, $viewId);
        if (!isset($this->viewStorage[$viewPath]['instances'][$viewId]['states'])) {
            $this->viewStorage[$viewPath]['instances'][$viewId]['states'] = [];
        }
        if (is_object($stateValue) || is_array($stateValue)) {
            $stateValue = $this->deepArrayConvert($stateValue);
        }
        $this->viewStorage[$viewPath]['instances'][$viewId]['states'][$stateKey] = $stateValue;
    }

    public function addMarkerTagShortcut(string $name, string $shortcut){
        $this->markerTagShortcut[$name] = $shortcut;
    }

    public function getMarkerTagShortcut(string $name){
        return $this->markerTagShortcut[$name] ?? $name;
    }

    public function getMarkerKey(string $name, string $registryID){
        $name = $this->getMarkerTagShortcut($name);
        return $name.':'.$registryID;
    }

    public function addReactiveRegistry(string $type, string $registryID, string $stateKeys, array $options = []){
        $key = $this->getMarkerKey('reactive', $registryID);
        $this->markerRegistery[$key] = [
            'type' => $type,
            'registryID' => $registryID,
            'attributes' => [
                'stateKeys' => explode(',', $stateKeys),
                'options' => $options
            ]
        ];
        return $key;
    }

    public function addMarkerRegistry(string $name, string $registryID, array $attributes = []){
        $key = $this->getMarkerKey($name, $registryID);
        $this->markerRegistery[$key] = [
            'name' => $name,
            'registryID' => $registryID,
            'attributes' => $attributes
        ];
        return $key;
    }

    // Format chuẩn (RUNTIME_CONTRACT.md §5.1) — phải khớp client MarkerRegistry:
    //   open:  <!--s:{shortcut}:{id}-s-->
    //   close: <!--s:{shortcut}:{id}-e-->
    public function getMarkerOpenTag(string $name, string $registryID){
        $key = $this->getMarkerKey($name, $registryID);
        return '<!--'.$this->markerPrefix.':'.$key.'-s-->';
    }
    public function getMarkerCloseTag(string $name, string $registryID){
        $key = $this->getMarkerKey($name, $registryID);
        return '<!--'.$this->markerPrefix.':'.$key.'-e-->';
    }
}
