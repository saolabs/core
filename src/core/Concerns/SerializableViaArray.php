<?php

namespace Saola\Core\Concerns;

trait SerializableViaArray
{
    public function serialize(): string
    {
        return serialize($this->toSerializableArray());
    }

    public function unserialize($serialized): void
    {
        $data = unserialize($serialized);
        $this->loadFromSerializedArray($data);
    }

    protected function toSerializableArray(): array
    {
        $ref = new \ReflectionClass($this);
        $data = [];

        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $data[$prop->getName()] = $prop->getValue($this);
        }

        return $data;
    }

    protected function loadFromSerializedArray(array $data): void
    {
        $ref = new \ReflectionClass($this);
        foreach ($data as $key => $value) {
            if ($ref->hasProperty($key)) {
                $prop = $ref->getProperty($key);
                $prop->setAccessible(true);
                $prop->setValue($this, $value);
            }
        }
    }
}