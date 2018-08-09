<?php
namespace Entities;

class SweepstakeEntry extends \Entities\Base
{
    public $sweepstake_id;

    public $sweepstake_draw_id;

    public $sweepstake_alt_draw_id;

    public $participant_id;

    public $point;

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
    public function getSweepstakeDrawId()
    {
        return $this->sweepstake_draw_id;
    }

    /**
     * @param mixed $sweepstake_draw_id
     */
    public function setSweepstakeDrawId($sweepstake_draw_id)
    {
        $this->sweepstake_draw_id = $sweepstake_draw_id;
    }

    /**
     * @return mixed
     */
    public function getParticipantId()
    {
        return $this->participant_id;
    }

    /**
     * @param mixed $participant_id
     */
    public function setParticipantId($participant_id)
    {
        $this->participant_id = $participant_id;
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
     * @return mixed
     */
    public function getSweepstakeAltDrawId()
    {
        return $this->sweepstake_alt_draw_id;
    }

    /**
     * @param mixed $sweepstake_alt_draw_id
     */
    public function setSweepstakeAltDrawId($sweepstake_alt_draw_id): void
    {
        $this->sweepstake_alt_draw_id = $sweepstake_alt_draw_id;
    }
}
