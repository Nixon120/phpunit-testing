<?php
namespace Entities;

use Entities\Traits\StatusTrait;
use Entities\Traits\TimestampTrait;

class Adjustment extends Base
{
    use TimestampTrait;
    use StatusTrait;

    public $participant_id;

    public $amount = 0.00;

    public $type;

    public $transaction_id;

    public $reference;

    public $description;

    public $completed_at;

    /**
     * @var Participant|null
     */
    private $participant;

    public function __construct(?Participant $participant = null)
    {
        parent::__construct();

        if (!is_null($participant)) {
            $this->setParticipantId($participant->getId());
            $this->setParticipant($participant);
        }
        $date = new \DateTime();
        $this->setUpdatedAt($date->format('Y-m-d H:i:s'));
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
     * Participant should never be null when fetching
     * Let's get some exception going here?
     * @return Participant
     */
    public function getParticipant():Participant
    {
        return $this->participant;
    }

    /**
     * @param Participant|null $participant
     */
    public function setParticipant(?Participant $participant)
    {
        $this->setParticipantId($participant->getId());
        $this->participant = $participant;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        $point = $this->getParticipant()->getProgram()->getPoint();
        return bcmul($this->amount, $point, 5);
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $point = $this->getParticipant()->getProgram()->getPoint();
        $this->amount = bcdiv($amount, $point, 5);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        $type = 'credit';
        switch ($this->type) {
            case 2:
                $type = 'debit';
                break;
        }
        return $type;
    }

    /**
     * @param mixed $default
     */
    public function setType(string $default)
    {
        $type = 1;
        switch ($default) {
            case 'debit':
                $type = 2;
                break;
        }
        $this->type = $type;
    }

    public function isGeneratedFromTransaction()
    {
        return $this->transaction_id === null ? false : true;
    }

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId(string $transactionId)
    {
        $this->transaction_id = $transactionId;
    }

    /**
     * @return string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference(?string $reference = null)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(?string $description = null)
    {
        $this->description = $description;
    }

    /**
     * @param mixed $completed_at
     */
    public function setCompletedAt($completed_at)
    {
        $this->completed_at = $completed_at;
    }

    /**
     * @param Mixed_
     */
    public function getCompletedAt()
    {
        return $this->completed_at;
    }
}
