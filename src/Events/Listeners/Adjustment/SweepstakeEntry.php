<?php

namespace Events\Listeners\Adjustment;

use AllDigitalRewards\AMQP\MessagePublisher;
use AllDigitalRewards\Services\Catalog\Client;
use Entities\Event;
use Entities\Participant;
use Entities\Sweepstake;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Participant\Participant as ParticipantService;
use Services\Program\Exception\SweepstakeServiceException;
use Services\Program\Sweepstake as SweepstakeService;
use Traits\LoggerAwareTrait;

class SweepstakeEntry extends AbstractListener
{
    use LoggerAwareTrait;

    /**
     * @var ParticipantService
     */
    private $participantService;

    /**
     * @var SweepstakeService
     */
    private $sweepstakeService;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var Participant
     */
    private $participant;

    /**
     * @var Client
     */
    private $catalogService;

    public function __construct(
        MessagePublisher $publisher,
        ParticipantService $participantService,
        SweepstakeService $sweepstakeService,
        Client $catalogService
    ) {
        parent::__construct($publisher);
        $this->participantService = $participantService;
        $this->sweepstakeService = $sweepstakeService;
        $this->catalogService = $catalogService;
    }

    public function handle(EventInterface $event)
    {
        /**
         * @var Event $event
         */
        $this->event = $event;

        // Validate Participant exists.
        if ($this->participantExists() === false) {
            // Removed the re-queueing of the event because the participant will never be found.
            // and that has nothing to do with sweepstakes.
            $this->getLogger()->error(
                'Participant does not exist.',
                [
                    'system' => 'Event',
                    'subsystem' => 'SweepstakeEntry',
                    'action' => 'Adjustment.SweepstakeEntry',
                    'participant_id' => $this->event->getEntityId(),
                    'success' => false,
                ]
            );

            return false;
        }

        if ($this->isEligibleForEntry() === false) {
            return false;
        }

        return $this->createEntry($event);
    }

    private function participantExists(): bool
    {
        try {
            $this->getParticipant();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    private function getParticipant(): Participant
    {
        if (is_null($this->participant)) {
            $this->participant = $this
                ->participantService
                ->getById(
                    $this->event->getEntityId()
                );
        }

        return $this->participant;
    }

    private function getSweepstake(): Sweepstake
    {
        return $this
            ->getParticipant()
            ->getProgram()
            ->getSweepstake();
    }

    private function createEntry(Event $event)
    {
        if ($this->createSweepstakeEntry() === true) {
            return true;
        }

        // Something failed that should not have failed.
        $this->getLogger()->error(
            'Failed to create Sweepstake Entry.',
            [
                'system' => 'Event',
                'subsystem' => 'SweepstakeEntry',
                'action' => 'Adjustment.SweepstakeEntry',
                'participant_id' => $this->event->getEntityId(),
                'participant_unique_id' => $this->participant->getUniqueId(),
                'success' => false,
            ]
        );

        $event->setName('Adjustment.sweepstakeEntry');
        $this->reQueueEvent($event);
        return false;
    }

    private function isEligibleForEntry(): bool
    {
        if ($this->isSweepstakeEligible() === false) {
            return false;
        }

        if ($this->isParticipantSweepstakeEligible() === false) {
            return false;
        }

        return true;
    }

    private function isSweepstakeEligible(): bool
    {
        $program = $this->getParticipant()->getProgram();
        $now = (new \DateTime)->format('Y-m-d');
        if ($program->getSweepstake() === null
            || $program->getSweepstake()->isActive() === false
            || $program->getSweepstake()->getStartDate() <= $now === false
            || $program->getSweepstake()->getEndDate() >= $now === false
            || $program->getSweepstake()->getType() === 'manual'
        ) {
            return false;
        }

        return true;
    }

    //@TODO with parallel processing, should we lock the participant ?
    private function isParticipantSweepstakeEligible(): bool
    {
        if ($this->getSingleEntryPointCost() >= $this->participant->getCredit()) {
            // Participant does not have enough points to redeem for an entry.
            return false;
        }

        $maxEntryCount = $this->getSweepstake()->getMaxParticipantEntry();

        $existingEntryCount = $this
            ->sweepstakeService
            ->getParticipantEntryCount(
                $this->getSweepstake(),
                $this->getParticipant()
            );

        if ($existingEntryCount >= $maxEntryCount) {
            // Participant already has the maximum number of entries.
            return false;
        }

        return true;
    }

    private function createSweepstakeEntry()
    {
        try {
            $this->sweepstakeService->createSweepstakeEntry(
                $this->getParticipant(),
                [
                    'entryCount' => $this->getEntryCount()
                ]
            );
        } catch (SweepstakeServiceException $exception) {
            // @TODO Log this failure.
            return false;
        }

        return true;
    }

    private function getSingleEntryPointCost()
    {
        $sweepstake = $this->getParticipant()->getProgram()->getSweepstake();
        $product = $this->catalogService->getProduct($sweepstake->getSku());
        $entryCost = $this->getParticipant()->getProgram()->getPoint() * $product->getPriceTotal();

        return $entryCost;
    }

    private function getEntryCount()
    {
        // Number of entries a participant already has.
        $existingEntryCount = $this
            ->sweepstakeService
            ->getParticipantEntryCount(
                $this->getSweepstake(),
                $this->participant
            );

        // Maximum number of entries a participant may redeem for.
        $maxEntries = $this->getSweepstake()->getMaxParticipantEntry() - $existingEntryCount;

        // Point cost to redeem for the remaining available entries.
        $maxEntryPointCost = bcmul($maxEntries, $this->getSingleEntryPointCost(), 2);

        if (bcsub($this->participant->getCredit(), $maxEntryPointCost) < 0) {
            // Participant does not have enough points to redeem for all remaining entries.
            // Determine how many entries they can afford.
            $maxEntries = floor(
                bcdiv(
                    $this->participant->getCredit(),
                    $this->getSingleEntryPointCost()
                )
            );
        }

        return $maxEntries;
    }
}
