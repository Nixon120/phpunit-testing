<?php
namespace Entities;

use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class RangedPricing extends Base
{
    use TimestampTrait;

    public $active;

    public $unique_id;

    public $min;

    public $max;

    public function getUniqueId()
    {
        return $this->unique_id;
    }

    public function setUniqueId($uniqueId)
    {
        $this->unique_id = $uniqueId;
    }

    public function getMin()
    {
        return $this->min;
    }

    public function setMin($min)
    {
        $this->min = $min;
    }

    public function getMax()
    {
        return $this->max;
    }

    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->active == 1;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getActive()
    {
        return $this->active;
    }
}
