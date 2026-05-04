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
        $this->markerRegistery = [];
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

    public function startWrapper(array | string $tags, array $attributes = [], string $viewId = '')
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

    public function addScript($viewName, $viewId, $scripts)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName][$viewId]['scripts'] = $scripts;
    }
    public function addStyle($viewName, $viewId, $styles)
    {
        $this->registerView($viewName, $viewId);
        $this->viewStorage[$viewName][$viewId]['styles'] = $styles;
    }

    public function addEventListener($viewPath = null, $viewId = null, $eventType = null, $handlers = [])
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

    public function addEventQuickHandle($viewPath = null, $viewId = null, $eventType = null, $quickHandlers = [])
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

    public function subscribeState($viewPath = null, $viewId = null, $subscribe = true)
    {
        if ($viewPath) {
            $this->registerView($viewPath, $viewId);
        }
        $this->viewStorage[$viewPath]['instances'][$viewId]['subscribe'] = $subscribe;
    }

    public function addOutputComponent($viewPath, $viewId, $ocTaskId, $stateKeys)
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

    public function addTagAttribute($viewPath, $viewId, $config = [], $attr = null, $value = null)
    {
        $this->registerView($viewPath, $viewId);
        if (!isset($this->viewStorage[$viewPath]['instances'][$viewId]['attributes'])) {
            $this->viewStorage[$viewPath]['instances'][$viewId]['attributes'] = [];
        }
        $id = uniqid();
        $this->viewStorage[$viewPath]['instances'][$viewId]['attributes'][] = [
            'id' => $id,
            'config' => $config
        ];

        $output = " data-one-attribute-id=\"{$id}\"";

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

    public function setState($viewPath, $viewId, $stateKey, $stateValue)
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

    public function addMarkerTagShortcut($name, $shortcut){
        $this->markerTagShortcut[$name] = $shortcut;
    }

    public function getMarkerTagShortcut($name){
        return $this->markerTagShortcut[$name] ?? $name;
    }

    public function getMarkerKey($name, $registryID){
        $name = $this->getMarkerTagShortcut($name);
        return $name.':'.$registryID;
    }

    public function addReactiveRegistry($type, $registryID, $stateKeys, $options = []){
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

    public function addMarkerRegistry($name, $registryID, $attributes = []){
        $key = $this->getMarkerKey($name, $registryID);
        $this->markerRegistery[$key] = [
            'name' => $name,
            'registryID' => $registryID,
            'attributes' => $attributes
        ];
        return $key;
    }

    public function getMarkerOpenTag($name, $registryID){
        $key = $this->getMarkerKey($name, $registryID);
        return '<!--'.$this->markerPrefix.':'.$key.'-->';
    }
    public function getMarkerCloseTag($name, $registryID){
        $key = $this->getMarkerKey($name, $registryID);
        return '<!--/'.$this->markerPrefix.':'.$key.'-->';
    }
}
