<?php

namespace Entities;

/**
 * Base
 * @TODO if we add a PDO object to the base entity constructor
 * I think we can lazily populate the relational fields with constructor..
 */
abstract class Base implements \JsonSerializable
{
    public function __construct()
    {
        $this->setTimestamps();
    }

    public $id;

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $data = call_user_func('get_object_vars', $this);

        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $data;
    }

    public function exchange(iterable $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = $this->getSetterMethod($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        $this->setTimestamps();
        return $this;
    }

    private function setTimestamps()
    {
        $time = new \DateTime();
        if ((isset($this->created_at) || property_exists($this, 'created_at')) && $this->created_at === null) {
            $this->created_at = $time->format('Y-m-d H:i:s');
        }
    }

    private function getSetterMethod($propertyName)
    {
        return "set" . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
