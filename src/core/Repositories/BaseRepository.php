<?php

namespace Saola\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Exception;
use Saola\Core\Concerns\MagicMethods;
use Saola\Core\Events\EventMethods;

/**
 * Base Repository class providing common functionality for all repositories
 * 
 * @abstract
 */
abstract class BaseRepository
{
    use BaseQuery, BaseSearchQuery, GettingAction, CRUDAction, FilterAction, DataAction, OwnerAction, CacheAction, FileAction, EventMethods, MagicMethods;
    
    // Constants for better maintainability
    private const EVENT_PREFIXES = ['on', 'emit'];
    private const METHOD_PREFIXES = ['on', 'emit'];
    private const DEFAULT_MODEL_PRIMARY_KEY = 'id';
    
    // Auto-check owner functionality
    protected $checkOwner = true;

    protected $_primaryKeyName = self::DEFAULT_MODEL_PRIMARY_KEY;
    
    /**
     * @var Model|SQLModel|MongoModel
     */
    protected $_model;

    protected $modelType = 'default';

    /**
     * @var string name of class model
     */
    protected $model = null;

    /**
     * Constructor - initializes the repository with model and required properties
     */
    public function __construct()
    {
        $this->initializeModel();
        $this->setupRepository();
    }

    /**
     * Initialize the model instance
     */
    private function initializeModel(): void
    {
        if ($this->model && class_exists($this->model)) {
            $this->_model = app($this->model);
        } elseif (method_exists($this, 'getModel')) {
            $this->setModel();
        }
    }

    /**
     * Setup repository properties and initialize required components
     */
    private function setupRepository(): void
    {
        if ($this->_model) {
            $this->_primaryKeyName = $this->_model->getKeyName();
            
            if ($this->required == self::DEFAULT_MODEL_PRIMARY_KEY && $this->_primaryKeyName) {
                $this->required = $this->_primaryKeyName;
            }
            
            $this->modelType = $this->_model->__getModelType__();
            $this->ownerInit();
            $this->init();
            
            if (!$this->defaultValues) {
                $this->defaultValues = $this->_model->getDefaultValues();
            }
        }
    }

    /**
     * Get the primary key name for the model
     * 
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->_primaryKeyName;
    }

    /**
     * Initialize repository-specific settings
     * Override this method in child classes
     */
    protected function init(): void
    {
        // Child classes can override this method
    }

    /**
     * Create a new repository instance
     *
     * @return static
     */
    public function newRepo(): static
    {
        return new static();
    }

    /**
     * Check if records exist based on given criteria
     *
     * @param string|int|float ...$args
     * @return bool
     */
    final public function exists(...$args): bool
    {
        if (empty($args)) {
            return false;
        }
        
        if (count($args) === 1 && (is_string($args[0]) || is_numeric($args[0]))) {
            return $this->countBy($this->_primaryKeyName, $args[0]) > 0;
        }
        
        return $this->countBy(...$args) > 0;
    }

    /**
     * Static method to check existence
     *
     * @param string|int|float|array $id
     * @return bool
     */
    public static function checkExists($id): bool
    {
        return app(static::class)->exists($id);
    }

    /**
     * Handle calls to undefined methods
     *
     * @param string $method
     * @param array $params
     * @return static
     */
    public function __call($method, $params)
    {
        $key = strtolower($method);
        
        if ($this->handleSqlClause($method, $params)) {
            return $this;
        }
        
        if (!empty($params)) {
            $this->handleMethodWithParams($method, $params);
        } else {
            $this->handleMethodWithoutParams($method, $params);
        }
        
        return $this;
    }

    /**
     * Handle SQL clause methods
     */
    private function handleSqlClause(string $method, array $params): bool
    {
        $f = $this->sqlclause[strtolower($method)] ?? null;
        
        if (!$f) {
            return false;
        }
        
        if (!isset($this->actions) || !is_array($this->actions)) {
            $this->actions = [];
        }
        
        if ($f === 'groupby') {
            $this->handleGroupBy($method, $params);
        } else {
            $this->actions[] = compact('method', 'params');
        }
        
        return true;
    }

