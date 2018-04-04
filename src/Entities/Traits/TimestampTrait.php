<?php
namespace Entities\Traits;

trait TimestampTrait
{
    public $created_at;

    public $updated_at;

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param mixed $time
     */
    public function setCreatedAt($time)
    {
        $this->created_at = $time;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param mixed $time
     */
    public function setUpdatedAt($time)
    {
        $this->updated_at = $time;
    }
}
