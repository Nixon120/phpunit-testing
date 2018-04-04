<?php
namespace Entities\Traits;

trait ReferenceTrait
{
    public $reference_id;

    /**
     * @return mixed
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * @param mixed $ref
     */
    public function setReferenceId($ref)
    {
        $this->reference_id = $ref;
    }
}
