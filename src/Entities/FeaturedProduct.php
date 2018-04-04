<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class FeaturedProduct extends Base
{
    use TimestampTrait;

    public $program_id;

    public $sku;

    /**
     * @return mixed
     */
    public function getProgramId()
    {
        return $this->program_id;
    }

    /**
     * @param mixed $program_id
     */
    public function setProgramId($program_id)
    {
        $this->program_id = $program_id;
    }

    /**
     * @return mixed
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param mixed $sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }
}
