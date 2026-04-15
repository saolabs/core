<?php

namespace Saola\Core\Support;

class ViewState
{
    private $state;
    private $listeners = [];
    public function __construct($value)
    {
        $this->state = $value;
    }

    public function onUpdate($listener)
    {
        if(is_callable($listener)) {
            $this->listeners[] = $listener;
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($value)
    {
        $this->state = $value;
        foreach ($this->listeners as $listener) {
            $listener($this->state);
        }
    }
}