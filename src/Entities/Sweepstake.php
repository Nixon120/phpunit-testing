<?php
namespace Entities;

use Entities\Traits\ProgramTrait;
use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class Sweepstake extends \Entities\Base
{
    use TimestampTrait;
    use ProgramTrait;

    /**
     * Overwrite the default of 1, as this isn't active by default
     * @var int
     */
    public $active = 0;

    public $start_date;

    public $end_date;

    public $type = 'manual';

    public $point;

    public $max_participant_entry;

    private $drawing = [];

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

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param mixed $startDate
     */
    public function setStartDate($startDate)
    {
        $this->start_date= $startDate;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param mixed $endDate
     */
    public function setEndDate($endDate)
    {
        $this->end_date= $endDate;
    }

    /**
     * @return string
     */
    public function getType():string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param mixed $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
    }

    /**
     * @return SweepstakeDraw[]
     */
    public function getDrawing(): array
    {
        return $this->drawing;
    }

    /**
     * @param SweepstakeDraw[] $drawings
     */
    public function setDrawing(array $drawings)
    {
        $this->drawing = $drawings;
    }

    /**
     * @return mixed
     */
    public function getMaxParticipantEntry()
    {
        return $this->max_participant_entry;
    }

    /**
     * @param mixed $max_participant_entry
     */
    public function setMaxParticipantEntry($max_participant_entry)
    {
        $this->max_participant_entry = $max_participant_entry;
    }
}
