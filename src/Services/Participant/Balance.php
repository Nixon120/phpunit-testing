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
        $completedAt = $data['completed_at'] ?? null;
        $activity = $data['activity'] ?? null;

        $adjustment = new Adjustment($participant);
        $adjustment->setType($data['type']);
        $adjustment->setAmount($data['amount']);
        $adjustment->setReference($reference);
        $adjustment->setDescription($description);
        $adjustment->setCompletedAt($completedAt);
        $adjustment->setActivity($activity);

        if ($this->repository->validate($adjustment) && $this->repository->addAdjustment($adjustment)) {
            $adjustment = $this->repository->getAdjustment($participant, $this->repository->getLastInsertId());
            $this->repository->updateParticipantCredit($adjustment);
            $this->queueAdjustmentWebhookEvent($adjustment);
            $this->queueAdjustmentEvent($adjustment->getType());
            return $adjustment;
        }
    }

    public function updateAdjustment(Adjustment $adjustment)
    {
        if (!$this->repository->validate($adjustment)) {
            return false;
        }

        return $this->repository->update($adjustment->getId(), $adjustment->toArray());
    }

    private function queueAdjustmentEvent($type)
    {
        $event = new Event();
        $event->setName('Adjustment.' . $type);
        $event->setEntityId($this->participant->getId());
        $this->eventPublisher->publishJson($event);
    }

    /**
     * @param Adjustment $adjustment
     */
    protected function queueAdjustmentWebhookEvent(Adjustment $adjustment)
    {
        $event = new Event();
        $event->setName('AdjustmentWebhook.' . $adjustment->getType());
        $event->setEntityId($adjustment->getId());
        $this
            ->eventPublisher
            ->publish(json_encode($event));
    }

    //@TODO clean up, be less vague.
    public function get(Interfaces\InputNormalizer $input)
    {
        $filter = new BalanceFilterNormalizer($input->getInput());
        $this->repository->orderBy = " ORDER BY adjustment.created_at DESC ";
        $adjustments = $this->repository->getCollection($filter, $input->getPage(), $input->getLimit());
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

    public function getParticipantAdjustments(\Entities\Participant $participant)
    {
        return $this->repository->getAdjustmentsByParticipant($participant);
    }

    public function getErrors()
    {
        return $this->repository->getErrors();
    }
}
