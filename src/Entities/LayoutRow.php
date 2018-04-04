<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class LayoutRow extends Base
{
    use TimestampTrait;

    public $priority;

    public $program_id;

    public $label;

    private $cards;

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return mixed
     */
    public function getProgramId()
    {
        return $this->program_id;
    }

    /**
     * @param mixed $programId
     */
    public function setProgramId($programId)
    {
        $this->program_id = $programId;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function setCards(array $cards)
    {
        $this->cards = $cards;
    }

    /**
     * @return LayoutRowCard[]
     */
    public function getCards(): array
    {
        if (empty($this->cards)) {
            return [];
        }

        return $this->cards;
    }

    public function getCard($priority):?array
    {
        return $this->getCards()[$priority] ?? null;
    }
}
