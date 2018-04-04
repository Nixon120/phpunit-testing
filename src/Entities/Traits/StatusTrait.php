<?php
namespace Entities\Traits;

trait StatusTrait
{
    public $active = 1;

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
