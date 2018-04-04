<?php

namespace Entities\Traits;

trait DataExchangeTrait
{
    public function exchange(iterable $options)
    {
        $methods = get_class_methods($this);

        foreach ($options as $key => $value) {
            $method = $this->getSetterMethod($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    protected function getSetterMethod($propertyName)
    {
        return "set" . str_replace(
            ' ',
            '',
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $propertyName
                )
            )
        );
    }
}