    /**
     * Handle GROUP BY clause
     */
    private function handleGroupBy(string $method, array $params): void
    {
        if (count($params) === 1 && is_string($params[0])) {
            $params = array_map('trim', explode(',', $params[0]));
        }
        
        foreach ($params as $column) {
            $this->actions[] = [
                'method' => $method,
                'params' => [$column]
            ];
        }
    }

    /**
     * Handle method calls with parameters
     */
    private function handleMethodWithParams(string $key, array $params): void
    {
        $value = $params[0];
        $key2 = $key;
        $key = strtolower($key);
        if ($this->handleWhereable($key, $value)) {
            return;
        }
        
        if ($this->handleSearchableFields($key, $value)) {
            return;
        }
        
        if ($this->handleEventMethods($key, $params)) {
            return;
        }
        
        if ($this->handleMagicMethods($key, $params)) {
            return;
        }
        
        $this->handleEventListeners($key2, $params);
    }

    /**
     * Handle whereable fields
     */
    private function handleWhereable(string $key, $value): bool
    {
        if (!$this->whereable || !is_array($this->whereable)) {
            return false;
        }
        
        if (isset($this->whereable[$key])) {
            $this->where($this->whereable[$key], $value);
            return true;
        }
        
        if (in_array($key, $this->whereable)) {
            $this->where($key, $value);
            return true;
        }
        
        return false;
    }

    /**
     * Handle searchable fields
     */
    private function handleSearchableFields(string $key, $value): bool
    {
        $fields = array_merge([$this->required], $this->getFields());
        
        if (in_array($key, $fields)) {
            $this->where($key, $value);
            return true;
        }
        
        return false;
    }

    /**
     * Handle event methods
     */
    private function handleEventMethods(string $key, array $params): bool
    {
        if (in_array($key, static::$eventMethods ?? [])) {
            static::callEventMethod($key, $params);
            return true;
        }
        
        return false;
    }

    /**
     * Handle magic methods
     */
    private function handleMagicMethods(string $key, array $params): bool
    {
        if ($this->_funcExists($key)) {
            $this->_nonStaticCall($key, $params);
            return true;
        }
        
        return false;
    }

    /**
     * Handle event listeners
     */
    private function handleEventListeners(string $key, array $params): void
    {
        if (str_starts_with($key, 'on') && 
            strlen($event = substr($key, 2)) > 0 && 
            ctype_upper(substr($event, 0, 1)) && 
            count($params) && 
            (is_callable($params[0]) || is_callable([$this, $params[0]]))) {
            $this->_addEventListener(strtolower($event), $params[0]);
        }
    }

    /**
     * Handle method calls without parameters
     */
    private function handleMethodWithoutParams(string $method, array $params): void
    {
        if ($this->_funcExists($method)) {
            $this->_nonStaticCall($method, $params);
            return;
        }
        
        $this->handleEmitMethods($method, $params);
    }

    /**
     * Handle emit methods
     */
    private function handleEmitMethods(string $method, array $params): void
    {
        if (str_starts_with($method, 'emit')) {
            $event = substr($method, 4);
            
            if (strlen($event) > 0 && ctype_upper(substr($event, 0, 1))) {
                static::_dispatchEvent($event, ...$params);
            } else {
                static::_dispatchEvent(array_shift($params), ...$params);
            }
        }
    }

    /**
     * Handle static calls to undefined methods
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (in_array($method, static::$eventMethods ?? [])) {
            return static::_staticCall($method, $parameters);
        }
        
        return static::_staticCall($method, $parameters);
    }
}

// Register global functions
BaseRepository::globalStaticFunc('on', '_addEventListener');
BaseRepository::globalFunc('on', 'addEventListener');

