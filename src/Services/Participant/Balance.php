<?php
namespace Services\Participant;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Interfaces as Interfaces;
use Entities\Adjustment;
use Entities\Event;
use Repositories\BalanceRepository;
use Repositories\ParticipantRepository;

class Balance
{
    /**
     * @var BalanceRepository
     */
    public $repository;

    /**
     * @var ParticipantRepository
     */
    public $participantRepository;

    private $eventPublisher;

    /**
     * @var \Entities\Participant
     */
    private $participant;

    public function __construct(
        BalanceRepository $repository,
        ParticipantRepository $participantRepository,
        MessagePublisher $eventPublisher
    ) {
    
        $this->repository = $repository;
        $this->participantRepository = $participantRepository;
        $this->eventPublisher = $eventPublisher;
    }

    public function createAdjustment(\Entities\Participant $participant, Interfaces\InputNormalizer $input)
    {
        $this->participant = $participant;
        //@TODO Return AdjustmentRequestObject from getInput normalize refactor, e.g, $input->getType(), $input->getAmount()
        $data = $input->getInput();
        $reference = $data['reference'] ?? null;
        $description = $data['description'] ?? null;

        $adjustment = new Adjustment($participant);
        $adjustment->setType($data['type']);
        $adjustment->setAmount($data['amount']);
        $adjustment->setReference($reference);
        $adjustment->setDescription($description);

        if ($this->repository->validate($adjustment) && $this->repository->addAdjustment($adjustment)) {
            $adjustment = $this->repository->getAdjustment($participant, $this->repository->getLastInsertId());
            $this->repository->updateParticipantCredit($adjustment);
            $this->queueAdjustmentEvent($adjustment);
            return $adjustment;
        }
    }

    private function queueAdjustmentEvent(Adjustment $adjustment)
    {
        $event = new Event();
        $event->setName('Adjustment.' . $adjustment->getType());
        $event->setEntityId($adjustment->getId());
        $this->eventPublisher->publishJson($event);
    }

    //@TODO clean up, be less vague.
    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new BalanceFilterNormalizer($input->getInput());
        $adjustments = $this->repository->getCollection($filter, $input->getOffset(), 30);
        return $adjustments;
    }

    //@TODO update naming to be less vague
    public function getSingle(\Entities\Participant $participant, $adjustmentId)
    {
        return $this->repository->getAdjustment($participant, $adjustmentId);
    }

    public function getAdjustmentForWebhook($adjustmentId)
    {
        return $this->repository->getAdjustmentForWebhook($adjustmentId);
    }

    public function getParticipantAdjustments(
        \Entities\Participant $participant,
        string $fromDate = null,
        string $toDate = null
    )
    {
        return $this->repository->getAdjustmentsByParticipant(
            $participant,
            $fromDate,
            $toDate
        );
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}
