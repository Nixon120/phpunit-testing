<?php
namespace Entities;

class SweepstakeDraw extends \Entities\Base
{
    public $sweepstake_id;

    public $date;

    public $draw_count;

    public $processed = 0;

    public $alt_entry = 0;

    /**
     * @return mixed
     */
    public function getSweepstakeId()
    {
        return $this->sweepstake_id;
    }

    /**
     * @param mixed $sweepstake_id
     */
    public function setSweepstakeId($sweepstake_id)
    {
        $this->sweepstake_id = $sweepstake_id;
    }

    /**
     * @return mixed
     */
    public function isElapsed()
    {
        $now = new \DateTime;
        $date = new \DateTime($this->date);
        return $now > $date;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getDrawCount()
    {
        return $this->draw_count;
    }

    /**
     * @param mixed $drawCount
     */
    public function setDrawCount($drawCount)
    {
        $this->draw_count = $drawCount;
    }

    /**
     * @param mixed $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    public function isProcessed()
    {
        return $this->processed == 1?true:false;
    }

    /**
     * @return int
     */
    public function getAltEntry(): int
    {
        return $this->alt_entry;
    }

    /**
     * @param int $alt_entry
     */
    public function setAltEntry(int $alt_entry): void
    {
        $this->alt_entry = $alt_entry;
    }

}
