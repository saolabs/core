<?php

namespace Saola\Core\Support\Methods;

trait AttributeMethods
{
    /**
     * Attributes storage
     * 
     * @var array
     */
    protected $attributes = [];

    /**
     * Attribute casts
     * Format: ['attribute_name' => 'cast_type']
     * 
     * Supported casts:
     * - int, integer
     * - float, double, real
     * - string
     * - bool, boolean
     * - array
     * - json
     * - object
     * - collection
     * - date, datetime
     * - timestamp
     * 
     * @var array
     */
    protected $casts = [];

    /**
     * Cached cast methods for performance
     * 
     * @var array
     */
    protected static $cachedCastMethods = [];

    /**
     * Khởi tạo attribute methods
     * 
     * @return void
     */
    public function initAttributeMethods()
    {
        if (!method_exists($this, 'addAttributeMacro')) {
            throw new \Exception('Method addAttributeMacro not found in class ' . static::class . '. Please use OneMacro trait.');
        }

        // Sử dụng constants từ OneMacro (nếu class đã use OneMacro)
        // Fallback về string literal nếu không có constant
        $getAttribute = defined(static::class . '::GET_ATTRIBUTE') 
            ? constant(static::class . '::GET_ATTRIBUTE') 
            : '__getAttribute__';
        $setAttribute = defined(static::class . '::SET_ATTRIBUTE') 
            ? constant(static::class . '::SET_ATTRIBUTE') 
            : '__setAttribute__';
        $issetAttribute = defined(static::class . '::ISSET_ATTRIBUTE') 
            ? constant(static::class . '::ISSET_ATTRIBUTE') 
            : '__issetAttribute__';
        $unsetAttribute = defined(static::class . '::UNSET_ATTRIBUTE') 
            ? constant(static::class . '::UNSET_ATTRIBUTE') 
            : '__unsetAttribute__';

        // Getter với casting
        $this->addAttributeMacro($getAttribute, function($name) {
            // Custom accessor có priority cao nhất
            if (method_exists($this, $method = 'get' . ucfirst($name) . 'Attribute')) {
                return $this->{$method}();
            }
            
            // Lấy giá trị từ attributes
            $value = $this->attributes[$name] ?? null;
            
            // Apply cast nếu có
            if ($value !== null && isset($this->casts[$name])) {
                $value = $this->castAttribute($name, $value);
            }
            
            return $value;
        });

        // Setter với casting
        $this->addAttributeMacro($setAttribute, function($name, $value) {
            // Custom mutator có priority cao nhất
            if (method_exists($this, $method = 'set' . ucfirst($name) . 'Attribute')) {
                return $this->{$method}($value);
            }
            
            // Apply cast trước khi lưu
            if (isset($this->casts[$name])) {
                $value = $this->castAttributeAs($name, $value);
            }
            
            $this->attributes[$name] = $value;
        });

        $this->addAttributeMacro($issetAttribute, function($name) {
            if (method_exists($this, $method = 'isset' . ucfirst($name) . 'Attribute')) {
                return $this->{$method}();
            }
            return isset($this->attributes[$name]);
        });

        $this->addAttributeMacro($unsetAttribute, function($name) {
            if (method_exists($this, $method = 'unset' . ucfirst($name) . 'Attribute')) {
                return $this->{$method}();
            }
            unset($this->attributes[$name]);
        });
    }

    /**
     * Cast attribute value when getting
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        $castType = $this->casts[$key];
        
        if ($value === null) {
            return null;
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
                
            case 'float':
            case 'double':
            case 'real':
                return (float) $value;
                
            case 'string':
                return (string) $value;
                
            case 'bool':
            case 'boolean':
                return (bool) $value;
                
            case 'array':
                return is_array($value) ? $value : json_decode($value, true) ?? [];
                
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
                
            case 'object':
                return is_object($value) ? $value : (object) $value;
                
            case 'collection':
                if (class_exists(\Illuminate\Support\Collection::class)) {
                    return is_array($value) || is_object($value) 
                        ? \Illuminate\Support\Collection::make($value) 
                        : \Illuminate\Support\Collection::make();
                }
                return is_array($value) ? $value : [];
                
            case 'date':
            case 'datetime':
                if (is_string($value)) {
                    try {
                        return new \DateTime($value);
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return $value instanceof \DateTime ? $value : null;
                
            case 'timestamp':
                if (is_numeric($value)) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    return strtotime($value) ?: null;
                }
                return null;
                
            default:
                // Custom cast method
                if (method_exists($this, $method = 'cast' . ucfirst($castType) . 'Attribute')) {
                    return $this->{$method}($key, $value);
                }
                return $value;
        }
    }

    /**
     * Cast attribute value when setting
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttributeAs($key, $value)
    {
        $castType = $this->casts[$key];
        
        if ($value === null) {
            return null;
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
                
            case 'float':
            case 'double':
            case 'real':
                return (float) $value;
                
            case 'string':
                return (string) $value;
                
            case 'bool':
            case 'boolean':
                return (bool) $value;
                
            case 'array':
                return is_array($value) ? json_encode($value) : $value;
                
            case 'json':
                return is_array($value) || is_object($value) 
                    ? json_encode($value) 
                    : $value;
                
            case 'object':
                return is_object($value) ? json_encode($value) : $value;
                
            case 'collection':
                if (is_array($value) || (is_object($value) && method_exists($value, 'toArray'))) {
                    $array = is_array($value) ? $value : $value->toArray();
                    return json_encode($array);
                }
                return $value;
                
            case 'date':
            case 'datetime':
                if ($value instanceof \DateTime) {
                    return $value->format('Y-m-d H:i:s');
                }
                if (is_string($value) || is_numeric($value)) {
                    return $value;
                }
                return null;
                
            case 'timestamp':
                if ($value instanceof \DateTime) {
                    return $value->getTimestamp();
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    return strtotime($value) ?: null;
                }
                return null;
                
            default:
                // Custom cast method
                if (method_exists($this, $method = 'castAs' . ucfirst($castType) . 'Attribute')) {
                    return $this->{$method}($key, $value);
                }
                return $value;
        }
    }

    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set attributes
     * 
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Get casts
     * 
     * @return array
     */
    public function getCasts()
    {
        return $this->casts;
    }

    /**
     * Set casts
     * 
     * @param array $casts
     * @return $this
     */
    public function setCasts(array $casts)
    {
        $this->casts = array_merge($this->casts, $casts);
        return $this;
    }
}